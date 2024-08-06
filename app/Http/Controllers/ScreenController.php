<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScreenStoreRequest;
use App\Http\Requests\ScreenUpdateRequest;
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
        $code = $request->input('code');
        $this->sendPublishMessage("home_screen_$code", ["message" => "check_screen_update"]);
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


        $devices = Screen::with('devices')->find($screen->id)->get()->first()->devices;
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

        $screens = DB::table('screens')->whereIn('id', $ids)->get();

        // delete records
        $deleted = DB::table('screens')->whereIn('id', $ids)->delete();

        if ($deleted > 0) {
            foreach ($screens as $screen) {
                $this->sendPublishMessage("screen_".$screen->code, ["message" => "check_screen_update"]);
            }
        }

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }

    public function checkForUpdate(Request $request): JsonResponse
    {
        $screen = Screen::all()->where('code', $request->query('code'))->first();
        $update = $screen->checkForUpdate($request->query('udpated_time'));

        return response()->json([
            'success' => $update
        ]);
    }
}
