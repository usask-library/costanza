<?php

namespace App\Http\Controllers;

use App\GitHub;
use App\StanzaList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StanzaListController extends Controller
{
    /**
     * Get the complete list of known EZproxy stanzas
     *
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function index()
    {
        // Get a list of all known stanzas
        $stanzaList = StanzaList::getAll();

        // Check that the list actually has entries
        if (empty($stanzaList)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Costanza could not find the list of EZproxy stanzas, or the list was empty'
            ], 400);
        }

        // Return the content of the stanza list
        return response()->json([
            'status' => 'success',
            'message' => 'The request for all known EZproxy stanzas was successful',
            'data' => $stanzaList
        ],200);
    }

    /**
     * Get a single EZproxy stanza from the list of known stanzas
     *
     * @param $stanzaCode
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function show($stanzaCode)
    {
        $matchingStanza = StanzaList::get($stanzaCode);

        // If no matching stanza was found, return an error message
        if (empty($matchingStanza)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No matching stanza was found with ID ' . $stanzaCode
            ], 400);
        }

        // If a matching stanza was found, return that stanza's data
        return response()->json([
            'status' => 'success',
            'message' => 'A matching EZproxy stanza was found for ' . $stanzaCode,
            'data' => $matchingStanza
        ],200);
    }

    /**
     * Get the contents of a single database stanza.
     *
     * @param $stanzaCode
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function contents($stanzaCode)
    {
        $stanzaDirectives = StanzaList::getStanza($stanzaCode);
        if (empty($stanzaDirectives)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No matching stanza was found with ID ' . $stanzaCode
            ], 400);
        }

        return response(implode("\n", $stanzaDirectives), 200)
            ->header('Content-Type', 'text/plain');
    }


    /**
     * Return a list of recent updates to the stanza repository
     *
     * @param int $count
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function updates($count = 10)
    {
        $feed = array_slice(GitHub::recentStanzaUpdates(), 0, $count);
        return $feed;
    }
}
