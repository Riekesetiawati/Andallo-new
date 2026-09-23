<?php

namespace App\Http\Middleware;

use App\Services\BookingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SweepBookings
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Cache::add('andallo.booking-sweep', true, 15)) {
            app(BookingService::class)->sweepExpired();
        }

        return $next($request);
    }
}
