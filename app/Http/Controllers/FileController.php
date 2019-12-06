<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileExportRequest;
use App\Http\Requests\FileImportRequest;
use App\Http\Requests\FileNewRequest;
use App\Mail\ExportEmail;
use App\StanzaList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileController extends Controller
{
    // These EZproxy directives are considered to be part of a "database stanza" as that is typically where they appear
    private static $stanzaDirectives = [
        'Title', 'T',
        'URL', 'U',
        'HostJavaScript', 'HJ',
        'Host', 'H',
        'DomainJavaScript', 'DJ',
        'Domain', 'D',
        'AddUserHeader',
        'AllowVars', 'EncryptVar',
        'AnonymousURL',
        'Cookie', 'CookieFilter',
        'Description',
        'FormSelect', 'FormVariable', 'FormSubmit',
        'Find', 'Replace',
        'HTTPHeader',                   // Despite being part of some OCLC stanzas, this may be better in nonStanzaDirectives
        'HTTPMethod',                   // Despite being part of some OCLC stanzas, this may be better in nonStanzaDirectives
        'MimeFilter',
        'NeverProxy',                   // NeverProxy is position independent but is often used within individual stanzas
        'Option Cookie',
        'Option CookiePassThrough',
        'Option DomainCookieOnly',
        'Option NoCookie',
        'Option HideEZproxy',
        'Option NoHideEZproxy',
        'Option HttpsHyphens',
        'Option NoHttpsHyphens',
        'Option MetaEZproxyRewriting',
        'Option NoMetaEZproxyRewriting',
        'Option ProxyFTP',
        'Option NoProxyFTP',
        'Option UTF16', 'Option NoUTF16',
        'Option X-Forwarded-For', 'Option NoX-Forwarded-For',
        'ProxyHostnameEdit', 'PHE',
        'Proxy', 'ProxySSL',
        'Referer',
        'Validate',

        // Site specific directives
        'Books24x7Site', 'TokenKey', 'TokenSignatureKey',
        'EBLSecret',
        'ebrarySite',
        'MetaFind',

        'Option AllowSendGZip',         // Does not appear to be a valid directive, but appears in Visible_Body stanza
        'Option ebraryUnencodedTokens', // Does not appear to be a valid directive, but appears in Ebrary and Ebook_Central stanzas
        'Option X-Forwarded',           // Does not appear to be a valid directive, but appears in eTG_Complete stanza
        'SSO',                          // Does not appear to be a valid directive, but appears in Skillsoft-Skillport
    ];

    // These directives are NOT typically used in a "database stanza"; instead they typically control how EZproxy functions
    private static $nonStanzaDirectives = [
        'Audit',
        'AuditPurge',
        'AutoLoginIP',
        'AutoLoginIPBanner',
        'BinaryTimeout',
        'CASServiceURL',
        'Charset',
        'ClientTimeout',
        'ConnectWindow',
        'DbVar',
        'DenyIfRequestHeader',
        'DNS',
        'ExcludeIP',
        'ExcludeIPBanner',
        'ExtraLoginCookie',
        'FirstPort',
        'Gartner',
        'HAName',
        'HAPeer',
        'IncludeFile',
        'IncludeIP', 'I',
        'Interface',
        'IntruderIPAttempts',
        'IntruderLog',
        'IntruderUserAttempts',
        'IntrusionAPI', 'WhitelistIP',
        'LBPeer',
        'Location',
        'LogFile',
        'LogFilter',
        'LogFormat',
        'LoginCookieDomain',
        'LoginCookieName',
        'LoginMenu',
        'LoginPort',
        'LoginPortSSL',
        'LogSPU',
        'MaxConcurrentTransfers', 'MC',
        'MaxLifetime', 'ML',
        'MaxSessions', 'MS',
        'MaxVirtualHosts', 'MV',
        // 'MessagesFile',                  // This directive should never appear in messages.txt not config.txt
        'Name',
        'Option AcceptX-Forwarded-For',
        'Option AllowWebSubdirectories',
        'Option AnyDNSHostname',
        'Option BlockCountryChange',
        'Option CSRFToken',
        'Option DisableSSL40bit',
        'Option DisableSSLv2',
        'Option ExcludeIPMenu',
        'Option ForceHTTPSAdmin',
        'Option ForceHTTPSLogin',
        'Option ForceWildcardCertificate',
        'Option IgnoreWildcardCertificate',
        'Option IPv6',
        'Option I choose to use Domain lines that threaten the security of my network',
        'Option LoginReplaceGroups',
        'Option LogRefer',
        'Option LogSAML',
        'Option LogSession',
        'Option LogSPUEdit',
        'Option LogUser',
        'Option MenuByGroups',
        'Option ProxyByHostname',
        'Option RecordPeaks',
        // 'Option RedirectUnknown',        // Disabled in EZproxy 5.1c
        'Option ReferInHostname',
        'Option RelaxedRADIUS',
        'Option RequireAuthenticate',
        'Option SafariCookiePatch',
        'Option StatusUser',
        'Option TicketIgnoreExcludeIP',
        'Option UnsafeRedirectUnknown',
        'Option UsernameCaretN',
        'OverDriveSite',
        'P3P',
        'PDFRefresh', 'PDFRefreshPre', 'PDFRefreshPost',
        'PidFile',
        'RADIUSRetry',
        'RedirectSafe',
        'RejectIP',
        'RemoteIPHeader', 'RemoteIPInternalProxy', 'RemoteIPTrustedProxy',
        'RemoteTimeout',
        'RunAs',
        'ShibbolethDisable',
        'SkipPort',
        'SPUEdit', 'SPUEditVar',
        'SSLCipherSuite',
        'SSLHonorCipherOrder',
        'SSLOpenSSLConfCmd',
        'UMask',
        'URLAppendEncoded',             // (replaced by URL -Append -Encoded)
        'URLRedirectAppendEncoded',     // (replaced by URL -Redirect -Append -Encoded)
        'URLRedirectAppend',            // (replaced by URL -Redirect -Append)
        'URLRedirect',                  // (replaced by URL -Redirect)
        'UsageLimit',
        'XDebug',
    ];


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


    /**
     * Export one or more EZproxy configuration files (in plain text format), converting them to the
     * internal format (JSON) used by Costanza. The converted files, along with the JSON formatted files
     * are placed in a ZIP archive abd sent via email to the current user.
     *
     * @param FileExportRequest $request
     * @return mixed
     */
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
     * - Each entry in the file is enclosed by '#-- BEGIN' and '#-- END' lines
     * - The BEGIN line includes the original entry "type" as defined by Costanza as well as the entry ID.
     * - For all entries other than a comment, the BEGIN line includes the active/inactive state of the entry.
     * - For entries of type "stanza", the BEGIN line contains the stanza "code", and
     *   any alternate name the user gave to this stanza
     * - For entries of type "custom_stanza", the BEGIN line contains any alternate name
     *   the user gave to this stanza
     * - If the user added any notes/comments to the entry, these begin with "#--"
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
            '#-- Costanza version ' . config('app.version') . ' -- EZproxy Configuration generated ' . date('c'),
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
                    $buffer[] = '#-- BEGIN: comment|' . $entry['id'];
                    // Comments really only contain one field of data for the output file -- the comment itself
                    // Each line of the comment is preceded by a double #
                    foreach ((array)$entry['value'] as $comment) {
                        $buffer[] = '#-- ' . $comment;
                    }
                    $buffer[] = '#-- END: comment';
                    break;

                case 'directive':
                    $buffer[] = '#-- BEGIN: directive|' . $entry['id'] . '|'
                        . (isset($entry['active']) && ($entry['active'] == false) ? 'false' : 'true');
                    // Directives can have user supplied notes/comments
                    // Each line of the comment is preceded by a double #
                    if (! empty($entry['comment'])) {
                        foreach ((array)$entry['comment'] as $comment) {
                            $buffer[] = '#-- ' . $comment;
                        }
                    }
                    // Costanza allows a single Directive to have multiple values
                    // Each will appear on their own line in the output file, of course
                    // If the user marked this entry inactive, precede each line with a single #
                    foreach ((array)$entry['value'] as $value) {
                        $buffer[] = ((isset($entry['active']) && ($entry['active'] == false)) ? '#' : '') .
                            $entry['name'] . ' ' . $value;
                    }
                    $buffer[] = '#-- END: directive';
                    break;

                case 'group':
                    // Groups can have user supplied notes/comments
                    // Each line of the comment is preceded by a double #
                    $buffer[] = '#-- BEGIN: group|' . $entry['id'] . '|' .
                        (isset($entry['active']) && ($entry['active'] == false) ? 'false' : 'true');
                    if (! empty($entry['comment'])) {
                        foreach ((array)$entry['comment'] as $comment) {
                            $buffer[] = '#-- ' . $comment;
                        }
                    }
                    // Each Group entry can have only one value
                    // If the user marked this entry inactive, precede each line with a single #
                    $buffer[] = ((isset($entry['active']) && ($entry['active'] == false)) ? '#' : '') .
                        'GROUP ' . $entry['name'];
                    $buffer[] = '#-- END: group';
                    break;

                case 'stanza':
                    // To aid in parsing later, include the stanza code and name
                    $buffer[] = '#-- BEGIN: stanza|' .
                        $entry['id'] . '|' .
                        (isset($entry['active']) && ($entry['active'] == false) ? 'false' : 'true') . '|' .
                        $entry['code'] . '|' .
                        ((! empty($entry['name'])) ? $entry['name'] : '') ;

                    // Stanzas can have user supplied notes/comments
                    // Each line of the comment is preceded by a double #
                    if (! empty($entry['comment'])) {
                        foreach ((array)$entry['comment'] as $comment) {
                            $buffer[] = '#-- ' . $comment;
                        }
                    }
                    // The "master" stanza list should contain a matching entry
                    $stanza = StanzaList::get($entry['code']);
                    if (empty($stanza)) {
                        $conversionErrors[] = 'Unknown stanza code ' . $entry['code'];
                        $buffer[] = '#-- Costanza did not recognize stanza code ' . $entry['code'];
                    } elseif ($oclc_includes && (! empty($stanza->oclcIncludeFile))) {
                        if (is_array($stanza->oclcIncludeFile)) {
                            foreach ($stanza->oclcIncludeFile as $includeFile) {
                                $buffer[] .= 'IncludeFile ' . $includeFile;
                            }
                        } else {
                            $buffer[] .= 'IncludeFile ' . $stanza->oclcIncludeFile;
                        }
                    } else {
                        // Get the contents of the actual stanza
                        $stanzaDirectives = StanzaList::getStanza($entry['code']);
                        if (empty($stanzaDirectives)) {
                            $conversionErrors[] = 'Could not find the stanza contents for ' . $entry['code'];
                        } else {
                            if (! empty($entry['rules'])) {
                                $stanzaDirectives = $this->processRules($stanzaDirectives, $entry['rules']);
                            }
                            foreach ($stanzaDirectives as $value) {
                                $buffer[] = ((isset($entry['active']) && ($entry['active'] === false)) ? '#' : '') . $value;
                            }
                        }
                    }
                    $buffer[] = '#-- END: stanza';
                    break;

                case 'custom_stanza':
                    // To aid in parsing later, include the custom stanza name
                    $buffer[] = '#-- BEGIN: custom_stanza|' . $entry['id'] . '|' .
                        (isset($entry['active']) && ($entry['active'] == false) ? 'false' : 'true') . '|' .
                        ((! empty($entry['name'])) ? $entry['name'] : '') ;
                    // Custom Stanzas can have user supplied notes/comments
                    // Each line of the comment is preceded by a double #
                    if (! empty($entry['comment'])) {
                        foreach ((array)$entry['comment'] as $comment) {
                            $buffer[] = '#-- ' . $comment;
                        }
                    }
                    // Each line of the custom stanza is stored in the value array
                    // If the user marked this entry inactive, precede each line with a single #
                    foreach ((array)$entry['value'] as $value) {
                        $buffer[] = ((isset($entry['active']) && ($entry['active'] === false)) ? '#' : '') . $value;
                    }
                    $buffer[] = '#-- END: stanza';
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
     * Apply a list of processing rules to the directives in a stanza
     *
     * @param array $stanza
     * @param array $rules
     * @return array
     */
    private function processRules(array $stanza, array $rules) {
         $newStanza = $stanza;

        foreach ($rules as $rule) {
            if ($rule['enabled']) {
                switch ($rule['rule']) {
                    case 'Prepend':
                        if (! empty($rule['value'])) {
                            $newStanza = array_merge(preg_split("/[\r\n]/", $rule['value']), $newStanza);
                        }
                        break;
                    case 'Append':
                        if (! empty($rule['value'])) {
                            $newStanza = array_merge($newStanza, preg_split("/[\r\n]/", $rule['value']));
                        }
                        break;
                    case 'Replace':
                        if (! empty($rule['term'])) {
                            $directives = [];
                            foreach ($newStanza as $directive) {
                                if (! empty($rule['value'])) {
                                    $directives[] = str_replace($rule['term'], $rule['value'], $directive);
                                } else {
                                    $directives[] = str_replace($rule['term'], '', $directive);
                                }
                            }
                            $newStanza = $directives;
                        }
                        break;
                }
            }
        }

        return $newStanza;
    }

/**
     * Import one or more EZproxy configuration files (in plain text format), converting them to the
     * internal format (JSON) used by Costanza
     *
     * @param FileImportRequest $request
     * @return mixed
     */
    public function import(FileImportRequest $request)
    {
        // Some arrays to hold any errors/warnings returned by the converter, as well as the list of converted files
        $importErrors = [];
        $importedFiles = [];

        // Process each uploaded file in turn
        foreach ($request->file('EZproxyFiles') as $file) {
            // Save just the filename portion
            $filename = $file->getClientOriginalName();
            $importedFiles[] = $filename;

            // Perform the file conversion.  The return value is either null, or an array of warnings/errors
            $status = $this->convertFromEZproxy($file);
            if (! empty($status)) {
                $importErrors[$filename] = $status;
            }
        }

        // If there were errors, return those...
        if (! empty($importErrors)) {
            return response()->json([
                'status' => 'warning',
                'message' => 'One or more imported files contained errors',
                'warnings' => $importErrors
            ], 400);
        }

        // ... otherwise return success
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully imported EZproxy configuration',
            'data' => implode(', ', $importedFiles)
        ], 200);
    }

    /**
     * Convert a single EZproxy file from its text format to the JSON format used by Costanza,
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

        // Read the input file into an array, one element per line
        $file = preg_split("/[\r\n]/", File::get($uploadedFile));

        // Extract each "entry" (comment, group, stanza, directive) from the file array
        $lineNumber = 0;
        while ($entry = self::getNextEZproxyEntry($file, $lineNumber)) {
            if (! empty($entry['warnings'])) {
                $warnings = array_merge($warnings, $entry['warnings']);
                unset($entry['warnings']);
            }

            if (isset($entry['type']) && ($entry['type'] != 'unknown')) {
                $EZproxyConfig[] = $entry;
            }
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
     * @param   array       $fileArray  An array representing each line of the EZproxy config file
     * @return  array|bool          Returns an array representing the entry extracted, or FALSE when the array is empty
     */
    private static function getNextEZproxyEntry(&$fileArray, &$lineNumber) {
        // Stitch together the stanza and non-stanza directives into a string for use in a regex later
        $stanzaDirectives = implode('|', self::$stanzaDirectives);
        $nonStanzaDirectives = implode('|', self::$nonStanzaDirectives);

        // Skip over blank lines
        $line = null;
        while ((! empty($fileArray)) && (empty($line) || preg_match("/^#-- Costanza version /", $line))) {
            $line = trim(array_shift($fileArray));
            $lineNumber++;
        }

        // If the array is empty, there is nothing to do.
        if (empty($line) && empty($fileArray)) {
            return false;
        }

        // Keep track of the Title. The Title is needed, but finding a second one indicates the start of a new stanza
        $title = null;
        $entry = [];
        $entry['id'] = Str::uuid()->toString();

        // The first "element" on the line determines what type of entry this is.
        // The remainder of the line is "data" portion

        if (preg_match("/^#-- BEGIN:\s+(.*)$/", $line, $matches)) {
            // Specially formatted block that matches the export from Costanza
            // The BEGIN line encodes important details about the entry

            // COMMENT has only 2 encoded fields -- TYPE and ID
            $entryData = explode('|', $matches[1]);
            $entry['type'] = $entryData[0];
            $entry['id'] = $entryData[1];

            // Everything else has a 3rd encoded field -- ACTIVE
            if ($entry['type'] !== 'comment') {
                $entry['active'] = ($entryData[2] === 'false') ? false : true;
            }
            // CUSTOM STANZAS have an options 4th encoded field -- NAME
            if ($entry['type'] === 'custom_stanza') {
                $entry['name'] = empty($entryData[3]) ? '** UNKNOWN DATABASE **' : $entryData[3];
            }
            // STANZAS have a 4th encoded field and an optional 5th -- ID CODE and NAME
            if ($entry['type'] === 'stanza') {
                if (empty($entryData[3])) {
                    $entry['warnings'][] = ': Missing database ID code on line '  . $lineNumber;
                } else {
                    // Ensure the database ID code is actually valid
                    $stanza = StanzaList::get($entryData[3]);
                    if (empty($stanza->code)) {
                        // Add an error message to the queue and treat this entry like a custom stanza
                        $entry['warnings'][] = ': Invalid database ID code on line '  . $lineNumber . ': ' .
                            $entryData[3] . ' -- converting to custom stanza';
                        $entry['type'] = 'custom_stanza';
                        $entry['name'] = '** INVALID DATABASE **';
                    } else {
                        $entry['code'] = $entryData[3];
                    }
                }
                if (! empty($entryData[4])) {
                    $entry['name'] = $entryData[4];
                }
            }

            // Each BEGIN should have a matching END; keep reading lines until we find it
            while ((! empty($fileArray)) && (! preg_match("/^#-- END/", $fileArray[0]))) {
                $line = array_shift($fileArray);
                $lineNumber++;

                if (preg_match('/^#--\s+(.*)$/', $line, $matches)) {
                    // If the line starts with "#--" it is part of the comment/note
                    if ($entry['type'] === 'comment') {
                        $entry['value'][] = $matches[1];
                    } else {
                        $entry['comment'][] = $matches[1];
                    }
                } elseif (preg_match('/^#?(.*)$/', $line, $matches)) {
                    // All other lines form the "value"
                    if ($entry['type'] === 'directive') {
                        $fields = preg_split("/\s+/", $matches[1], 2);
                        $entry['name']= $fields[0];
                        $entry['value'][] = $fields[1];
                    } elseif ($entry['type'] === 'group') {
                        $fields = preg_split("/\s+/", $matches[1], 2);
                        $entry['name']= $fields[1];
                    } elseif ($entry['type'] === 'custom_stanza') {
                        $entry['value'][] = $matches[1];
                    } elseif ($entry['type'] == 'stanza') {
                        // Safely ignore the actual stanza directives
                    }
                }
            }
            // The END marker should be at the front of the file array; remove it
            if ((! empty($fileArray)) && preg_match("/^#-- END/", $fileArray[0])) {
                $line = array_shift($fileArray);
                $lineNumber++;
            }
        } elseif (preg_match("/^([#\s]*)GROUP\s+(.*)$/i", $line, $matches)) {
            // A GROUP directive, either active or inactive
            $entry['type'] = 'group';
            $entry['name'] = $matches[2];
            if (preg_match("/^\s*#/", $matches[1])) {
                $entry['active'] = false;
            }
        } elseif (preg_match("/^([#\s]*)(" . $stanzaDirectives . ")\b\s*(.*)$/i", $line, $matches)) {
            // A stanza directive, either active or inactive
            $entry['type'] = 'custom_stanza';
            $entry['value'][] = (preg_match('/#/', $matches[1]) ? '#' : '' ) . $matches[2] . ' ' . $matches[3];
            if (preg_match('/^(Title|T)$/i', $matches[2])) {
                // Save the title
                $title = $matches[3];
                $entry['name'] = $title;
            }

            // Continue to read any subsequent stanza directives
            while ((! empty($fileArray)) && preg_match("/^([#\s]*)(" . $stanzaDirectives . ")\b\s*(.*)$/i", $fileArray[0], $matches)) {
                if (preg_match('/^(Title|T)$/i', $matches[2]) && empty($title)) {
                    // Encountered Title and title is empty
                    $title = $matches[3];
                    $line = array_shift($fileArray);
                    $lineNumber++;
                    $entry['name'] = $title;
                    $entry['value'][] = (preg_match('/#/', $matches[1]) ? '#' : '' ) . $matches[2] . ' ' . $matches[3];
                } elseif (preg_match('/^(Title|T)$/i', $matches[2]) && (! empty($title))) {
                    // Encountered Title but title is NOT empty
                    // Signals the end of the current entry and the beginning of a new stanza
                    //  (because a stanza cannot have 2 titles; must be 2 stanzas joined together)
                    break;
                } else {
                    // Is a stanza directive, but not the title
                    $line = array_shift($fileArray);
                    $lineNumber++;
                    $entry['value'][] = (preg_match('/#/', $matches[1]) ? '#' : '' ) . $matches[2] . ' ' . $matches[3];
                }
            }

            if (empty($entry['name'])) {
                $entry['name'] = '** UNKNOWN DATABASE **';
            }
        } elseif (preg_match("/^\s*(" . $nonStanzaDirectives . ")\b\s*(.*)$/i", $line, $matches)) {
            // A non-stanza directive, active
            $entry['type'] = 'directive';
            $entry['name'] = $matches[1];
            $entry['value'][] = $matches[2];

            // Continue to read any subsequent matching non-stanza directives
            while ((! empty($fileArray)) && preg_match("/^\s*" . $entry['name'] . "\s+(.*)/i", $fileArray[0], $matches)) {
                $line = array_shift($fileArray);
                $lineNumber++;
                $entry['value'][] = $matches[1];
            }
        } elseif (preg_match("/^\s*#+[\s#]*(" . $nonStanzaDirectives . ")\b\s*(.*)$/i", $line, $matches)) {
            // A non-stanza directive, inactive
            $entry['type'] = 'directive';
            $entry['name'] = $matches[1];
            $entry['value'][] = $matches[2];
            $entry['active'] = false;

            // Continue to read any subsequent matching non-stanza directives
            while ((! empty($fileArray)) && preg_match("/^\s*#+[\s#]*" . $entry['name'] . "\s*(.*)$/i", $fileArray[0], $matches)) {
                $line = array_shift($fileArray);
                $lineNumber++;
                $entry['value'][] = $matches[1];
            }
        } elseif (preg_match("/^\s*#(.*)$/", $line, $matches)) {
            // Anything not matched already, that starts with a "#" is a comment
            $entry['type'] = 'comment';
            $entry['value'][] = empty($matches[1]) ? '' : $matches[1];

            // Continue to read any subsequent comments
            while ((! empty($fileArray)) && preg_match("/^\s*#(.*)$/", $fileArray[0], $matches)) {
                $line = array_shift($fileArray);
                $lineNumber++;
                $entry['value'][] = empty($matches[1]) ? '' : $matches[1];
            }
        } else {
            $entry['type'] = 'unknown';
            $entry['warnings'][] = ': Failed to properly parse line '  . $lineNumber . ': ' . $line;
        }

        return $entry;
    }
}
