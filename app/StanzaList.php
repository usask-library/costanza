<?php


namespace App;

use GuzzleHttp\Client;
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
        if (env('GITHUB_STANZAS', false)) {
            // Fetch the stanza list file from the GitHub repo
            $stanzaList = self::getGitHubFileContents();
            if (empty($stanzaList)) {
                return false;
            }
        } else {
            // Verify the stanza list file exists locally
            if (! Storage::disk('stanzas')->exists('stanza_list.json')) {
                return false;
            }

            // Read the entire (JSON formatted) stanza list into an array
            $stanzaList = json_decode(Storage::disk('stanzas')->get('stanza_list.json'));
        }

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
        $stanzaList = self::getAll();

        // Check for a match
        $matchingStanza = false;
        if (! empty($stanzaList->{$stanzaCode})) {
            $matchingStanza = $stanzaList->{$stanzaCode};
            $matchingStanza->code = $stanzaCode;
        }

        // Return either a FALSE value, or the matching stanza if it exists
        return $matchingStanza;
    }


    /**
     * Get the contents of a single stanza as an array.
     *
     * @param $stanzaCode
     * @return array|false
     */
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

        if (env('GITHUB_STANZAS', true)) {
            $stanzaDirectives = self::getGitHubFileContents($stanza->stanza);
        } else {
            $stanzaDirectives = Storage::disk('stanzas')->get($stanza->stanza);
        }

        if (empty($stanzaDirectives)) {
            return false;
        }
        return preg_split("/[\r\n]+/", $stanzaDirectives);
    }


    /**
     * Fetch a file from ezproxy-stanzas repo.
     *
     * If no filename is specified, return the JSON encoded list of known stanzas (stanza_list.json).
     * If a specific file is requested, return the raw (usually plain text) contents of that file.
     *
     * @param string $repositoryFile
     * @return mixed|string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private static function getGitHubFileContents($repositoryFile = 'stanza_list.json')
    {
        $client = new Client();

        $response = $client->request('GET', 'https://api.github.com/repos/usask-library/ezproxy-stanzas/contents/' . $repositoryFile);
        if ($response->getStatusCode() != '200') {
            return null;
        }
        $body = json_decode($response->getBody());
        if (empty($body->size) || empty($body->content)) {
            return null;
        }

        // Return JSON data for files ending in '.json' and the raw data for everything else
        if (preg_match("/\.json$/", $body->name)) {
            return json_decode(base64_decode($body->content));
        } else {
            return base64_decode($body->content);
        }
    }
}
