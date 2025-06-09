<?php

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
        return App::make(\Illuminate\Contracts\Debug\ExceptionHandler::class)->shouldReport($e);
    }
}
