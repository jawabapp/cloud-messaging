<?php

namespace Jawabapp\CloudMessaging\Notifications;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Jawabapp\CloudMessaging\Jobs\SendNotificationJob;
use Jawabapp\CloudMessaging\Models\Notification;

class FcmNotification
{

    public static function sendMessage($message, Collection $fcmTokens, $type = null, $sender = null, Notification $notification = null): void
    {

        $tokens = $fcmTokens->pluck('fcm_token');

        if (config('cloud-messaging.test.types')) {

            $testTypes = config('cloud-messaging.test.types', []);
            $testTokens = config('cloud-messaging.test.tokens', []);

            if (in_array($type, $testTypes)) {
                $tokens = $tokens->intersect($testTokens);
            }
        }

        // remove empty and duplicate
        $tokens = $tokens->filter(function ($value) { return !is_null($value); })->unique()->values();

        if($tokens) {
            $sent_at = now()->toDateTimeString();

            if($tokens->count() === 1) {
                SendNotificationJob::publish($message, $tokens->all(), $sent_at, $type, $sender, $notification);
            } else {
                foreach ($tokens->chunk(50) as $chunk) {
                    SendNotificationJob::dispatch($message, $chunk->all(), $sent_at, $type, $sender, $notification)->onQueue('cloud-message');
                }
            }
        }
    }

    public static function sendNotification(Notification $notification, $message, $wheres = []) :void
    {
        if($notifiable_model = config('cloud-messaging.notifiable_model')) {
            try {
                $sender = $notification->id ?? 0;
                $target = $notification->target ?? [];

                $callable = function ($userTokens) use ($message, $sender, $notification) {
                    self::sendMessage($message, $userTokens, 'cloud-message', $sender, $notification);
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
    }
}
