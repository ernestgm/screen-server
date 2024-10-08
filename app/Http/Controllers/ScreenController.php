<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScreenStoreRequest;
use App\Http\Requests\ScreenUpdateRequest;
use App\Models\Device;
use App\Models\Screen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScreenController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $id = $request->input('business_id');
        $all = Screen::with(['business.user', 'devices'])->get();
        if ($id) {
            $all = Screen::with(['business.user', 'devices'])->where('business_id', $id)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $all
        ]);
    }

    public function store(ScreenStoreRequest $request): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        Screen::create($input);
        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
    }

    public function show(Request $request, Screen $screen): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Screen::with(['business.user', 'devices'])->find($screen->id)
        ]);
    }

    public function update(ScreenUpdateRequest $request, Screen $screen): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        $screen->update($input);

        $devices = Device::where('screen_id', $screen->id)->get();
        foreach ($devices as $device) {
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

        // delete records
        $deleted = DB::table('screens')->whereIn('id', $ids)->delete();

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
