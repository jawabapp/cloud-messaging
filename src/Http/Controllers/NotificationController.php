<?php

namespace JawabApp\CloudMessaging\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Validation\ValidationException;
use JawabApp\CloudMessaging\Models\Notification;
use JawabApp\CloudMessaging\Jobs\PushNotificationJob;

class NotificationController extends Controller
{

    public function index(Request $request)
    {
        $notifications = Notification::with('user')->latest()->paginate(10);
        return view('cloud-messaging::notifications.index')->with('data', $notifications);
    }

    public function compose(Request $request, ?Notification $notification = null)
    {
        return view('cloud-messaging::notifications.compose', compact('notification'));
    }

    public function send(Request $request)
    {
        $this->validate($request, [
            'extra_info.name' => 'required|string|max:140',
            'title' => 'nullable|string|max:140',
            'text' => 'required|string|max:240',
            'image' => 'image|mimetypes:' . config('cloud-messaging.image_mimetypes') . '|max:300',
            'target' => 'required|array',
            'target.phone' => 'nullable|string',
        ]);

        $target = $request->get('target');

        $campaign = [];
        $apps = $target['app'] ?? [];
        $phone = $target['phone'] ?? '';

        if ($apps || $phone) {
            $notifiable_model = config('cloud-messaging.notifiable_model');
            $users_count = $notifiable_model::getJawabTargetAudience($target, true);

            $campaign['tokens_count'] = $users_count;
        }

        if (empty($campaign['tokens_count'])) {
            throw ValidationException::withMessages([
                'target.app' => [trans('No Target Audience Found')],
            ]);
        }

        if (app()->environment() !== 'production') {
            if ($campaign['tokens_count'] > 5) {
                throw ValidationException::withMessages([
                    'target.app' => [trans('Only production can send to more than 5 user')],
                ]);
            }
        }

        if ($campaign['tokens_count']) {

            $payload = [
                'body' => $request->get('text')
            ];

            if ($request->has('title')) {
                $payload['title'] = $request->get('title');
            }

            $imageUrl = null;
            if ($request->hasFile('image')) {
                $imageUrl = \Storage::url($request->file('image')->store('notifications'));
                $payload['image'] = $imageUrl;
            }

            $notification = Notification::create([
                'image'  => $imageUrl,
                'extra_info'  => $request->get('extra_info'),
                'title'  => $request->get('title'),
                'text'   => $request->get('text'),
                'target' => $request->get('target'),
                'schedule' => $request->get('schedule'),
                'campaign'  => $campaign,
                'status' => 'pending',
                'user_id'  => auth()->id()
            ]);

            PushNotificationJob::dispatch($notification, $payload)
                ->onQueue('cloud-message');
        }

        return redirect(route('jawab.notifications.index'));
    }

    public function show(Notification $notification)
    {
        $data = collect();

        $this->prepareItem($notification, $data);

        return view('cloud-messaging::notifications.report')->with('data', $data);
    }

    public function delete(Notification $notification)
    {
        switch (env('QUEUE_DRIVER')) {
            case 'redis':
                $this->deleteRedisJobs($notification);
                break;

            case 'database':
                if ($notification->schedule['job_ids'] ?? false) {
                    DB::table('jobs')->whereIn('id', $notification->schedule['job_ids'])->delete();
                }
                break;
        }

        $notification->update([
            'status' => 'deleted'
        ]);

        return redirect(route('jawab.notifications.index'));
    }

    protected function deleteRedisJobs(Notification $notification)
    {
        $key = "queues:cloud-message:delayed";

        $jobs = Redis::zrange($key, 0, -1);

        $notification_job_ids = $notification->schedule['job_ids'] ?? [];

        $notification_jobs = array_filter($jobs, function ($job) use ($notification_job_ids) {
            $job_data = json_decode($job, true);
            $job_id = $job_data['id'] ?? null;
            return in_array($job_id, $notification_job_ids);
        });

        // dd($jobs, $notification_jobs);

        if (count($notification_jobs)) {
            foreach ($notification_jobs as $index => $notification_job) {
                Redis::zrem($key, $notification_job);
            }
        }
    }

    public function reportRefresh()
    {
        //$this->getNotificationCountsFromBigQuery(true);
        return redirect(route('jawab.notifications.report'));
    }

    public function report()
    {

        $start = Carbon::now()->subWeeks(1);
        $end = Carbon::now();

        $items = Notification::whereBetween('created_at', array($start, $end))->latest()->get();

        $data = collect();

        foreach ($items as $item) {
            $this->prepareItem($item, $data);
        }

        $cohort = $data->groupBy('created')->map(function ($row) {
            return [
                'tokens_count' => $row->sum('tokens_count'),
                'viewed' => $row->sum('viewed'),
                'vote' => $row->sum('vote_up') + $row->sum('vote_down'),
                'counts' => $row->count(),
                'comments' => $row->sum('comments'),
                'fcm_sent_count' => $row->sum('fcm_sent_count'),
                'fcm_notification_received_count' => $row->sum('fcm_notification_received_count'),
                'fcm_notification_open_count' => $row->sum('fcm_notification_open_count'),
                'fcm_post_view_count' => $row->sum('fcm_post_view_count'),
                'fcm_post_vote_count' => $row->sum('fcm_post_vote_count'),
                // 'fcm_post_share_count' => $row->sum('fcm_post_share_count'),
                'fcm_post_comment_count' => $row->sum('fcm_post_comment_count'),
            ];
        });

        $cohortData = collect();
        $cohort->each(function ($item, $date) use ($cohortData) {
            $cohortData->push([
                'Notification Created' => $date,
                'Counts' => $item['counts'],
                'Audience' => $item['tokens_count'],
                'Sent' => "{$item['fcm_sent_count']}",
                'Received' => "{$item['fcm_notification_received_count']}",
                'Open' => "{$item['fcm_notification_open_count']}",
                'Post Viewed' => "{$item['viewed']}",
                'Campaign Viewed' => "{$item['fcm_post_view_count']}",
                'Post Vote' => "{$item['vote']}",
                'Campaign Vote' => "{$item['fcm_post_vote_count']}",
                'Post Comments' => "{$item['comments']}",
                // 'Campaign Comments' => "{$item['fcm_post_share_count']}",
                'Campaign Comments' => "{$item['fcm_post_comment_count']}",
            ]);
        });
        \Storage::put('notifications-cohort.csv', csv($cohortData->all()));

        return view('cloud-messaging::notifications.report')->with('data', $data)->with('cohort', $cohort);
    }

    private function prepareItem(Notification $item, Collection $data)
    {

        $campaign_type = $item->campaign['type'] ?? '';
        $campaign_link = $item->campaign['link'] ?? '';
        $campaign_id = $item->campaign['id'] ?? 0;
        $campaign_tokens_count = $item->campaign['tokens_count'] ?? 0;

        $response = collect($item->response);

        $fcm_sent_count = 0;

        $response->each(function ($item) use (&$fcm_sent_count) {
            $fcm_sent_count += intval($item['success']);
        });

        if (request()->get('google')) {
            //TODO: check big query
            // $bigQueryCounts = $this->getNotificationCountsFromBigQuery();
            $bigQueryCount = $bigQueryCounts->where('notification_id', $item->id)->first();
        } else {
            //TODO: update count in report
            $bigQueryCount = $this->getNotificationReport($item->id);
        }

        $campaign = null;

        $data->push([
            'id' => $item->id,
            'fcm_sent_count' => $fcm_sent_count,
            'fcm_notification_received_count' => $bigQueryCount['notification_received_count'] ?? 0,
            'fcm_notification_open_count' => $bigQueryCount['notification_open_count'] ?? 0,
            'fcm_post_view_count' => $bigQueryCount['post_view_count'] ?? 0,
            'fcm_post_vote_count' => $bigQueryCount['post_vote_count'] ?? 0,
            //                    'fcm_post_share_count' => $bigQueryCount['post_share_count'] ?? 0,
            'fcm_post_comment_count' => $bigQueryCount['post_comment_count'] ?? 0,
            'title' => $item->title,
            'text' => $item->text,
            'sent_by' => $item->user->name ?? '',
            'created' => $item->created_at->toDateString(),
            'tokens_count' => $campaign_tokens_count,
            'viewed' => 0,
            'vote_up' => 0,
            'vote_down' => 0,
            'comments' => 0,
            'target' => config('cloud-messaging.notifiable_model')::getJawabTargetAudienceString($item->target),
            'campaign_created' => '',
            'campaign_title' => '',
            'campaign_type' => '',
            'campaign_id' => '',
            'campaign_link' => '',
        ]);
    }

    private function getNotificationReport($id)
    {
        return [
            'notification_received_count' => 0,
            'notification_open_count' => 0,
            'post_view_count' => 0,
            'post_vote_count' => 0,
            'post_share_count' => 0,
            'post_comment_count' => 0,
        ];
    }

    /**
     * @param bool $clearCache
     * @return Collection|mixed
     * @throws \Google\Cloud\Core\Exception\GoogleException
     */
    private function getNotificationCountsFromBigQuery($clearCache = false)
    {

        $key = 'biq-query-notification-counts';

        if ($clearCache || !\Cache::has($key)) {

            $bigQuery = new BigQueryClient([
                'keyFilePath' => storage_path(config('cloud-messaging.big_query.key_file_path')),
                'projectId' => config('cloud-messaging.big_query.project_id'),
            ]);

            $yesterday = now()->format('Y*');

            $table_name = config('cloud-messaging.big_query.table_name') . ".events_{$yesterday}";

            $query = str_replace(
                ['{DATA_TABLE}'],
                [$table_name],
                file_get_contents(storage_path('big-query/notification-counts.sql'))
            );

            $queryJobConfig = $bigQuery->query($query);
            $queryResults = $bigQuery->runQuery($queryJobConfig);

            if ($queryResults->isComplete()) {
                $rows = $queryResults->rows();
            }

            $data = collect($rows ?? []);

            \Cache::put($key, $data, now()->addHours(12));
        } else {
            $data = \Cache::get($key);
        }

        return $data;
    }

    public function downloadCohort()
    {

        $filename = 'notifications-cohort.csv';

        $csvPath = \Storage::get($filename);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => "attachment; filename={$filename}",
            'filename' => $filename
        ];

        return response($csvPath, 200, $headers);
    }
}
