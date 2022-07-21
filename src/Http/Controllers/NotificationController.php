<?php

namespace Jawabapp\CloudMessaging\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Validation\ValidationException;
use Jawabapp\CloudMessaging\Models\Notification;
use Jawabapp\CloudMessaging\Jobs\PushNotificationJob;

class NotificationController extends Controller
{

    public function index(Request $request)
    {
        $query = Notification::with('user')->latest();

        collect($request->get('fltr'))
            ->only(array_merge(['name', 'conversion'], array_keys(config('cloud-messaging.extra_info', []))))
            ->each(function ($value, $column) use ($query) {
                if($value) {
                    $query->whereRaw("extra_info->>'$.{$column}' = ?", $value);
                }
            });

        $data = $query->paginate(10);

        return view('cloud-messaging::notifications.index', compact('data'));
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
                $imageUrl = Storage::disk(config('cloud-messaging.disk-storage'))->url($request->file('image')->store('notifications', config('cloud-messaging.disk-storage')));
                $payload['image'] = $imageUrl;
            }

            if ($request->has('extra_info')) {
                $extra_info = $request->get('extra_info');

                foreach ($extra_info as $key => $value) {
                    $payload['data'][$key] = $value;
                }
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

    public function report()
    {

        $start = Carbon::now()->subWeeks(2);
        $end = Carbon::now();

        $items = Notification::whereBetween('created_at', array($start, $end))->latest()->get();

        $data = collect();

        foreach ($items as $item) {
            $this->prepareItem($item, $data);
        }

        $cohort = $data->groupBy('created')->map(function ($row) {
            return [
                'counts' => $row->count(),
                'audience' => $row->sum('audience'),
                'sends' => $row->sum('sends'),
                'opens' => $row->sum('opens'),
                'conversions' => $row->sum('conversions'),
            ];
        });

        Cache::forever('last-cohort', $cohort->all());

        return view('cloud-messaging::notifications.report')->with('data', $data)->with('cohort', $cohort);
    }

    public function downloadCohort()
    {
        $filename = 'notifications-cohort.csv';
        $data = collect();

        $cohort = Cache::get('last-cohort');
        if($cohort) {
            collect($cohort)->each(function ($item, $date) use ($data) {
                $data->push([
                    'Notification Created' => $date,
                    'Counts' => $item['counts'] ?? 0,
                    'Audience' => $item['audience'] ?? 0,
                    'Sends' => $item['sends'] ?? 0,
                    'Opens' => $item['opens'] ?? 0,
                    'Conversions' => $item['conversions'] ?? 0,
                ]);
            });
        } else {
            $data->push([
                'Notification Created' => 'No Data',
                'Counts' => 0,
                'Audience' => 0,
                'Sends' => 0,
                'Opens' => 0,
                'Conversions' => 0,
            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => "attachment; filename={$filename}",
            'filename' => $filename
        ];

        return response($this->csv($data->all()), 200, $headers);
    }

    /********************************************** HELPERS **********************************************/

    private function prepareItem(Notification $item, Collection $data)
    {

        $openCount = $this->getOpens($item->id);
        $conversionCount = $this->getConversions($item->extra_info['conversion'] ?? null, $item->id);

        $targetAudienceString = config('cloud-messaging.notifiable_model')::getJawabTargetAudienceString($item->target);

        $data->push([
            'id' => $item->id,
            'title' => $item->title,
            'text' => $item->text,
            'sent_by' => $item->user->name ?? '',
            'created' => $item->created_at->toDateString(),
            'target' => $targetAudienceString,
            'audience' => $item->campaign['tokens_count'] ?? 0,
            'sends' => $item->response['success'] ?? 0,
            'opens' => $openCount['counts'] ?? 0,
            'conversions' => $conversionCount['counts'] ?? 0,
        ]);
    }

    private function bigQuery($eventName) {

        $yesterday = now()->format('Y*');
        $tableName = config('cloud-messaging.big_query.table_name');
        $dataSource =  "{$tableName}.events_{$yesterday}";

        return trim("
        with
        notification_analytics as (SELECT
            event_param.value.string_value as notification_id, count(distinct user_pseudo_id) as counts
            FROM `{$dataSource}`, unnest(event_params) AS event_param
            where event_name = '{$eventName}' and event_param.key = 'notification_id'
            group by event_param.value.string_value)

        select * from notification_analytics
        ");
    }

    /**
     * @param false $clearCache
     * @return mixed
     */
    private function getEventAnalytics($eventName)
    {
        $key = $eventName;

        if (!Cache::has($key)) {

            $query = $this->bigQuery($eventName);
            $data = $this->executeBigQuery($query);

            Cache::put($key, $data, now()->addHours(12));
        } else {
            $data = Cache::get($key);
        }

        return $data;
    }

    private function getOpens($notificationId) {
        $data = $this->getEventAnalytics(config('cloud-messaging.notification_open_event_name'));
        return $data->where('notification_id', $notificationId)->first();
    }

    private function getConversions($eventName, $notificationId) {
        if($eventName) {
            $data = $this->getEventAnalytics($eventName);
            return $data->where('notification_id', $notificationId)->first();
        }
        return [];
    }

    private function executeBigQuery($query) {

        $bigQuery = new BigQueryClient([
            'keyFilePath' => storage_path(config('cloud-messaging.big_query.key_file_path')),
            'projectId' => config('cloud-messaging.big_query.project_id'),
        ]);

        $queryJobConfig = $bigQuery->query($query);
        $queryResults = $bigQuery->runQuery($queryJobConfig);

        if ($queryResults->isComplete()) {
            $rows = $queryResults->rows();
        }

        return collect($rows ?? []);
    }

    private function deleteRedisJobs(Notification $notification)
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

    private function csv($array, $headers = [])
    {
        if (empty($array)) {
            return '';
        }

        foreach ($array as &$item) {
            foreach ($item as $key => &$value) {
                //fix value
                $value = str_replace(',', '-', $value);
            }
        }

        if (!empty($headers)) {
            $txt = implode(',', $headers) . PHP_EOL;
        } else {
            $txt = implode(',', array_keys((array) $array[0])) . PHP_EOL;
        }
        foreach ($array as $line) {
            $txt .= implode(',', array_values((array) $line)) . PHP_EOL;
        }

        return $txt;
    }
}
