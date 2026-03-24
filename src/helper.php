<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Arr;

if (! function_exists('getCurPageParams')) {
    function getCurPageParams($add = [], $remove = []): string
    {
        $r = request()?->all();
        if ($add) {
            $r = array_merge($r, $add);
        }
        if ($remove) {
            Arr::forget($r, $remove);

            return trim(request()?->url().'?'.http_build_query($r), '?');
        }

        return '/'.trim(request()?->path().'?'.http_build_query($r), '?');
    }
}
if (! function_exists('isReportable')) {
    function isReportable($e): string
    {
        return App::make(ExceptionHandler::class)->shouldReport($e);
    }
}
