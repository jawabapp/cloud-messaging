<?php

namespace Jawabapp\CloudMessaging\Jobs;

use Log;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Jawabapp\CloudMessaging\Models\Notification;
use Jawabapp\CloudMessaging\Notifications\FcmNotification;

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

            $this->notification->update([
                'response' => array_merge($this->notification->response ?? [], $response->all() ?? []),
            ]);

            $notification_job_ids = $this->notification->schedule['job_ids'] ?? [];

            switch (env('QUEUE_DRIVER')) {
                case 'redis':
                    $this->checkRedisJobs($notification_job_ids);
                    break;

                case 'database':
                    if ($notification_job_ids) {
                        $this->checkDBJobs($notification_job_ids);
                    }
                    break;
            }

            Log::info("[PushNotificationScheduledJob] send notification end");
        } catch (\Exception $exception) {
            Log::info('Error: [PushNotificationScheduledJob] File: ' . $exception->getFile() . ' Line: ' . $exception->getLine() . ' Message: ' . $exception->getMessage());
        }
    }

    protected function checkRedisJobs($notification_job_ids)
    {
        $jobs = Redis::zrange("queues:cloud-message:delayed", 0, -1);
        $notification_jobs = array_filter($jobs, function ($job) use ($notification_job_ids) {
            $job_data = json_decode($job, true);
            $job_id = $job_data['id'] ?? null;
            return in_array($job_id, $notification_job_ids);
        });

        if (!$notification_jobs) {
            $this->markNotificationAsCompleted();
        }
    }

    protected function checkDBJobs($notification_job_ids)
    {
        $jobs_count = DB::table('jobs')->whereIn('id', $notification_job_ids)->count();
        if (!$jobs_count) {
            $this->markNotificationAsCompleted();
        }
    }

    protected function markNotificationAsCompleted()
    {
        $this->notification
            ->update([
                'status' => 'completed'
            ]);
    }
}
