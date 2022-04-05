<?php

namespace Jawabapp\CloudMessaging\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Jawabapp\CloudMessaging\Models\Notification;
use Jawabapp\CloudMessaging\Notifications\FcmNotification;
use Jawabapp\CloudMessaging\Traits\HasCloudMessagingQueue;

class PushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $notification;
    private $payload;
    private $model;

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
        $this->payload['notification_id'] = $notification->id;
        $this->model = config('cloud-messaging.notifiable_model');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("[PushNotificationJob] send notification start");

        if (empty($this->notification)) {
            return;
        }

        $schedule = $this->notification->schedule;

        switch ($schedule['type'] ?? 'Now') {
            case 'Scheduled':
                $this->publishToScheduledDate($schedule['date'] ?? null);
                break;
            case 'Now':
            default:
                $this->publishNow();
                break;
        }
    }

    protected function publishToScheduledDate($scheduledDate = null)
    {
        if (config('cloud-messaging.country_code_column')) {
            $country_users = $this->model::getJawabTargetAudience($this->notification->target)->groupBy([
                config('cloud-messaging.country_code_column')
            ]);

            $countries = collect(config('cloud-messaging-countries'));

            foreach ($country_users as $country_code => $users) {
                $country = $countries->where('country_code', strtoupper($country_code))->first();

                $countryTimeZone = $country['timezones'][0] ?? null;

                $this->publishScheduledJob($scheduledDate, $country_code, $countryTimeZone);
            }
        } else {
            $this->publishScheduledJob($scheduledDate);
        }
    }

    protected function publishScheduledJob($scheduledDate, $country_code = null, $countryTimeZone = null)
    {
        $date_time = Carbon::parse($scheduledDate);

        $jobId = app(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatch(
            (new PushNotificationScheduledJob($this->notification, $this->payload, $country_code))->onQueue('cloud-message')->delay(
                now($countryTimeZone)->diff(Carbon::parse($date_time, $countryTimeZone))
            )
        );

        $schedule = $this->notification->schedule;
        $schedule['job_ids'][] = $jobId;

        $this->notification->update([
            'schedule' => $schedule
        ]);
    }

    protected function publishNow()
    {
        try {
            $this->notification->update([
                'status' => 'processing'
            ]);

            $response = collect();

            try {
                $sender = $this->notification->user_id;

                $message = FcmNotification::prepare($this->payload);

                $users = $this->model::getJawabTargetAudience($this->notification->target, false, true);

                $users->chunk(500, function ($chunked) use ($message, $response, $sender) {

                    $res = FcmNotification::sendMessage($message, $chunked, 'cloud-message', $sender);

                    $response->push(collect($res)->pluck('api_response')->all());

                });
            } catch (\Exception $exception) {
                $this->error("[PushNotificationJob] send-notification " . $exception->getMessage());
            }

            $this->notification->update([
                'response' => $response,
                'status' => 'completed'
            ]);

            Log::info("[PushNotificationJob] send notification end");
        } catch (\Exception $exception) {
            \Log::info('Error: [PushNotificationJob] File: ' . $exception->getFile() . ' Line: ' . $exception->getLine() . ' Message: ' . $exception->getMessage());
        }
    }
}
