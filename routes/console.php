<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jalan tiap jam - cek booking pending yang sudah lewat 24 jam belum
// dibayar, tandai expired. Untuk scheduler ini benar-benar jalan
// otomatis (bukan cuma terdaftar), WSL/server harus punya cron yang
// manggil `php artisan schedule:run` tiap menit - lihat catatan di chat.
Schedule::command('bookings:expire')->hourly();
