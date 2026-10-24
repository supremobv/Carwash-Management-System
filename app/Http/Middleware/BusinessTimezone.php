<?php
namespace App\Http\Middleware;
use Closure;
class BusinessTimezone {
    public function handle($request,Closure $next) {
        $timezone=\App\Models\Setting::value('timezone')??'Asia/Manila';
        config(['app.timezone'=>$timezone]); date_default_timezone_set($timezone);
        return $next($request);
    }
}
