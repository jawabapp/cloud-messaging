<?php

namespace JawabApp\CloudMessaging\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{

    /**
     * target-audience
     *
     * @param Request $request
     * @return \Illuminate\Support\Collection|int
     */
    public function targetAudience(Request $request)
    {

        $target = $request->get('target');

        $apps = $target['app'] ?? [];
        $phone = $target['phone'] ?? '';

        if ($apps || $phone) {
            $notifiable_model = config('cloud-messaging.notifiable_model');
            return $notifiable_model::getJawabTargetAudience($target, true);
        }

        return 0;
    }
}
