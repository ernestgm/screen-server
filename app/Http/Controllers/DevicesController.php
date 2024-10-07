<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceStoreRequest;
use App\Http\Requests\DeviceUpdateRequest;
use \App\Models\Device;
use App\Models\Screen;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DevicesController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Device::with(['screen', 'marquee'])->get()
        ]);
    }

    public function store(DeviceStoreRequest $request): JsonResponse
    {
        $request->validated();
        $inputs = $request->all();

        if ($this->validateLimitDevice($inputs['user_id'])){
            Device::create($inputs);
            return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
        }

        return response()->json(['success'=>'limit_device'], app('SUCCESS_STATUS'));
    }

    private function validateLimitDevice($userId): bool {
        $user = User::with(['devices','role'])->find($userId);
        if ($user->limit_devices === 0) {
            return true;
        }

        return ($user->limit_devices > count($user->devices));
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
        $device = Device::with(['screen.images'])->where('code', $request->query('code'))->get()->first();

        return response()->json([
            'success' => $device->screen != null && $device->user->id === Auth::user()->id,
            'screen' => $device->screen
        ]);
    }

    public function marqueeByCode(Request $request): JsonResponse {
        $device = Device::with('marquee.ads')->where('code', $request->query('code'))->get()->first();

        return response()->json([
            'success' => $device->marquee != null,
            'marquee' => $device->marquee
        ]);
    }

    public function update(DeviceUpdateRequest $request, Device $device): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        $oldDevice = Device::with('marquee.ads')->find($device->id);

        $device->update($input);

        if ($input['marquee_id'] != $oldDevice->marquee_id) {
            $this->sendPublishMessage("player_marquee_$device->code", ["message" => "check_marquee_update"]);
        }
        if ($input['screen_id'] != $oldDevice->screen_id) {
            $this->sendPublishMessage("home_screen_$device->code", ["message" => "check_screen_update"]);
            $this->sendPublishMessage("player_screen_$device->code", ["message" => "check_screen_update"]);
        }

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

        $devices = DB::table('devices')->whereIn('id', $ids)->get();

        // delete records
        $deleted = DB::table('devices')->whereIn('id', $ids)->delete();

        if ($deleted > 0) {
            foreach ($devices as $device) {
                $this->sendPublishMessage("user_" . $device->code, ["message" => "logout"]);
            }
        }

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
