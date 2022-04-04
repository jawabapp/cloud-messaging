<?php

namespace Jawabapp\CloudMessaging\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;

class FCMNotificationSent
{
    use Dispatchable, SerializesModels;

    public $message;
    public $response;
    public $type;
    public $sender;

    public function __construct($message, $response, $type, $sender)
    {
        $this->message = $message;
        $this->response = $response;
        $this->type = $type;
        $this->sender = $sender;
    }
}


