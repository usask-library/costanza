<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewEntryRequest;
use App\Http\Requests\UpdateEntryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EntryController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $filename
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function show(Request $request, $filename, $id)
    {
        return response()->json(['status' => 'success', 'message' => 'Entry ' . $id . ' returned in data'], 200);
    }


    /**
     * Add a new entry to the specified EZproxy config file after the given entry.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $filename
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function store(NewEntryRequest $request, $filename)
    {
        //
        // Generate a new (array/JSON) entry to insert into the file
        //
        // Each entry needs a unique ID
        $newEntry['id'] = (string) Str::uuid();
        // The type of entry to be added comes in as a hidden form element
        $newEntry['type'] = $request->get('type');

        switch ($newEntry['type']) {
            case 'comment':
                $newEntry['value'] = preg_split('/\R/', $request->get('comment_value'));
                break;
            case 'directive':
                $comment = $request->get('directive_comment');
                $newEntry['name'] = $request->get('directive_name');
                $newEntry['value'] = preg_split('/\R/', $request->get('directive_value'));
                if (! empty($comment)) {
                    $newEntry['comment'] = preg_split('/\R/', $comment);
                }
                $newEntry['active'] = ! $request->get('directive_inactive');
                break;
            case 'group':
                $comment = $request->get('group_comment');
                $newEntry['name'] = $request->get('group_name');
                if (! empty($comment)) {
                    $newEntry['comment'] = preg_split('/\R/', $comment);
                }
                $newEntry['active'] = ! $request->get('group_inactive');
                break;
            case 'stanza':
                // For a predefined stanza, stanza_id is the unique code for the database in the complete stanza list
                // A copy of that database stanza is needed, so it can be saved to the users config file

                // Get the entire (JSON formatted) stanza list
                $stanzaList = json_decode(Storage::disk('stanzas')->get('stanza_list.json'), true);

                // Search the list of stanzas to find the specified one
                $matchingStanza = null;
                foreach ($stanzaList as $code => $stanza) {
                    if ($code == $request->get('stanza_id')) {
                        $matchingStanza = $stanza;
                        break;
                    }
                }
                // If no matching stanza was found, return an error message
                if (empty($matchingStanza)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No matching stanza was found with ID ' . $request->get('stanza_id')
                    ], 400);
                }

                // Ensure the Costanza file entry has the necessary fields from the matching stanza entry
                $newEntry['code'] = $request->get('stanza_id');
                $newEntry['name'] = $matchingStanza['name'];
                if (! empty($matchingStanza['rules'])) {
                    $newEntry['rules'] = $matchingStanza['rules'];
                }

                // Add additional fields from the form
                $comment = $request->get('stanza_comment');
                $display_name = $request->get('stanza_name');
                $newEntry['code'] = $request->get('stanza_id');
                if (! empty($display_name)) {
                    $newEntry['display_name'] = $display_name;
                }
                if (! empty($comment)) {
                    $newEntry['comment'] = preg_split('/\R/', $comment);
                }
                $newEntry['active'] = ! $request->get('stanza_inactive');
                break;
            case 'custom_stanza':
                $comment = $request->get('custom_comment');
                $newEntry['name'] = $request->get('custom_name');
                $newEntry['value'] = preg_split('/\R/', $request->get('custom_value'));
                if (! empty($comment)) {
                    $newEntry['comment'] = preg_split('/\R/', $comment);
                }
                $newEntry['active'] = ! $request->get('custom_inactive');
                break;
        }

        // Read the entire contents of the (JSON formatted) file
        $EZproxyConfig = json_decode(Storage::disk('users')->get(Auth::user()->institution_code . '/' .  $filename));

        // Place the entry at it's new location
        if ($request->get('placeAfter') == 'top') {
            // The entry is being added to the top of the file; push it onto the front of the list of entries
            if (empty($newEZproxyConfig)) {
                $newEZproxyConfig[] = $newEntry;
            } else {
                array_unshift($newEZproxyConfig, $newEntry);
            }
        } else {
            // Find the location to insert this entry
            $newEZproxyConfig = [];

            // Process the entire list of entries, looking for the ID of the entry after which the new one should be added
            $foundLocation = false;
            foreach ($EZproxyConfig as $stanza) {
                // Save the current entry in the new list
                $newEZproxyConfig[] = $stanza;
                if ($stanza->id == $request->get('placeAfter')) {
                    // Found the target entry; add the specified entry to the list at this location
                    $newEZproxyConfig[] = $newEntry;
                    $foundLocation = true;
                }
            }

            // If the target entry was not found in the file, return an error
            if (! $foundLocation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The location to insert this entry could not be determined',
                    'data'    => 'The entry to insert after (' . $request->get('placeAfter') . ') could not be found in the file ' . $filename,
                ], 400);
            }
        }

        // The newEZproxyConfig array contains the new list of entries, with the new entry in its correct location
        // Write this new list out to the disk
        Storage::disk('users')->put(Auth::user()->institution_code . '/' .  $filename, json_encode($newEZproxyConfig, JSON_PRETTY_PRINT));

        return response()->json(['status' => 'success', 'message' => 'Entry was successfully created', 'data' => $newEntry], 200);
    }


    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $filename
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function update(UpdateEntryRequest $request, $filename, $id)
    {
        // Read the entire contents of the (JSON formatted) file
        $EZproxyConfig = json_decode(Storage::disk('users')->get(Auth::user()->institution_code . '/' .  $filename), true);

        // Find the location to insert this entry
        $newEZproxyConfig = [];

        // Process the entire list of entries, looking for the ID of the entry after which the new one should be added
        $matchingStanza = null;
        foreach ($EZproxyConfig as $stanza) {
            if ($stanza['id'] == $id) {
                // Found the target entry

                // Update the entry using the data from the form
                switch ($request->get('type')) {
                    case 'comment':
                        $stanza['value'] = preg_split('/\R/', $request->get('comment_value'));
                        break;
                    case 'directive':
                        $comment = $request->get('directive_comment');
                        $stanza['name'] = $request->get('directive_name');
                        $stanza['value'] = preg_split('/\R/', $request->get('directive_value'));
                        if (empty($comment)) {
                            unset($stanza['comment']);
                        } else {
                            $stanza['comment'] = preg_split('/\R/', $comment);
                        }
                        $stanza['active'] = ($request->get('directive_inactive') !== true);
                        break;
                    case 'group':
                        $comment = $request->get('group_comment');
                        $stanza['name'] = $request->get('group_name');
                        if (empty($comment)) {
                            unset($stanza['comment']);
                        } else {
                            $stanza['comment'] = preg_split('/\R/', $comment);
                        }
                        $stanza['active'] = ($request->get('group_inactive') !== true);
                        break;
                    case 'stanza':
                        $comment = $request->get('stanza_comment');
                        $stanza['name'] = $request->get('stanza_name');
                        if (empty($comment)) {
                            unset($stanza['comment']);
                        } else {
                            $stanza['comment'] = preg_split('/\R/', $comment);
                        }
                        $display_name = $request->get('stanza_name');
                        if (empty($display_name)) {
                            unset($stanza['display_name']);
                        } else {
                            $stanza['display_name'] = $display_name;
                        }
                        $stanza['active'] = ($request->get('stanza_inactive') !== true);
                        break;
                    case 'custom_stanza':
                        $comment = $request->get('custom_comment');
                        $stanza['name'] = $request->get('custom_name');
                        $stanza['value'] = preg_split('/\R/', $request->get('custom_value'));
                        if (empty($comment)) {
                            unset($stanza['comment']);
                        } else {
                            $stanza['comment'] = preg_split('/\R/', $comment);
                        }
                        $stanza['active'] = ($request->get('custom_inactive') !== true);
                        break;
                }
                $matchingStanza = $stanza;
            }
            $newEZproxyConfig[] = $stanza;
        }

        // If the target entry was not found in the file, return an error
        if (empty($matchingStanza)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The entry to update could not be found',
                'data'    => 'The entry to update (' . $id . ') could not be found in the file ' . $filename,
            ], 400);
        }

        // The newEZproxyConfig array contains the new list of entries, with the new entry in its correct location
        // Write this new list out to the disk
        Storage::disk('users')->put(Auth::user()->institution_code . '/' .  $filename, json_encode($newEZproxyConfig,JSON_PRETTY_PRINT));

        return response()->json(['status' => 'success', 'message' => 'Entry was successfully updated', 'data' => $matchingStanza], 200);
    }


    /**
     * Delete a single entry from a Costanza file.
     *
     * @param string $filename
     * @param $id
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function destroy($filename, $id)
    {
        // Verify the specified file exists in the user's storage folder
        if (! Storage::disk('users')->exists(Auth::user()->institution_code . '/' .  $filename)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Costanza could not find the specified file'
            ], 400);
        }

        // Read the entire contents of the (JSON formatted) file
        $EZproxyConfig = json_decode(Storage::disk('users')->get(Auth::user()->institution_code . '/' .  $filename));

        // Examine each entry in the file, looking for the specified entry to delete
        // Keep track of all the non-matching entries in a new array
        $matchingDirective = null;
        $newEZproxyConfig = [];
        foreach ($EZproxyConfig as $stanza) {
            if ($stanza->id == $id) {
                // Set a flag indicating the matching stanza was found
                $matchingDirective = $stanza;
            } else {
                // Current stanza does NOT match the stanza to delete; keep it
                $newEZproxyConfig[] = $stanza;
            }
        }

        // If the specified entry to delete was not found in the file, return an error
        if (empty($matchingDirective)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to find a match in the EZproxy file'
            ], 400);
        }

        // The newEZproxyConfig array contains all non-matching entries
        // Write this new list out to the disk (essentially deleting the specified entry)
        Storage::disk('users')->put(Auth::user()->institution_code . '/' .  $filename, json_encode($newEZproxyConfig, JSON_PRETTY_PRINT));

        // Return a success message
        return response()->json([
            'status' => 'success',
            'message' => 'The entry was successfully deleted',
            'data' => 'Entry ' . $id . ' was successfully deleted from file ' . $filename,
        ], 200);
    }


    /**
     * Move an entry from one location in a file to a new location
     *
     * @param \Illuminate\Http\Request $request
     * @param string $filename
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function move(Request $request, $filename, $id)
    {
        // Ensure the ID of the target entry is either "top" or a UUID value.
        if ($request->get('placeAfter') == 'top') {
            $validatedData['placeAfter'] = 'top';
        } else {
            $validatedData = $request->validate([
                'placeAfter' => 'required|uuid',
            ]);
        }

        // Read the entire contents of the (JSON formatted) file
        $EZproxyConfig = json_decode(Storage::disk('users')->get(Auth::user()->institution_code . '/' .  $filename));

        // Find the requested entry to move, save that entry, then remove it from the list
        $matchingDirective = null;
        $newEZproxyConfig = [];
        foreach ($EZproxyConfig as $stanza) {
            if ($stanza->id == $id) {
                // The specified entry was found; keep it's details but don't add it to the list of current entries
                $matchingDirective = $stanza;
            } else {
                // This entry does NOT match the specified one to move; add it to the list of current entries
                $newEZproxyConfig[] = $stanza;
            }
        }

        // If the specified entry was not actually in the file, then it can't be moved to a new location
        if (empty($matchingDirective)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The specified entry could not be found in the EZproxy config file',
                'data'    => 'Entry ' . $id . ' could not be found in the file ' . $filename,
            ], 400);
        }

        // Place the entry at it's new location
        if ($validatedData['placeAfter'] == 'top') {
            // The entry is being added to the top of the file; push it onto the front of the list of entries
            array_unshift($newEZproxyConfig, $matchingDirective);
        } else {
            // Find the location to insert this entry
            $EZproxyConfig = $newEZproxyConfig;
            $newEZproxyConfig = [];

            // Process the entire list of entries, looking for the ID of the entry after which the specified one should be added
            $foundLocation = false;
            foreach ($EZproxyConfig as $stanza) {
                // Save the current entry in the new list
                $newEZproxyConfig[] = $stanza;
                if ($stanza->id == $validatedData['placeAfter']) {
                    // Found the target entry; add the specified entry to the list at this location
                    $newEZproxyConfig[] = $matchingDirective;
                    $foundLocation = true;
                }
            }

            // If the target entry was not found in the file, return an error
            if (! $foundLocation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The location to insert this entry could not be determined',
                    'data'    => 'The entry to insert ' . $id . ' after (' . $validatedData['placeAfter'] . ') could not be found in the file ' . $filename,

                ], 400);
            }
        }

        // The newEZproxyConfig array contains the new list of entries, with the specified entry in its new location
        // Write this new list out to the disk
        Storage::disk('users')->put(Auth::user()->institution_code . '/' .  $filename, json_encode($newEZproxyConfig, JSON_PRETTY_PRINT));

        // Return a success message
        return response()->json([
            'status' => 'success',
            'message' => 'The entry was successfully moved',
            'data'    => 'Entry ' . $id . ' was successfully placed after entry ' . $validatedData['placeAfter'] . ' in the file ' . $filename,
        ], 200);
    }
}
