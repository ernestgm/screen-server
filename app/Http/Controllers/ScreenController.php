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
        $id = $request->input('area_id');
        $all = Screen::with(['area.business.user'])->get();
        if ($id) {
            $all = Screen::with(['area.business.user'])->where('area_id', $id)->get();
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
            'data' => Screen::with(['area.business.user'])->find($screen->id)
        ]);
    }

    public function byCode(Request $request): JsonResponse {
        $enabled = false;
        $screen = Screen::all()->where('code', $request->query('code'))->first();
        if ($screen) {
            $enabled = $screen->enabled == 1;
        }
        return response()->json([
            'success' => $screen != null,
            'enabled' => $enabled
        ]);
    }

    public function update(ScreenUpdateRequest $request, Screen $screen): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        $old_code = $screen->code;
        $screen->update($input);
        if (key_exists('code', $input)) {
            $updatedCode = $input['code'] != $old_code;
            $code = $input['code'];
            if ($updatedCode) {
                $this->sendPublishMessage("home_screen_$old_code", ["message" => "check_screen_update"]);
                $this->sendPublishMessage("home_screen_$code", ["message" => "check_screen_update"]);

                $this->sendPublishMessage("player_screen_$old_code", ["message" => "check_screen_update"]);
                $this->sendPublishMessage("player_screen_$code", ["message" => "check_screen_update"]);
            }
        } else {
            $this->sendPublishMessage("home_screen_".$screen->code, ["message" => "check_screen_update"]);
            $this->sendPublishMessage("player_screen_".$screen->code, ["message" => "check_screen_update"]);
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
