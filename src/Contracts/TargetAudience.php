<?php

namespace JawabApp\CloudMessaging\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface TargetAudience
{
    public static function targetAudienceForOs(Builder $builder, $os);

    public static function targetAudienceForPhoneNumbers(Builder $builder, $phone_numbers);
}
