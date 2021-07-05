<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client as Guzzle;
use Log;

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

                // Get the sender PSID
                $sender_psid = $webhook_event['sender']['id'];

                // Check if the event is a message or postback and
                // pass the event to the appropriate handler function
                Log::info('webhook_event', $webhook_event);
                if($webhook_event['message']) {
                    $this->handleMessage($sender_psid, $webhook_event['message']);
                } elseif ($webhook_event['postback']) {
                    $this->handlePostback($sender_psid, $webhook_event['postback']);
                }
            }

            // Returns a '200 OK' response to all requests
            return 'EVENT_RECEIVED';
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
        $VERIFY_TOKEN = env('VERIFY_TOKEN');

        $input = $request->all();

        // Parse the query params
        $mode = $input['hub_mode'];
        $token = $input['hub_verify_token'];
        $challenge = $input['hub_challenge'];

        // Checks if a token and mode is in the query string of the request
        if ($mode && $token) {

            // Checks the mode and token sent is correct
            if ($mode === 'subscribe' && $token === $VERIFY_TOKEN) {

                // Responds with the challenge token from the request
                return $challenge;
            } else {
                // Responds with '403 Forbidden' if verify tokens do not match
                return response()->json([], 403);
            }
        }
    }

    // Handles messages events
    private function handleMessage($sender_psid, $received_message) {
        $response = [];

        // Check if the message contains text

        if($received_message['text']) {
            $response['text'] = 'Type:text';
        } else {
            Log::info('<<prepare: response other>>');
            $response['text'] = 'Type:other';
        }
//        if ($received_message['text']) {
//            // Create the payload for a basic text message
//            $response['text'] = 'Bạn đã gửi tin nhắn: ' . $received_message['text'] . '. Now send me an image! PSID:' . $sender_psid;
//        } elseif ($received_message['attachments']) {
//            // Gets the URL of the message attachment
//            $attachment_url = $received_message['attachments'][0]['payload']['url'];
//
//            $response = [
//                'attachment' => [
//                    'type' => 'template',
//                    'payload' => [
//                        'template_type' => 'generic',
//                        'elements' => [
//                            [
//                                'title' => 'Is this the right picture?',
//                                'subtitle' => 'Tap a button to answer.',
//                                'image_url' => $attachment_url,
//                                'buttons' => [
//                                    [
//                                        'type' => 'postback',
//                                        'title' => 'Yes!',
//                                        'payload' => 'yes'
//                                    ],
//                                    [
//                                        'type' => 'postback',
//                                        'title' => 'No!',
//                                        'payload' => 'no'
//                                    ]
//                                ]
//                            ]
//                        ]
//                    ]
//                ]
//            ];
//        }

        // Sends the response message
        $this->callSendAPI($sender_psid, $response);
    }

    private function callSendAPI($sender_psid, $response) {
        $client = new Guzzle(['base_uri' => 'https://graph.facebook.com']);

        $result = $client->request(
            'POST',
            'v2.6/me/messages' . '?' . http_build_query([
                'access_token' => env('PAGE_ACCESS_TOKEN')
            ]),
            [
                'json' => [
                    'recipient' => [
                        'id' => $sender_psid
                    ],
                    'message' => $response
                ]
            ]
        );
    }

    // Handles messaging_postbacks events
    private function handlePostback($sender_psid, $received_postback) {

    }

    public function test() {
        Log::error('test');
        $response = [];
//        $response['text'] = 'You sent the message:1';

        $response = [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'generic',
                    'elements' => [
                        [
                            'title' => 'Is this the right picture?',
                            'subtitle' => 'Tap a button to answer.',
                            'image_url' => 'https://scontent.fhan2-4.fna.fbcdn.net/v/t1.6435-0/p526x296/207822890_4255032357909947_4756174985248883043_n.jpg?_nc_cat=103&ccb=1-3&_nc_sid=8bfeb9&_nc_ohc=NCinjImcrr4AX9PRhwr&_nc_ht=scontent.fhan2-4.fna&tp=6&oh=6471e467d06b36d139c992748267ffa9&oe=60E7B4D3',
                            'buttons' => [
                                [
                                    'type' => 'postback',
                                    'title' => 'Yes!',
                                    'payload' => 'yes'
                                ],
                                [
                                    'type' => 'postback',
                                    'title' => 'No!',
                                    'payload' => 'no'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $client = new Guzzle(['base_uri' => 'https://graph.facebook.com']);

        $result = $client->request(
            'POST',
            'v2.6/me/messages' . '?' . http_build_query([
                'access_token' => env('PAGE_ACCESS_TOKEN')
            ]),
            [
                'json' => [
                    'recipient' => [
                        'id' => 5640144536057383
                    ],
                    'message' => $response
                ]
            ]
        );;
    }
}
