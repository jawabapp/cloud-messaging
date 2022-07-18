<?php

namespace Jawabapp\CloudMessaging\Notifications;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Jawabapp\CloudMessaging\Events\FCMNotificationSent;
use Jawabapp\CloudMessaging\Models\Notification;

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

        info('FcmNotification send title:' . $message['title'] . ' body:' . $message['body'] . ' tokens counts:' . count($tokens));

        try {
            $response = $this->client->request('POST', 'https://fcm.googleapis.com/fcm/send', [
                    'headers' => [
                        'Authorization' => 'key=' . env('FIREBASE_SERVER_KEY'),
                        'Content-Type'  => 'application/json'
                    ],
                    'body' => json_encode($this->prepareBody($message, $tokens))
                ]
            );
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return 'Fcm_Notification (' . $e->getMessage() . ')';
        }
    }

    private function prepareBody(array $message, array $tokens)
    {

        $payload = [
            'registration_ids' => $tokens,
            'priority' => $message['priority'] ?? 'high',
        ];

        if (isset($message['mutable_content'])) {
            $payload['mutable_content'] = $message['mutable_content'];
        } else {
            $payload['mutable_content'] = true;
        }

        if (isset($message['content_available']) && $message['content_available']) {
            $payload['content_available'] = true;
        } else {
            $payload['notification'] = [
                'title' => $message['notification']['title'] ?? $message['title'] ?? null,
                'body' => $message['notification']['body'] ?? $message['body'] ?? null,
                'image' => $message['notification']['image'] ?? $message['image'] ?? null,
                'badge' => $message['notification']['badge'] ?? $message['badge'] ?? 1,
                'sound' => 'default',
            ];
        }

        if (!empty($message['data'])) {
            $payload['data'] = $message['data'];
        }

        if (!empty($message['notification_id'])) {
            $payload['data']['notification_id'] = $message['notification_id'];
        }

        return $payload;
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

    public static function sendNotification(Notification $notification, Collection $response, $message, $wheres = []){
        if($notifiable_model = config('cloud-messaging.notifiable_model')) {
            try {
                $sender = $notification->id ?? 0;
                $target = $notification->target ?? [];
                $limit = intval($target['limit'] ?? 0);

                $users = $notifiable_model::getJawabTargetAudience($target, false, true);

                if ($wheres) {
                    foreach ($wheres as $where_column => $where_value) {
                        $users->where($where_column, $where_value);
                    }
                }

                if($limit > 0) {
                    foreach($users->get()->chunk(500) as $chunked) {
                        $apiResponse = self::sendMessage($message, $chunked, 'cloud-message', $sender);
                        $response->push($apiResponse[0]['api_response']);
                    }
                } else {
                    $users->chunk(500, function ($chunked) use ($message, $response, $sender) {
                        $apiResponse = self::sendMessage($message, $chunked, 'cloud-message', $sender);
                        $response->push($apiResponse[0]['api_response']);
                    });
                }
            } catch (\Exception $exception) {
                error("[PushNotificationJob] send-notification " . $exception->getMessage());
            }
        }
    }
}
