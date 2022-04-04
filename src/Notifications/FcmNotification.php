<?php

namespace Jawabapp\CloudMessaging\Notifications;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Jawabapp\CloudMessaging\Events\FCMNotificationSent;

class FcmNotification
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    private function send($message, array $tokens)
    {
        if (!$tokens) {
            return 'Fcm_Notification (No Tokens)';
        }

        try {
            $body = array_merge($message, ['registration_ids' => $tokens]);

            $response = $this->client->request('POST', 'https://fcm.googleapis.com/fcm/send', [
                    'headers' => [
                        'Authorization' => 'key=' . env('FIREBASE_SERVER_KEY'),
                        'Content-Type'  => 'application/json'
                    ],
                    'body' => json_encode($body)
                ]
            );
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return 'Fcm_Notification (' . $e->getMessage() . ')';
        }
    }

    public static function prepare(array $payload, $asData = false, $silent = false)
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

        if (!empty($payload['data']) && is_array($payload['data'])) {
            $rawMessage['data'] = $payload['data'];
            unset($payload['data']);
        }

        if (isset($payload['notification_id'])) {
            $rawMessage['data']['notification_id'] = $payload['notification_id'];
            unset($payload['notification_id']);
        }

        if ($asData) {
            $rawMessage['data']['payload'] = json_encode($payload);
        } else {
            $rawMessage['notification'] = $payload;
        }

        return $rawMessage;
    }

    public static function sendMessage($message, Collection $dataTokens, $type = null, $sender = null): array
    {

        $tokens = $dataTokens->pluck('fcm_token');

        if (config('cloud-messaging.test.types')) {

            $testTypes = config('cloud-messaging.test.types', []);
            $testTokens = config('cloud-messaging.test.tokens', []);

            if (in_array($type, $testTypes)) {
                $tokens = $tokens->intersect($testTokens);
            }
        }

        $response = [];

        // remove empty and duplicate
        $tokens = $tokens->filter(function ($value) { return !is_null($value); })->unique()->values();
        if($tokens) {

            $client = (new self);

            foreach ($tokens->chunk(500) as $chunkId => $chunk) {
                $fcm_tokens = $chunk->all();
                $response[$chunkId]['sent_at'] = now()->toDateTimeString();
                $response[$chunkId]['fcm_tokens'] = $fcm_tokens;
                $response[$chunkId]['api_response'] = $client->send($message, $fcm_tokens);
            }

            FCMNotificationSent::dispatch($message, $response, $type, $sender);
        }
        return $response;

    }
}
