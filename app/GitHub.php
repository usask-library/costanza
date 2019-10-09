<?php


namespace App;

use GuzzleHttp\Client;

class GitHub
{
    const STANZA_REPO_URI = 'https://api.github.com/repos/usask-library/ezproxy-stanzas';
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
    public static function get($repositoryFile = 'stanza_list.json')
    {
        $client = new Client();

        $response = $client->request('GET', self::STANZA_REPO_URI . '/contents/' . $repositoryFile);
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

    /**
     * Get a list of recent updates to the ezproxy-stanzas repo
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function recentStanzaUpdates()
    {
        $feed = [];

        $client = new Client();
        $response = $client->request('GET', self::STANZA_REPO_URI . '/commits');
        if ($response->getStatusCode() != '200') {
            return $feed;
        }
        $commits = json_decode($response->getBody());
        foreach ($commits as $commit) {
            $title = trim(array_slice(preg_split("/[\r\n]+/", $commit->commit->message), 0, 1)[0]);
            if (preg_match("/^(Add|Update).*\sstanza/i", $title)) {
                $feed[] = [
                    'title' => $title,
                    'date' => date("Y-m-d", strtotime($commit->commit->author->date)),
                    'url' => $commit->html_url,
                ];
            }
        }
        return $feed;
    }
}
