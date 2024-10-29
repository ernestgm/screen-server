<?php

namespace App\Models;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Artisan;

class DeviceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'device_id',
        'screen_id',
        'marquee_id',
        'start_time',
        'end_time',
        'schedule_type',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function marquee(): BelongsTo
    {
        return $this->belongsTo(Marquee::class);
    }

    /**
     * @throws \Exception
     */
    public function createSchedule($name, $deviceId, $startTime, $endTime, $type, $screenId = null, $marqueeId = null)
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        $this->verifyOverlapTime($deviceId, $type, $start, $end);

        return DeviceSchedule::create([
            'name' => $name,
            'device_id' => $deviceId,
            'start_time' => $start,
            'end_time' => $end,
            'schedule_type' => $type,
            'screen_id' => $type === 'screen' ? $screenId : null,
            'marquee_id' => $type === 'marquee' ? $marqueeId : null,
        ]);
    }

    /**
     * @throws \Exception
     */
    public function updateSchedule(DeviceSchedule $deviceSchedule, $name, $deviceId , $startTime, $endTime, $type, $screenId = null, $marqueeId = null): bool
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        $this->verifyOverlapTime($deviceId, $type, $start, $end, $deviceSchedule);

        // Crear el registro en la base de datos
        return $deviceSchedule->update([
            'name' => $name,
            'device_id' => $deviceId,
            'start_time' => $start,
            'end_time' => $end,
            'schedule_type' => $type,
            'screen_id' => $type === 'screen' ? $screenId : null,
            'marquee_id' => $type === 'marquee' ? $marqueeId : null,
        ]);
    }

    /**
     * @throws \Exception
     */
    function verifyOverlapTime($deviceId, $type, $start, $end, DeviceSchedule $deviceSchedule = null): void
    {
        if ($deviceSchedule) {
            $overlap = DeviceSchedule::where('device_id', $deviceId)
                ->where('id', '!=', $deviceSchedule->id)
                ->where('schedule_type', $type)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('start_time', [$start, $end])
                        ->orWhereBetween('end_time', [$start, $end])
                        ->orWhere(function ($query) use ($start, $end) {
                            $query->where('start_time', '<=', $start)
                                ->where('end_time', '>=', $end);
                        });
                })
                ->exists();
        } else {
            $overlap = DeviceSchedule::where('device_id', $deviceId)
                ->where('schedule_type', $type)
                ->where(function ($query) use ($start, $end) {
                    $query->where(function ($query) use ($start, $end) {
                        $query->where('start_time', '<', $end)
                            ->where('end_time', '>', $start);
                    });
                })
                ->exists();
        }

        if ($overlap) {
            throw new \Exception('The schedule overlaps with an existing schedule.');
        }
    }

    public function getUpdateScheduleForTime($deviceId, $time) : void
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
            } elseif($device->defaultMarquee == null) {
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

