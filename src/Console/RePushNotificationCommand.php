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
                    $users->chunk(500, function ($chunked) use ($message, $response) {
                        $response->push(FcmNotification::send($message, $chunked->pluck('fcm_token')->all()));
                    });
                } catch (\Exception $exception) {
                    $this->error("[PushNotificationJob] send-notification " . $exception->getMessage());
                }

                $notification->update([
                    'response' => $response->all(),
                    'status' => 'completed'
                ]);
            }
        }
    }
}
