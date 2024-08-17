<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdStoreRequest;
use App\Http\Requests\AdUpdateRequest;

use App\Models\Ad;
use App\Models\Marquee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $marqueeId = $request->input('marquee_id');
        $all = Ad::all();
        if ($marqueeId) {
            $all = Ad::with(['marquee'])->where('marquee_id', $marqueeId)->get();
        }
        return response()->json([
            'success' => true,
            'data' => $all
        ]);
    }

    public function store(AdStoreRequest $request): JsonResponse
    {
        $request->validated();
        $inputs = $request->all();
        Ad::create($inputs);

        $marquee = Marquee::with('devices')->find($inputs['marquee_id']);
        foreach ($marquee->devices as $device) {
            $this->sendPublishMessage("player_marquee_".$device->code, ["message" => "check_marquee_update"]);
        }
        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
    }

    public function show(Request $request, Ad $ad): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Ad::with(['marquee'])->find($ad->id)
        ]);
    }

    public function update(AdUpdateRequest $request, Ad $ad): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        $ad->update($input);

        $marquee = Marquee::with('devices')->find($ad->marquee_id);
        foreach ($marquee->devices as $device) {
            $this->sendPublishMessage("player_marquee_".$device->code, ["message" => "check_marquee_update"]);
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

        $ads = DB::table('ads')->whereIn('id', $ids);
        $ads_aux = $ads->get();
        // delete records
        $deleted = $ads->delete();
        foreach ($ads_aux as $ad) {
            $marquee = Marquee::with('devices')->find($ad->marquee_id);
            foreach ($marquee->devices as $device) {
                $this->sendPublishMessage("player_marquee_".$device->code, ["message" => "check_marquee_update"]);
            }
        }

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
