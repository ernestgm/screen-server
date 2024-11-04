<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceSchedule;
use Carbon\Carbon;

class DeviceScheduleService
{
    private CentrifugueService $centrifugueService;

    public function __construct()
    {
        $this->centrifugueService = new CentrifugueService();
    }

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

        $scheduleAll = DeviceSchedule::with(['marquee'])
            ->where('device_id', $deviceId)
            ->where('schedule_type', 'all')
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->first();


        $device = Device::with(['defaultScreen', 'defaultMarquee'])->find($deviceId);

        if ($scheduleAll) {
            $updateScreens = $this->updateScheduleScreen($device, $scheduleAll);
            $this->updateScheduleMarquee($device, $scheduleAll, $updateScreens);
            return;
        }

        if (!$scheduleScreen) {
            if ($device->defaultScreen != null && $device->defaultScreen->id != $device->screen_id) {
                $device->update(['screen_id' => $device->defaultScreen->id]);
                $controller->sendPublishMessage("home_screen_$device->code", ["message" => "check_screen_update"]);
                $controller->sendPublishMessage("player_screen_$device->code", ["message" => "check_screen_update"]);
                $updateScreens = true;
            }
        } else {
            $updateScreens = $this->updateScheduleScreen($device, $scheduleScreen);
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
            $this->updateScheduleMarquee($device, $scheduleMarquee, $updateScreens);
        }
    }

    private function updateScheduleScreen($device, $schedule): bool
    {
        if ($schedule->screen != null && $schedule->screen->id != $device->screen_id) {
            $device->update(['screen_id' => $schedule->screen->id]);
            $this->centrifugueService->sendPublishMessage("home_screen_$device->code", ["message" => "check_screen_update"]);
            $this->centrifugueService->sendPublishMessage("player_screen_$device->code", ["message" => "check_screen_update"]);
            return true;
        }

        return false;
    }

    private function updateScheduleMarquee($device, $schedule, $publish): void
    {
        if ($schedule->marquee != null && $schedule->marquee->id != $device->marquee_id) {
            $device->update(['marquee_id' => $schedule->marquee->id]);
            if (!$publish) {
                $this->centrifugueService->sendPublishMessage("player_marquee_$device->code", ["message" => "check_marquee_update"]);
            }
        }
    }
}

