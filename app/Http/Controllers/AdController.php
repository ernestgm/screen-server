<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdStoreRequest;
use App\Http\Requests\AdUpdateRequest;

use App\Models\Ad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $all = Ad::all();
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

        // delete records
        $deleted = $ads->delete();

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
