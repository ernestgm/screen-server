<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceSchedule;
use Carbon\Carbon;

class DeviceScheduleService
{
    public function updateDevicesBySchedule(): void
    {
        $devices = Device::all();
        $time = Carbon::now()->toTimeString();

        foreach ($devices as $device) {
            $this->getUpdateScheduleForTime($device->id, $time);
            //$this->getUpdateScheduleForTime($device->id, "12:00:00");
        }
    }

    public function getUpdateScheduleForTime($deviceId, $time): void
    {
        $controller = new Controller();
        //$time = Carbon::parse($time)->toTimeString();
        $updateScreens = false;

        // Buscar un schedule activo para el tiempo dado
        $scheduleScreen = DeviceSchedule::with(['screen'])
            ->where('device_id', $deviceId)
            ->where('schedule_type', 'screen')
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->first();

        $scheduleMarquee = DeviceSchedule::with(['marquee'])
            ->where('device_id', $deviceId)
            ->where('schedule_type', 'marquee')
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->first();


        $device = Device::with(['defaultScreen', 'defaultMarquee'])->find($deviceId);

        if (!$scheduleScreen) {
            if ($device->defaultScreen != null && $device->defaultScreen->id != $device->screen_id) {
                $device->update(['screen_id' => $device->defaultScreen->id]);
                $controller->sendPublishMessage("home_screen_$device->code", ["message" => "check_screen_update"]);
                $controller->sendPublishMessage("player_screen_$device->code", ["message" => "check_screen_update"]);
                $updateScreens = true;
            }
        } else {
            if ($scheduleScreen->screen != null && $scheduleScreen->screen->id != $device->screen_id) {
                //(new Command())->info($device->toJson(JSON_PRETTY_PRINT));
                $device->update(['screen_id' => $scheduleScreen->screen->id]);
                $controller->sendPublishMessage("home_screen_$device->code", ["message" => "check_screen_update"]);
                $controller->sendPublishMessage("player_screen_$device->code", ["message" => "check_screen_update"]);
                $updateScreens = true;
            }
        }

        if (!$scheduleMarquee) {
            if ($device->defaultMarquee != null && $device->defaultMarquee->id != $device->marquee_id) {
                $device->update(['marquee_id' => $device->defaultMarquee->id]);
                if (!$updateScreens) {
                    $controller->sendPublishMessage("player_marquee_$device->code", ["message" => "check_marquee_update"]);
                }
            } elseif ($device->defaultMarquee == null) {
                $device->update(['marquee_id' => null]);
                if (!$updateScreens) {
                    $controller->sendPublishMessage("player_marquee_$device->code", ["message" => "check_marquee_update"]);
                }
            }
        } else {
            if ($scheduleMarquee->marquee != null && $scheduleMarquee->marquee->id != $device->marquee_id) {
                $device->update(['marquee_id' => $scheduleMarquee->marquee->id]);
                if (!$updateScreens) {
                    $controller->sendPublishMessage("player_marquee_$device->code", ["message" => "check_marquee_update"]);
                }
            }
        }
    }
}

