<?php

namespace JawabApp\CloudMessaging\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
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
    private $notifiable_model;

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
        $this->notifiable_model = config('cloud-messaging.notifiable_model');
        $this->payload['notification_id'] = $notification->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("send notification start");

        if (empty($this->notification)) {
            return;
        }

        $schedule = $this->notification->schedule;
        Log::info(config('cloud-messaging.country_code_column'), $schedule);

        switch ($schedule['type'] ?? 'Now') {
            case 'Now':
                $this->sendNow();
                break;
            case 'Scheduled':
                $this->sendToScheduledDate($schedule['date'] ?? null);
                break;
        }
    }

    protected function sendToScheduledDate($scheduledDate = null)
    {
        if (config('cloud-messaging.country_code_column')) {
            $country_users = $this->notifiable_model::getJawabTargetAudience($this->notification->target, false, true)
                ->get()
                ->groupBy([
                    config('cloud-messaging.country_code_column')
                ]);

            $countries = collect(config('cloud-messaging-countries'));

            foreach ($country_users as $country_code => $users) {
                $country = $countries->where('country_code', strtoupper($country_code))->first();
                $scheduledCountryDateTime = Carbon::parse($scheduledDate, $country->timezones[0] ?? null);
                $this->publishScheduledJob($scheduledCountryDateTime, $country_code);
            }
        } else {
            $this->publishScheduledJob($scheduledDate);
        }
    }

    protected function publishScheduledJob($scheduledDate, $country_code = null)
    {

        $jobId = app(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatch(
            (new PushNotificationScheduledJob($this->notification, $this->payload, $country_code))
                ->onQueue('cloud-message:' . $this->notification->id . ':' . $country_code)
                ->delay(now()->diff(Carbon::parse($scheduledDate)))
        );

        $schedule = $this->notification->schedule;
        $schedule['job_ids'][] = $jobId;

        $this->notification->update([
            'schedule' => $schedule
        ]);
    }

    protected function sendNow()
    {
        try {
            $this->notification->update([
                'status' => 'processing'
            ]);

            $users = $this->notifiable_model::getJawabTargetAudience($this->notification->target);

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
