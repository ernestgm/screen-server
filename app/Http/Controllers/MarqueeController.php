<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarqueeStoreRequest;
use App\Http\Requests\MarqueeUpdateRequest;
use App\Models\Marquee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MarqueeController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $all = Marquee::with(['business', 'devices'])->get();
        return response()->json([
            'success' => true,
            'data' => $all
        ]);
    }

    public function store(MarqueeStoreRequest $request): JsonResponse
    {
        $request->validated();
        $inputs = $request->all();
        Marquee::create($inputs);
        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
    }

    public function show(Request $request, Marquee $marquee): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Marquee::with(['business', 'devices', 'ads'])->find($marquee->id)
        ]);
    }

    public function update(MarqueeUpdateRequest $request, Marquee $marquee): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        $marquee->update($input);

        $marquee = Marquee::with('devices')->find($marquee->id);
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

        $marquees = DB::table('marquees')->whereIn('id', $ids);

        // delete records
        $deleted = $marquees->delete();

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
