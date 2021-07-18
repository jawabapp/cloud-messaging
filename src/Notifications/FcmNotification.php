<?php

namespace JawabApp\CloudMessaging\Notifications;

use Carbon\Carbon;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

/*
 * https://github.com/kreait/firebase-php/blob/master/docs/cloud-messaging.rst
 */

class FcmNotification
{

    private $channel;

    public function __construct()
    {
        $firebase = (new Factory())->withServiceAccount(
            ServiceAccount::fromJsonFile(storage_path(env('FIREBASE_SERVER')))
        )->create();

        $this->channel = $firebase->getMessaging();
    }

    public static function send($message, array $tokens)
    {

        if (!$tokens) {
            return 'Fcm_Notification (No Tokens)';
        }

        static $channel;

        if (is_null($channel)) {
            $channel = (new self())->channel;
        }

        try {
            $report = $channel->sendMulticast($message, $tokens);

            return [
                'success' => $report->successes()->count(),
                'failure' => $report->failures()->count(),
                'invalidTokens' => $report->invalidTokens(),
                'unknownTokens' => $report->unknownTokens()
            ];
        } catch (\Exception $e) {
            return 'Fcm_Notification (' . $e->getMessage() . ')';
        }
    }

    public static function prepare(array $payload, $asData = true, $silent = false)
    {

        $expiration = Carbon::today()->addDays(7)->getPreciseTimestamp(0);

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
