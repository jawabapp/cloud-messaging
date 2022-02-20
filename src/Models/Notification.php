<?php

namespace Jawabapp\CloudMessaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Notification extends Model
{
    protected $table = 'jawab_notifications';

    protected $fillable = [
        'title',
        'text',
        'image',
        'target',
        'campaign',
        'user_id',
        'response',
        'schedule',
        'status',
        'extra_info'
    ];

    protected $casts = [
        'target' => 'array',
        'campaign' => 'array',
        'response' => 'array',
        'schedule' => 'array',
        'extra_info' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(config('cloud-messaging.user_model'));
    }

    public function setResponseAttribute($value) {

        Log::info('Notification_API_Response' , $value);

        if(is_array($value)) {
            $path = 'app/jawab_notifications/' . uniqid() . '.json';
            file_put_contents(storage_path($path), json_encode($value));

            $this->attributes['response'] = json_encode(['response_path' => $path]);
        } else {
            $this->attributes['response'] = $value;
        }

    }

    public function getResponseAttribute() {

        $original = $this->getOriginal('response');

        if(!$original) {
            return null;
        }

        $json = json_decode($original, true);
        if(!empty($json['response_path']) && is_string($json['response_path']) && file_exists(storage_path($json['response_path']))) {
            $original = file_get_contents(storage_path($json['response_path']));
        }

        return json_decode($original, true);
    }
}
