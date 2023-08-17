<?php

namespace Jawabapp\CloudMessaging\Notifications;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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

        $api_response = [
            "multicast_id" => null,
            "success" => 0,
            "failure" => 0,
            "canonical_ids" => 0,
            "results" => []
        ];

        foreach ($tokens as $token) {
            try {
                $firebase_project_id = env('FIREBASE_PROJECT_ID');

                $body = $this->prepareBody($message, $token);

                $response = $this->client->post("https://fcm.googleapis.com/v1/projects/{$firebase_project_id}/messages:send", [
                        'headers' => [
                            'Authorization' => 'Bearer ' . env('FIREBASE_BEARER_TOKEN'),
                            'Content-Type'  => 'application/json'
                        ],
                        'body' => json_encode($body)
                    ]
                );

                $res = json_decode($response->getBody(), true);

                $api_response['success'] += 1;
                $api_response['results'][] = [
                    "message_id" => str_replace("projects/{$firebase_project_id}/messages/", '', $res['name'] ?? ''),
                    "analytics_label" => $body['message']['fcm_options']['analytics_label'] ?? null
                ];
            } catch (ClientException $e) {

                //to handel 401 UNAUTHENTICATED

                $res = json_decode($e->getResponse()->getBody(), true);

                $api_response['failure'] += 1;
                $api_response['results'][] = [
                    "error" => $res['error']['details'][0]['errorCode'] ?? $res['error']['status'] ?? 'UNKNOWN'
                ];
            }
        }

        return $api_response;
    }

    private function prepareBody(array $message, string $token)
    {
        $payload['message'] = [
            "token" => $token,
            "fcm_options" => [
                "analytics_label" => uniqid()
            ]
        ];

        if (!(isset($message['content_available']) && $message['content_available'])) {
            $payload['message']['notification'] = [
                'title' => $message['notification']['title'] ?? $message['title'] ?? null,
                'body' => $message['notification']['body'] ?? $message['body'] ?? null,
                'image' => $message['notification']['image'] ?? $message['image'] ?? null,
            ];
        }

        if (!empty($message['data'])) {
            $payload['message']['data'] = $message['data'];
        }

        if (!empty($message['notification_id'])) {
            $payload['message']['data']['notification_id'] = $message['notification_id'];
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
            $sent_at = now()->toDateTimeString();

            foreach ($tokens->chunk(500) as $chunkId => $chunk) {
                $fcm_tokens = $chunk->all();
                $response[$chunkId]['sent_at'] = $sent_at;
                $response[$chunkId]['fcm_tokens'] = $fcm_tokens;
                $response[$chunkId]['api_response'] = $client->send($message, $fcm_tokens);
            }

            FCMNotificationSent::dispatch($message, $response, $type, $sender);
        }

        return $response;

    }

    public static function sendNotification(Notification $notification, $message, $wheres = []) :array
    {

        $success = 0;
        $failure = 0;

        if($notifiable_model = config('cloud-messaging.notifiable_model')) {
            try {
                $sender = $notification->id ?? 0;
                $target = $notification->target ?? [];

                $callable = function ($userTokens) use ($message, $sender, &$success, &$failure) {
                    $response = self::sendMessage($message, $userTokens, 'cloud-message', $sender);

                    $success += intval($response[0]['api_response']['success'] ?? 0);
                    $failure += intval($response[0]['api_response']['failure'] ?? 0);
                };

                $query = $notifiable_model::getJawabTargetAudience($target, false, true);

                if ($wheres) {
                    foreach ($wheres as $where_column => $where_value) {
                        if($where_column && $where_value) {
                            $query->where($where_column, $where_value);
                        }
                    }
                }

                if(isset($target['limit']) && intval($target['limit']) > 0) {
                    $query->get()->chunk(500)->each($callable);
                } else {
                    $query->chunk(500, $callable);
                }

            } catch (\Exception $exception) {
                Log::error("[PushNotificationJob] send-notification " . $exception->getMessage());
            }
        }

        return [
            'success' => $success,
            'failure' => $failure,
        ];
    }
}
