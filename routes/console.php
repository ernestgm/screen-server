<?php

use App\Models\DeviceSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('schedule_update', function () {
    $time = Carbon::now()->toTimeString();
    //$this->comment($time);
    (new DeviceSchedule())->getUpdateScheduleForTime(1, $time);
    //(new DeviceSchedule())->getUpdateScheduleForTime(1, "12:00:00");
})->purpose('Update Schedule');
