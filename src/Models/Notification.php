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
        'status'
    ];

    protected $casts = [
        'target' => 'array',
        'campaign' => 'array',
        'response' => 'array',
        'schedule' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(config('jawab-fcm.user_model'));
    }
}
