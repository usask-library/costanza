<?php


namespace App;

use Illuminate\Support\Facades\Storage;

class StanzaList
{
    /**
     * Return a list of all the known stanzas
     *
     * @return bool|object
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function getAll()
    {
        // Verify the stanza list file exists
        if (! Storage::disk('stanzas')->exists('stanza_list.json')) {
            return false;
        }

        // Read the entire (JSON formatted) stanza list into an array
        $stanzaList = json_decode(Storage::disk('stanzas')->get('stanza_list.json'));
        foreach ($stanzaList as $code => $stanza) {
            // Ensure the "code" value also appears inside each stanza's data element
            $stanzaList->{$code}->code = $code;
        }

        // Return the content of the stanza list
        return $stanzaList;
    }

    /**
     * Return a single matching stanza from the stanza list
     *
     * @param $stanzaCode
     * @return bool|object
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function get($stanzaCode)
    {
        // Verify the stanza list file exists
        if (! Storage::disk('stanzas')->exists('stanza_list.json')) {
            return false;
        }

        // Read the entire (JSON formatted) stanza list into an array
        $stanzaList = json_decode(Storage::disk('stanzas')->get('stanza_list.json'));

        // Check for a match
        $matchingStanza = false;
        if (! empty($stanzaList->{$stanzaCode})) {
            $matchingStanza = $stanzaList->{$stanzaCode};
            $matchingStanza->code = $stanzaCode;
        }

        // Return either a FALSE value, or the matching stanza if it exists
        return $matchingStanza;
    }

    public static function getStanza($stanzaCode)
    {
        // Get the stanza details for the specified stanza
        $stanza = self::get($stanzaCode);
        if (empty($stanza)) {
            return false;
        }

        // The 'stanza' field should list the path to actual file
        if (! isset($stanza->stanza)) {
            return false;
        }

        $stanzaDirectives = Storage::disk('stanzas')->get($stanza->stanza);
        if (empty($stanzaDirectives)) {
            return false;
        }
        return preg_split("/[\r\n]+/", $stanzaDirectives);

    }
}
