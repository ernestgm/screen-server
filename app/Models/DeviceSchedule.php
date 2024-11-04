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
        'enabled'
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
    public function createSchedule($name, $deviceId, $startTime, $endTime, $type, $screenId, $marqueeId, $enabled)
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        if ($enabled == 1) {
            $this->verifyOverlapTime($deviceId, $type, $start, $end);
        }

        return DeviceSchedule::create([
            'name' => $name,
            'device_id' => $deviceId,
            'start_time' => $start,
            'end_time' => $end,
            'schedule_type' => $type,
            'screen_id' => $type === 'screen' || $type === 'all' ? $screenId : null,
            'marquee_id' => $type === 'marquee' || $type === 'all' ? $marqueeId : null,
            'enabled' => $enabled,
        ]);
    }

    /**
     * @throws \Exception
     */
    public function updateSchedule(DeviceSchedule $deviceSchedule, $name, $deviceId, $startTime, $endTime, $type, $screenId, $marqueeId, $enabled): bool
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        if ($enabled == 1) {
            $this->verifyOverlapTime($deviceId, $type, $start, $end, $deviceSchedule);
        }

        // Crear el registro en la base de datos
        return $deviceSchedule->update([
            'name' => $name,
            'device_id' => $deviceId,
            'start_time' => $start,
            'end_time' => $end,
            'schedule_type' => $type,
            'screen_id' => $type === 'screen' || $type === 'all' ? $screenId : null,
            'marquee_id' => $type === 'marquee' || $type === 'all' ? $marqueeId : null,
            'enabled' => $enabled,
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
                ->where('enabled', 1)
                ->where(function ($query) use ($type) {
                    if ($type != 'all') {
                        $query->where('schedule_type', $type);
                    }
                })
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
                ->where(function ($query) use ($type) {
                    if ($type != 'all') {
                        $query->where('schedule_type', $type);
                    }
                })
                ->where('enabled', 1)
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
}

