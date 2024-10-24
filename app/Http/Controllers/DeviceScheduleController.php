<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdStoreRequest;
use App\Http\Requests\AdUpdateRequest;

use App\Http\Requests\DevicesScheduleStoreRequest;
use App\Models\Ad;
use App\Models\DeviceSchedule;
use App\Models\Marquee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeviceScheduleController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $deviceId = $request->input('device_id');
        $scheduleType = $request->input('schedule_type');
        if ($deviceId && $scheduleType) {
            $all = DeviceSchedule::with(['device', 'marquee', 'screen'])
                ->where('device_id', $deviceId)
                ->where('schedule_type', $scheduleType)
                ->get();
        } else {
            $all = DeviceSchedule::with(['device', 'marquee', 'screen'])->get();
        }

        return response()->json([
            'success' => true,
            'data' => $all
        ]);
    }

    /**
     */
    public function store(DevicesScheduleStoreRequest $request): JsonResponse
    {
        $inputs = $request->all();
        try {
            (new DeviceSchedule)->createSchedule(
                $inputs['device_id'],
                $inputs['start_time'],
                $inputs['end_time'],
                $inputs['schedule_type'],
                $inputs['screen_id'],
                $inputs['marquee_id'],
            );
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()]);
        }

        return response()->json(['success' => 'success'], app('SUCCESS_STATUS'));
    }

    public function show(Request $request, DeviceSchedule $deviceSchedule): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DeviceSchedule::with(['device', 'marquee', 'screen'])->find($deviceSchedule->id)
        ]);
    }

    public function update(DevicesScheduleStoreRequest $request, DeviceSchedule $deviceSchedule): JsonResponse
    {
        $inputs = $request->all();
        try {
            (new DeviceSchedule)->updateSchedule(
                $deviceSchedule,
                $inputs['device_id'],
                $inputs['start_time'],
                $inputs['end_time'],
                $inputs['schedule_type'],
                $inputs['screen_id'],
                $inputs['marquee_id']
            );
        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()]);
        }

        return response()->json(['success' => 'success'], app('SUCCESS_STATUS'));
    }

    public function delete(Request $request): JsonResponse
    {
        $ids = $request->input('ids'); // array of IDs to delete

        // validate input
        $validator = Validator::make(['ids' => $ids], [
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], app('VALIDATION_STATUS'));
        }

        $deviceSchedules = DB::table('device_schedules')->whereIn('id', $ids);
        // delete records
        $deleted = $deviceSchedules->delete();


        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
