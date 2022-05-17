<?php

namespace Jawabapp\CloudMessaging\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

trait HasTargetAudience
{
    public static function getJawabTargetAudienceString($target)
    {

        $targetString = 'sent to';

        $apps = $target['app'] ?? [];
        $phone = $target['phone'] ?? '';

        if (!$phone && !$phone) {
            $targetString .= " all";
        }

        if ($phone) {
            $targetString .= " phone {$phone}";
        }

        if ($phone && $apps) {
            $targetString .= " and";
        }

        $appsCount = 0;

        foreach ($apps as $app) {

            $appsCount++;

            $targetString .= " {$app['os']} mobiles";

            foreach (($app['and'] ?? []) as $and) {
                if (!empty($and['options']) && !empty($and['type'])) {

                    $options = collect($and['options'])->map(function ($option) {
                        return trim($option);
                    })->filter(function ($option) {
                        return !empty($option);
                    });
                    $options = implode(', ', $options->toArray());
                    $targetString .= " and {$and['type']} {$and['condition']} ({$options})";
                }
            }

            if ($appsCount != count($apps)) {
                $targetString .= " and";
            }
        }

        return $targetString;
    }

    public static function getJawabTargetAudience($target, $count = false, $isQuery = false)
    {

        $apps = $target['app'] ?? [];
        $phone = $target['phone'] ?? '';
        $limit = (int) $target['limit'] ?? 0;
        $ql = $target['ql'] ?? '';

        $tableName = (new self)->getTable();

        $query = self::select($tableName . '.*')->distinct();

        //TODO: check inactive users
        // $query->whereNull('inactive_at');

        $query->where(function ($qq) use ($apps, $phone, $ql, $query) {

            if ($phone) {

                $phones = self::getPhones($phone);

                if (method_exists(self::class, $method = 'targetAudienceForPhoneNumbers')) {
                    self::{$method}($qq, $phones);
                }
            }

            $joins = [];
            $joins['query'] = $query;

            foreach ($apps as $app) {

                $qq->orWhere(function ($q) use ($app, &$joins) {

                    if (method_exists(self::class, $method = 'targetAudienceForOs')) {
                        self::{$method}($q, $app['os']);
                    }

                    foreach (($app['and'] ?? []) as $and) {
                        if (!empty($and['options']) && !empty($and['type'])) {

                            $options = collect($and['options'])->map(function ($option) {
                                return trim($option);
                            })->filter(function ($option) {
                                return !empty($option);
                            });

                            if (method_exists(self::class, $method = 'targetAudienceFor' . Str::studly($and['type']))) {
                                self::{$method}($q, $and['condition'], $options, $joins);
                            }
                        }
                    }
                });
            }

            if ($ql) {
                $qq->where(function ($q) use ($ql) {
                    $q->whereRaw($ql);
                });
            }
        });

        if ($limit) {
            $query->limit($limit);
        }

        if ($isQuery) {
            return $query;
        }

        if ($count) {
            return $query->count($tableName . '.id');
        }

        return $query->get();
    }

    protected static function getPhones($phone)
    {
        return collect(explode(',', $phone))->map(function ($phone) {
            return trim($phone);
        })->filter(function ($phone) {
            return !empty($phone);
        });
    }
}
