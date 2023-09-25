<?php

namespace Jawabapp\CloudMessaging\Jobs;

use Google_Client;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Jawabapp\CloudMessaging\Events\FCMNotificationSent;
use Jawabapp\CloudMessaging\Models\Notification;
use Jawabapp\CloudMessaging\Traits\HasCloudMessagingQueue;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 0;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 0;

    private $message;
    private $tokens;
    private $sent_at;
    private $type;
    private $sender;
    private $notification;

    public function __construct($message, array $tokens, $sent_at, $type = null, $sender = null, Notification $notification = null)
    {
        $this->message = $message;
        $this->tokens = $tokens;
        $this->sent_at = $sent_at;
        $this->type = $type;
        $this->sender = $sender;
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = [
            'sent_at' => $this->sent_at,
            'fcm_tokens' => $this->tokens,
            'api_response' => $this->send($this->message, $this->tokens)
        ];

        if($this->notification) {
            $this->notification->update([
                'response' => [
                    'success' => ($this->notification->response['success'] ?? 0) + ($response['api_response']['success'] ?? 0),
                    'failure' => ($this->notification->response['failure'] ?? 0) + ($response['api_response']['failure'] ?? 0),
                ],
            ]);
        }

        FCMNotificationSent::dispatch($this->message, [$response], $this->type, $this->sender);
    }

    public static function publish($message, array $tokens, $sent_at, $type = null, $sender = null, Notification $notification = null) {
        (new self($message, $tokens, $sent_at, $type, $sender, $notification))->handle();
    }

    private function getFirebaseAuth() {

        $client = new Google_Client();

        $key = 'cloud-massaging:firebase-messaging-auth';

        if (!cache()->has($key)) {
            $authConfigPath = storage_path(env('FIREBASE_APPLICATION_CREDENTIALS'));

            if(file_exists($authConfigPath)) {
                $config = json_decode(file_get_contents($authConfigPath),true);

                $client->setAuthConfig($authConfigPath);
                $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);

                $token = $client->fetchAccessTokenWithAssertion();

                $auth = [
                    'project_id' => $config['project_id'],
                    'token' => $token
                ];

                cache()->put($key, $auth, $token['expires_in']);
            } else {
                die('Error no FIREBASE_APPLICATION_CREDENTIALS in the .env file');
            }
        } else {
            $auth = cache()->get($key);

            $client->setAccessToken($auth['token']);

            if($client->isAccessTokenExpired()) {
                cache()->forget($key);
                return $this->getFirebaseAuth();
            }
        }

        return $auth;

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

        $client = new Client();

        foreach ($tokens as $token) {
            try {
                $auth = $this->getFirebaseAuth();

                $body = $this->prepareBody($message, $token);

                $response = $client->post("https://fcm.googleapis.com/v1/projects/{$auth['project_id']}/messages:send", [
                        'headers' => [
                            'Authorization' => "Bearer {$auth['token']['access_token']}",
                            'Content-Type'  => 'application/json'
                        ],
                        'body' => json_encode($body)
                    ]
                );

                $res = json_decode($response->getBody(), true);

                $api_response['success'] += 1;
                $api_response['results'][] = [
                    "message_id" => str_replace("projects/{$auth['project_id']}/messages/", '', $res['name'] ?? ''),
                    "analytics_label" => $body['message']['fcm_options']['analytics_label'] ?? null
                ];
            } catch (ClientException $e) {
                $res = json_decode($e->getResponse()->getBody(), true);

                $api_response['failure'] += 1;
                $api_response['results'][] = [
                    "error" => $res['error']['details'][0]['errorCode'] ?? $res['error']['status'] ?? 'UNKNOWN'
                ];
            }
        }

        return $api_response;
    }

    private function prepareBody(array $message, string $token): array
    {
        $payload['message'] = [
            "token" => $token,
            "fcm_options" => [
                "analytics_label" => (isset($message['analytics_label_prefix']) ? "{$message['analytics_label_prefix']}-" : "") . uniqid()
            ]
        ];

        if (!(isset($message['content_available']) && $message['content_available'] == true)) {
            $payload['message']['notification'] = [
                'title' => $message['notification']['title'] ?? $message['title'] ?? null,
                'body' => $message['notification']['body'] ?? $message['body'] ?? null,
                'image' => $message['notification']['image'] ?? $message['image'] ?? null,
            ];

            $payload['message']['apns']['payload']['aps']['badge'] = $message['badge'] ?? 1;
            $payload['message']['apns']['payload']['aps']['sound'] = 'default';
            $payload['message']['apns']['payload']['aps']['mutable-content'] = 1;
        }

        if (!empty($message['data'])) {
            foreach ($message['data'] as $key => $value) {
                if($value) {
                    $payload['message']['data'][$key] = (string) $value;
                }
            }
        }

        if (!empty($message['notification_id'])) {
            $payload['message']['data']['notification_id'] = (string) $message['notification_id'];
        }

        return $payload;
    }

}
