<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileExportRequest;
use App\Http\Requests\FileImportRequest;
use App\Http\Requests\FileNewRequest;
use App\Mail\ExportEmail;
use App\StanzaList;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileController extends Controller
{
    /**
     * Return a JSON encoded list of files in the user's storage folder.
     *
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function index()
    {
        // Check that a storage folder exists for the current user
        $basePath = Auth::user()->institution_code . '/';
        if (! Storage::disk('users')->exists($basePath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'A storage folder for the current user does not exist'
            ], 400);
        }

        // Generate a list of JSON files in the user's folder
        $jsonFiles = [];
        foreach (Storage::disk('users')->files($basePath) as $filename) {
            // Remove the leading path value so just the filename itself is returned
            if (preg_match('#^' . $basePath . '(.*\.json)$#', $filename, $matches)) {
                    //$files[] = preg_replace('#^' . $basePath . '#', '', $filename );
                $jsonFiles[] = $matches[1];
            }
        }

        // Return a JSON encoded array of filenames
        return response()->json([
            'status' => 'success',
            'message' => 'The request for a list of files was successful',
            'data' => $jsonFiles
        ], 200);
    }


    /**
     * Creates a new Costanza (JSON) formatted EZproxy file with OCLC's default configuration as the base.
     *
     * @param FileNewRequest $request
     * @return \Illuminate\Http\Response
     */
    public function create(FileNewRequest $request)
    {
        $filename = $request->get('filename');
        $fileContents = '[]';

        if ($request->get('default') == true) {
            // Read in the JSON formatted version of OCLC's default EZproxy config
            $fileContents = Storage::get('stub.json');
        }

        // Save this as a Costanza config file for the user
        if (Storage::disk('users')->put(Auth::user()->institution_code . '/' . $filename, $fileContents)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully created ' . $filename
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to create the file ' . $filename
        ], 400);
    }


    /**
     * Return the contents of the specified Costanza file
     *
     * @param string $filename
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function show($filename)
    {
        // Check that the desired file exists
        if (! Storage::disk('users')->exists(Auth::user()->institution_code . '/' .  $filename)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Costanza could not find the specified file (' . $filename . ')'
            ], 400);
        }

        // Read the entire contents of the (JSON formatted) file
        $ezproxyFile = Storage::disk('users')->get(Auth::user()->institution_code . '/' .  $filename);

        return response()->json([
            'status' => 'success',
            'message' => 'The request for file ' . $filename . ' was successful',
            'data' => json_decode($ezproxyFile)
        ],200);
    }


    public function export(FileExportRequest $request)
    {
        $exportErrors = [];
        $exportedFiles = [];
        $oclc_includes = false;
        if ($request->get('oclc_includes') === true) {
            $oclc_includes = true;
        }

        // Successfully converted files will be added to a (local) ZIP file (whcih in turn will be sent to the user via email)
        $zipFileName = Storage::disk('users')->path(Auth::user()->institution_code . '/Costanza_EZproxy_Export.zip');

        // Each uploaded file needs to be converted to the internal JSON format used by Costanza
        foreach ($request->get('files') as $filename) {
            $status = $this->convertToEZproxy($filename, $oclc_includes);
            if ($status === true) {
                $exportedFiles[] = $filename;
            } else {
                $exportErrors[$filename] = $status;
            }
        }

        // Add all files that converted cleanly to a ZIP file; include the JSON versions as well
        $zipFile = new \ZipArchive();
        $zipFile->open($zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($exportedFiles as $filename) {
            $textFilename = preg_replace("/\.json$/i", '.txt', $filename);
            $zipFile->addFile(Storage::disk('users')->path(Auth::user()->institution_code . '/' . $textFilename), $textFilename);
            $zipFile->addFile(Storage::disk('users')->path(Auth::user()->institution_code . '/' . $filename), 'JSON/' . $filename);
        }
        $zipFile->close();

        // Check for errors/warning that may have happened during the export process
        if (! empty($exportErrors)) {
            return response()->json([
                'status' => 'warning',
                'message' => 'One or more imported files contained errors',
                'errors' => $exportErrors
            ],422);
        }

        // Send the ZIP file to the user
        Mail::to(Auth::user())->send(new ExportEmail($zipFileName));

        return response()->json([
            'status' => 'success',
            'message' => 'Export process completed successfully',
            'data' => $exportedFiles
        ],200);

    }

    /**
     * Convert a JSON formatted Costanza file into the plain text format required by EZproxy.
     *
     * The output actually has a bit of structure to it, to make automated parsing later somewhat
     * easier.
     *
     * - Each entry in the file is enclosed by '### BEGIN' and '### END' lines
     * - The BEGIN line includes the original entry "type" as defined by Costanza.
     * - For entries of type "stanza", the BEGIN line contains the stanza "code", and
     *   any alternate name the user gave to this stanza
     * - For entries of type "custom_stanza", the BEGIN line contains any alternate name
     *   the user gave to this stanza
     * - If the user added any notes/comments to the entry, these begin with "##"
     * - If the user marked the entry "inactive", the directives that make up the entry will begin
     *   with a single "#"
     *
     * @param $filename String      Name of the JSON formatted file to convert
     * @return array|bool           Returns TRUE on success; an array of error/warning messages on failure
     */
    private function convertToEZproxy($filename, $oclc_includes)
    {
        // Keep track of any errors/warnings that may occur during the conversion process in an array
        $conversionErrors = [];

        // The output filename is the same as the original, but with a .txt extension instead of .json
        $textFilename = preg_replace("/\.json$/i", '.txt', $filename);
        $outputFile = Auth::user()->institution_code . '/' . $textFilename;

        // Check that the specified JSON file actually exists.
        if (! Storage::disk('users')->exists(Auth::user()->institution_code . '/' . $filename)) {
            // Conversion cannot continue with out source JSON file.
            return ['The file ' . $filename . ' does not exist'];
        }

        // Delete any previously converted file with this name
        Storage::disk('users')->delete($outputFile);


        // To cut down on memory usage, the EZproxy output for each entry is stored in a buffer
        // during the conversion process, then flushed out to disk. It means more calls to
        // Storage::append, but that should be better then storing the complete JSON and text
        // content in memory

        // The first "entry" in the file is a simple header
        $buffer = [
            '# ======================================================================',
            '# EZproxy Configuration File ' . $textFilename,
            '# Generated by Costanza version ' . config('app.version'),
            '# ======================================================================',
        ];
        if (! Storage::disk('users')->append($outputFile, implode("\n", $buffer) . "\n\n")) {
            // Failure to write the buffer is a fatal error
            return ['Unable to write ' . $textFilename];
        }

        // Read the entire contents of the JSON formatted file into an associative array, and process each entry
        $ezproxyFile = json_decode(Storage::disk('users')->get(Auth::user()->institution_code . '/' .  $filename), true);
        foreach ($ezproxyFile as $entry) {
            // Empty the output buffer
            $buffer = [];

            switch ($entry['type']) {
                case 'comment':
                    $buffer[] = '### BEGIN: comment';
                    // Comments really only contain one field of data for the output file -- the comment itself
                    // Each line of the comment is preceded by a double #
                    foreach ((array)$entry['value'] as $comment) {
                        $buffer[] = '## ' . $comment;
                    }
                    $buffer[] = '### END: comment';
                    break;

                case 'directive':
                    $buffer[] = '### BEGIN: directive';
                    // Directives can have user supplied notes/comments
                    // Each line of the comment is preceded by a double #
                    if (! empty($entry['comment'])) {
                        foreach ((array)$entry['comment'] as $comment) {
                            $buffer[] = '## ' . $comment;
                        }
                    }
                    // Costanza allows a single Directive to have multiple values
                    // Each will appear on their own line in the output file, of course
                    // If the user marked this entry inactive, precede each line with a single #
                    foreach ((array)$entry['value'] as $value) {
                        $buffer[] = ((isset($entry['active']) && ($entry['active'] === false)) ? '#' : '') .
                            $entry['name'] . ' ' . $value;
                    }
                    $buffer[] = '### END: directive';
                    break;

                case 'group':
                    // Groups can have user supplied notes/comments
                    // Each line of the comment is preceded by a double #
                    $buffer[] = '### BEGIN: group';
                    if (! empty($entry['comment'])) {
                        foreach ((array)$entry['comment'] as $comment) {
                            $buffer[] = '## ' . $comment;
                        }
                    }
                    // Each Group entry can have only one value
                    // If the user marked this entry inactive, precede each line with a single #
                    $buffer[] = ((isset($entry['active']) && ($entry['active'] === false)) ? '#' : '') .
                        'GROUP ' . $entry['name'];
                    $buffer[] = '### END: group';
                    break;

                case 'stanza':
                    // To aid in parsing later, include the stanza code and name
                    $buffer[] = '### BEGIN: stanza|' .
                        $entry['code'] . '|' .
                        ((! empty($entry['name'])) ? $entry['name'] : '') ;
                    // Stanzas can have user supplied notes/comments
                    // Each line of the comment is preceded by a double #
                    if (! empty($entry['comment'])) {
                        foreach ((array)$entry['comment'] as $comment) {
                            $buffer[] = '## ' . $comment;
                        }
                    }
                    // The "master" stanza list should contain a matching entry
                    $stanza = StanzaList::get($entry['code']);
                    if (empty($stanza)) {
                        $conversionErrors[] = 'Unknown stanza code ' . $entry['code'];
                        $buffer[] = '# *** Costanza did not recognize stanza code ' . $entry['code'];
                    } elseif ($oclc_includes && (! empty($stanza->oclcIncludeFile))) {
                        $buffer[] .= 'IncludeFile ' . $stanza->oclcIncludeFile;
                    } else {
                        // Get the contents of the actual stanza
                        $stanzaDirectives = StanzaList::getStanza($entry['code']);
                        if (empty($stanzaDirectives)) {
                            $conversionErrors[] = 'Could not find the stanza contents for ' . $entry['code'];
                        } else {
                            // ToDo: Process any rules for manipulating the stanza here before it gets dumped to the output file
                            foreach ($stanzaDirectives as $value) {
                                $buffer[] = ((isset($entry['active']) && ($entry['active'] === false)) ? '#' : '') . $value;
                            }
                        }
                    }
                    $buffer[] = '### END: stanza';
                    break;

                case 'custom_stanza':
                    // To aid in parsing later, include the custom stanza name
                    $buffer[] = '### BEGIN: custom_stanza|' . ((! empty($entry['name'])) ? $entry['name'] : '') ;
                    // Custom Stanzas can have user supplied notes/comments
                    // Each line of the comment is preceded by a double #
                    if (! empty($entry['comment'])) {
                        foreach ((array)$entry['comment'] as $comment) {
                            $buffer[] = '## ' . $comment;
                        }
                    }
                    // Each line of the custom stanza is stored in the value array
                    // If the user marked this entry inactive, precede each line with a single #
                    foreach ((array)$entry['value'] as $value) {
                        $buffer[] = ((isset($entry['active']) && ($entry['active'] === false)) ? '#' : '') . $value;
                    }
                    $buffer[] = '### END: stanza';
                    break;
            }

            // Flush the entry to disk by appending the buffer to the output file
            if (! empty($buffer)) {
                if (! Storage::disk('users')->append($outputFile, implode("\n", $buffer) . "\n")) {
                    // Failure to write the buffer is a fatal error
                    return ['Unable to write ' . $textFilename];
                }
            }
        }

        // Return an array containing any errors/warnings, or TRUE if there were none
        if (! empty($conversionErrors)) {
            return $conversionErrors;
        }
        return true;
    }


    /**
     * @param FileImportRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function import(FileImportRequest $request)
    {
        // Each uploaded file needs to be converted to the internal JSON format used by Costanza
        $importErrors = [];
        $importedFiles = [];
        foreach ($request->file('EZproxyFiles') as $file) {
            $filename = $file->getClientOriginalName();
            $importedFiles[] = $filename;

            $status = $this->convertFromEZproxy($file);
            if (! empty($status)) {
                $importErrors[$filename] = $status;
            }
        }

        if (! empty($importErrors)) {
            return response()->json([
                'status' => 'warning',
                'message' => 'One or more imported files contained errors',
                'warnings' => $importErrors
            ],400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully imported EZproxy configuration',
            'data' => implode(', ', $importedFiles)
        ],200);
    }

    /**
     * Convert an EZproxy file from its text format to the JSON format used by Costanza,
     * identifying stanzas where possible
     *
     * @param UploadedFile $uploadedFile
     * @return array|null
     */
    private function convertFromEZproxy(UploadedFile $uploadedFile) {
        // Drop the original (.txt) extension and add .json
        $filename = str_replace('.' . $uploadedFile->getClientOriginalExtension(), '', $uploadedFile->getClientOriginalName()) . '.json';

        // Check that the file upload was successful
        if (! $uploadedFile->isValid()) {
            return ['The file upload failed'];
        }

        // First a few arrays and flags needed for processing
        $EZproxyConfig = [];
        $warnings = [];

        // Read the input file line by line
        $file = preg_split("/[\r\n]+/", File::get($uploadedFile));

        while ($entry = self::getNextEZproxyEntry($file)) {
            // Ensure the current entry has an ID number, and add it to the EZproxy config array
            $entry['id'] = Str::uuid()->toString();

            if (! empty($entry['warnings'])) {
                $warnings = array_merge($warnings, $entry['warnings']);
                unset($entry['warnings']);
            }

            $EZproxyConfig[] = $entry;
        }

        // Save this file in JSON format
        Storage::disk('users')->put(Auth::user()->institution_code . '/' .  $filename, json_encode($EZproxyConfig, JSON_PRETTY_PRINT));

        if (! empty($warnings)) {
            return $warnings;
        }

        return null;
    }


    /**
     * Returns a single "entry" from the EZproxy file.
     *
     * Note that the array representing the EZproxy file is passed by reference, and *will* be modified.
     * This function was meant to be called repeatedly, to extract each parsable entry from the EZproxy file
     * in turn, until none are left.
     *
     * @param   array       $array  An array representing each line of the EZproxy config file
     * @return  array|bool          Returns an array representing the entry extracted, or FALSE when the array is empty
     */
    private static function getNextEZproxyEntry(&$array) {
        $validStanzaDirectives = [
            'Title', 'T',
            'URL', 'U',
            'Host', 'HostJavaScript', 'H', 'HJ',
            'Domain', 'DomainJavaScript', 'D', 'DJ',
            'Description',
            'MimeFilter',
            'Find', 'Replace',
            'AddUserHeader',
            'AllowVars', 'EncryptVar',
            'FormSelect', 'Formvariable', 'FormSubmit',
            'ProxyHostnameEdit',

            // The following directives can be used as part of a stanza, but are position independent directives
            // Including them here as a stanza directive will lead to multiple single line misidentified stanzas
            //'HTTPHeader', 'HTTPMethod',
            //'Option',
            //'Proxy', 'ProxySSL',
            //'RedirectSafe',

            // Weird site specific directives
            'Books24x7Site', 'TokenKey', 'TokenSignatureKey',
            'EBLSecret',
            'ebrarySite',
            'MetaFind',

            // Are these problematic???
            'Cookie', 'CookieFilter',
        ];

        // Stitch together the above array into a string for use a regex later
        // Yes, these directives could have been written as a string to start with, but the array notation is
        // easier to read and edit.
        $stanzaDirectives = implode('|', $validStanzaDirectives);

        // Pull the first line off the array. If it's a blank line, continue until one that isn not blank is found
        $line = null;
        while (empty($line) && (! empty($array))) {
            $line = array_shift($array);
        }

        // If the array is empty, there is nothing to do.
        if (empty($array)) {
            return false;
        }

        // Keep track of the Title. The Title is needed, but finding a second one indicates the start of a new stanza
        $title = null;
        $entry = [];

        // The first "element" on the line determines what type of entry this is.
        // The remainder of the line is "data" portion
        if (preg_match("/^\s*#(.*)$/", $line, $matches)) {
            // A comment
            $entry['type'] = 'comment';
            $entry['value'][] = empty($matches[1]) ? '' : $matches[1];

            // Continue to read any subsequent comments
            while ((! empty($array)) && preg_match("/^\s*#(.*)$/", $array[0], $matches)) {
                $line = array_shift($array);
                $entry['value'][] = empty($matches[1]) ? '' : $matches[1];
            }
        } elseif (preg_match("/^\s*GROUP\s+(.*)$/i", $line, $matches)) {
            // A Group directive
            $entry['type'] = 'group';
            $entry['name'] = $matches[1];
        } elseif (preg_match("/^\s*(" . $stanzaDirectives . ")\s+(.*)$/i", $line, $matches)) {
            // A stanza directive
            $entry['type'] = 'custom_stanza';
            $entry['value'][] = $matches[1] . ' ' . $matches[2];
            if (preg_match('/^(Title|T)$/i', $matches[1])) {
                // Save the title
                $title = $matches[2];
                $entry['name'] = $title;
            }

            // Continue to read any subsequent stanza directives
            while ((! empty($array)) && preg_match("/^\s*(" . $stanzaDirectives . ")\s+(.*)$/i", $array[0], $matches)) {
                if (preg_match('/^(Title|T)$/i', $matches[1]) && empty($title)) {
                    // Encountered Title and title is empty
                    $title = $matches[2];
                    $line = array_shift($array);
                    $entry['name'] = $title;
                    $entry['value'][] = $matches[1] . ' ' . $matches[2];
                } elseif (preg_match('/^(Title|T)$/i', $matches[1]) && (! empty($title))) {
                    // Encountered Title but title is NOT empty
                    // Signals the end of the current entry
                    break;
                } else {
                    // Is a stanza directive, but not the title
                    $line = array_shift($array);
                    $entry['value'][] = $matches[1] . ' ' . $matches[2];
                }
            }
        } elseif (preg_match("/^\s*([^\s]+)\s+(.*)$/", $line, $matches)) {
            // Some other directive
            $entry['type'] = 'directive';
            $entry['name'] = $matches[1];
            $entry['value'][] = $matches[2];

            // Continue to read any subsequent matching non-stanza directives
            while ((! empty($array)) && preg_match("/^\s*" . $entry['name'] . "\s+(.*)/i", $array[0], $matches)) {
                $line = array_shift($array);
                $entry['value'][] = $matches[1];
            }
        } else {
            $entry['warnings'][] = "Failed to properly parse line: " . $line;
        }

        return $entry;
    }
}
