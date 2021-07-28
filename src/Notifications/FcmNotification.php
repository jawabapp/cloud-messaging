<?php

namespace JawabApp\CloudMessaging\Notifications;

use Carbon\Carbon;
use GuzzleHttp\Client;

class FcmNotification
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public static function send($message, array $tokens)
    {
        if (!$tokens) {
            return 'Fcm_Notification (No Tokens)';
        }
        static $client;

        if (is_null($client)) {
            $client = (new self())->client;
        }

        try {
            $body = array_merge($message, ['registration_ids' => $tokens]);

            return $client->request(
                'POST',
                'https://fcm.googleapis.com/fcm/send',
                [
                    'headers' => [
                        'Authorization' => 'key=' . env('FIREBASE_SERVER_KEY'),
                        'Content-Type'  => 'application/json'
                    ],
                    'body' => json_encode($body)
                ]
            )->getBody();
        } catch (\Exception $e) {
            return 'Fcm_Notification (' . $e->getMessage() . ')';
        }
    }

    public static function prepare(array $payload, $asData = true, $silent = false)
    {

        $expiration = Carbon::today()->addDays(7);

        $rawMessage = [
            'android' => [
                'ttl' => '604800s',
                'priority' => 'high',
            ],
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                    'apns-expiration' => "{$expiration}"
                ],
                'payload' => [
                    'aps' => [
                        'mutable-content' => 1,
                    ],
                ],
            ],
        ];

        if ($silent) {
            $rawMessage['apns']['payload']['aps']['content-available'] = 1;
        } else {
            $rawMessage['apns']['payload']['aps']['sound'] = 'default';
            $rawMessage['apns']['payload']['aps']['badge'] = 1;
            if ($asData) {
                $rawMessage['apns']['payload']['aps']['alert'] = [
                    'title' => 'Jawab'
                ];
            }
        }

        if ($asData) {
            $rawMessage['data'] = ['payload' => json_encode($payload)];
        } else {
            $rawMessage['notification'] = $payload;
        }

        return $rawMessage;
    }
}
