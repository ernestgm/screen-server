<?php

namespace App\Http\Controllers;

use App\Http\Requests\AreaStoreRequest;
use App\Http\Requests\AreaUpdateRequest;
use App\Models\Area;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $business = $request->input('business_id');
        $all = Area::with(['business'])->get();
        if ($business) {
            $all = Area::with(['business'])->where('business_id', $business)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $all
        ]);
    }

    public function store(AreaStoreRequest $request): JsonResponse
    {
        $area = Area::create($request->validated());

        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
    }

    public function show(Request $request, Area $area): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Area::with('screens')->find($area->id)
        ]);
    }

    public function update(AreaUpdateRequest $request, Area $area): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        $area->update($input);

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
