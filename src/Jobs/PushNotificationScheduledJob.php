<?php

namespace Jawabapp\CloudMessaging\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    private $model;
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
        $this->model = config('cloud-messaging.notifiable_model');
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

            $response = collect();

            try {
                $sender = $this->notification->id;
                $message = $this->payload;

                $users = $this->model::getJawabTargetAudience($this->notification->target, false, true);

                if ($this->country_code) {
                    $users->where(config('cloud-messaging.country_code_column'), $this->country_code);
                }

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

            $this->notification->update([
                'response' => [
                    'success' => ($this->notification->response['success'] ?? 0) + $success,
                    'failure' => ($this->notification->response['failure'] ?? 0) + $failure,
                ],
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
