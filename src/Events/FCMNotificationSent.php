<?php

namespace Jawabapp\CloudMessaging\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class FCMNotificationSent
{
    use Dispatchable, SerializesModels;


    /**
     * @var array
     */
    public $data;

    /**
     * FCMNotificationSent constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        \Log::info('FCM Notifications Event dispatched');
        $this->data = $data;
    }
}


