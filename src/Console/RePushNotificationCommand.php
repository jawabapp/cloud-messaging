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
        if(config('cloud-messaging.notifiable_model')) {
            $notification = Notification::find($this->argument('id'));

            if($notification && $notification->status !== 'completed') {

                $message = [
                    'image' => $notification->image,
                    'body' => $notification->text,
                    'title' => $notification->title,
                    'data' => $notification->extra_info,
                    'notification_id' => $notification->id,
                ];

                FcmNotification::sendNotification($notification, $message);

                $notification->update([
                    'status' => 'completed'
                ]);

            }
        }
    }
}
