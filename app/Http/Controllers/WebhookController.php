<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Creates the endpoint for our webhook
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function endpoint(Request $request)
    {
        $body = $request->all();

        // Checks this is an event from a page subscription
        if ($body['object'] === 'page') {
            // Iterates over each entry - there may be multiple if batched
            foreach ($body['entry'] as $entry) {
                // Gets the message. entry.messaging is an array, but
                // will only ever contain one message, so we get index 0
                $webhook_event = $entry['messaging'][0];
            }

            // Returns a '200 OK' response to all requests
            return response()->json('EVENT_RECEIVED', 200);
        } else {
            // Returns a '404 Not Found' if event is not from a page subscription
            return response()->json([], 404);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verification(Request $request)
    {
        // Your verify token. Should be a random string.
        $VERIFY_TOKEN = "duc";

        $input = $request->all();

        // Parse the query params
        $mode = $input['hub_mode'];
        $token = $input['hub_verify_token'];
        $challenge = $input['hub_challenge'];

        return response()->json($challenge, 200);

        // Checks if a token and mode is in the query string of the request
        if ($mode && $token) {

            // Checks the mode and token sent is correct
            if ($mode === 'subscribe' && $token === $VERIFY_TOKEN) {

                // Responds with the challenge token from the request
                return response()->json($challenge, 200);

            } else {
                // Responds with '403 Forbidden' if verify tokens do not match
                return response()->json([], 403);
            }
        }
    }
}
