<?php

namespace JawabApp\CloudMessaging\Jobs;

use Log;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use JawabApp\CloudMessaging\Models\Notification;
use JawabApp\CloudMessaging\Notifications\FcmNotification;

class PushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $notification;
    private $payload;

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

    /**
     * Create a new job instance.
     *
     * @param Notification $notification
     * @param array $payload
     */
    public function __construct(Notification $notification, array $payload)
    {
        $this->notification = $notification;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        try {

            Log::info("send notification start");

            if (empty($this->notification)) {
                return;
            }

            $this->notification->update([
                'status' => 'processing'
            ]);

            $notifiable_model = config('jawab-fcm.notifiable_model');
            $users = $notifiable_model::getJawabTargetAudience($this->notification->target);

            $response = collect();

            $message = FcmNotification::prepare($this->payload, false);

            $users->chunk(500)->each(function ($chunked) use ($message, $response) {
                try {
                    $response->push(FcmNotification::send($message, $chunked->pluck('fcm_token')->all()));
                } catch (\Exception $exception) {
                    Log::error("send-notification " . $exception->getMessage());
                }
            });

            $this->notification->update([
                'response' => $response->all(),
                'status' => 'completed'
            ]);

            Log::info("send notification end");
        } catch (\Exception $exception) {
            \Log::info('Error: [PushNotificationJob] File: ' . $exception->getFile() . ' Line: ' . $exception->getLine() . ' Message: ' . $exception->getMessage());
        }
    }
}
