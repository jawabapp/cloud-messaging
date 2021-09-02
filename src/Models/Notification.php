<?php

namespace JawabApp\CloudMessaging\Models;

use Illuminate\Database\Eloquent\Model;

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
}
