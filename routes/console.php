<?php

use App\Services\BookingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('bookings:sweep', function (BookingService $bookings) {
    $count = $bookings->sweepExpired();
    $this->info("Pesanan kedaluwarsa yang diproses: {$count}");
})->purpose('Kedaluwarsa pesanan yang melewati batas respons atau pembayaran');

Schedule::command('bookings:sweep')->everyMinute();
