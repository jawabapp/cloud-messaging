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

class PushNotificationScheduledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $notification;
    private $payload;
    private $notifiable_model;
    private $country_code;

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
    public function __construct(Notification $notification, array $payload, $country_code = null)
    {
        $this->notification = $notification;
        $this->payload = $payload;
        $this->payload['notification_id'] = $notification->id;

        $this->country_code = $country_code;
        $this->notifiable_model = config('cloud-messaging.notifiable_model');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {

            Log::info("[PushNotificationScheduledJob] send notification start country_code:" . ($this->country_code ?? 'N/A'));

            $this->notification->update([
                'status' => 'processing'
            ]);

            $query = $this->notifiable_model::getJawabTargetAudience($this->notification->target, false, true);

            if ($this->country_code) {
                $query->where(config('cloud-messaging.country_code_column'), $this->country_code);
            }

            $users = $query->get();

            $response = collect();

            $message = FcmNotification::prepare($this->payload, false);

            $users->chunk(500)->each(function ($chunked) use ($message, $response) {
                try {
                    $response->push(FcmNotification::send($message, $chunked->pluck('fcm_token')->all()));
                } catch (\Exception $exception) {
                    Log::error("[PushNotificationScheduledJob] send-notification " . $exception->getMessage());
                }
            });

            $notification_response = array_merge($this->notification->response ?? [], $response->all() ?? []);
            $this->notification->update([
                'response' => $notification_response,
                'status' => 'completed'
            ]);

            Log::info("[PushNotificationScheduledJob] send notification end");
        } catch (\Exception $exception) {
            \Log::info('Error: [PushNotificationScheduledJob] File: ' . $exception->getFile() . ' Line: ' . $exception->getLine() . ' Message: ' . $exception->getMessage());
        }
    }
}
