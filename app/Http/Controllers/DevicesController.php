<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceStoreRequest;
use App\Http\Requests\DeviceUpdateRequest;
use \App\Models\Device;
use App\Models\Screen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DevicesController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Device::with(['screen'])->get()
        ]);
    }

    public function store(DeviceStoreRequest $request): JsonResponse
    {
        Device::create($request->validated());

        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
    }

    public function show(Request $request, Device $device): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Device::with(['screen'])->find($device->id)
        ]);
    }

    public function showByDeviceId(Request $request): JsonResponse
    {
        $device = Device::where('device_id', $request->query("device_id"))
            ->where('user_id', $request->query("user_id"))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $device->first()
        ]);
    }

    public function screenByCode(Request $request): JsonResponse {
        $device = Device::with('screen.images')->where('code', $request->query('code'))->get()->first();

        return response()->json([
            'success' => $device->screen != null,
            'screen' => $device->screen
        ]);
    }

    public function update(DeviceUpdateRequest $request, Device $device): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        $device->update($input);

        $this->sendPublishMessage("home_screen_$device->code", ["message" => "check_screen_update"]);
        $this->sendPublishMessage("player_screen_$device->code", ["message" => "check_screen_update"]);

        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
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

        // delete records
        $deleted = DB::table('devices')->whereIn('id', $ids)->delete();

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
