<?php

namespace Jawabapp\CloudMessaging\Console;

use Illuminate\Console\Command;
use Jawabapp\CloudMessaging\Models\Notification;
use Jawabapp\CloudMessaging\Notifications\FcmNotification;

class RePushNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloud-messaging:re-push-notification {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 're push notification by id if any error';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if($notifiable_model = config('cloud-messaging.notifiable_model')) {
            $notification = Notification::find($this->argument('id'));

            if($notification && $notification->status !== 'completed') {

                $sender = $notification->id;

                $response = collect();

                $payload = [
                    'image' => $notification->image,
                    'body' => $notification->text,
                    'title' => $notification->title,
                    'data' => $notification->extra_info,
                ];

                $message = FcmNotification::prepare($payload);

                try {
                    $users = $notifiable_model::getJawabTargetAudience($notification->target, false, true);
                    $users->chunk(500, function ($chunked) use ($message, $response, $sender) {
                        $apiResponse = FcmNotification::sendMessage($message, $chunked, 'cloud-message', $sender);
                        $response->push($apiResponse[0]['api_response']);
                    });
                } catch (\Exception $exception) {
                    $this->error("[PushNotificationJob] send-notification " . $exception->getMessage());
                }

                $success = 0;
                $failure = 0;
                $response->each(function ($item) use (&$success, &$failure) {
                    $success += intval($item['success'] ?? 0);
                    $failure += intval($item['failure'] ?? 0);
                });

                $notification->update([
                    'response' => [
                        'success' => $success,
                        'failure' => $failure,
                    ],
                    'status' => 'completed'
                ]);

            }
        }
    }
}
