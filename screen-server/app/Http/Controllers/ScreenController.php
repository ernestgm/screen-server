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
        $all = Screen::with(['area'])->get();
        if ($id) {
            $all = Screen::with(['area'])->where('area_id', $id)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $all
        ]);
    }

    public function store(ScreenStoreRequest $request): JsonResponse
    {
        Screen::create($request->validated());
        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
    }

    public function show(Request $request, Screen $screen): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $screen
        ]);
    }

    public function update(ScreenUpdateRequest $request, Screen $screen): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        $screen->update($input);

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
        $deleted = DB::table('areas')->whereIn('id', $ids)->delete();

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
