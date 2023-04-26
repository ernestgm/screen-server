<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessStoreRequest;
use App\Models\Business;
use App\Models\GeoLocation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class BusinessController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Business::all()
        ]);
    }

    public function update(BusinessStoreRequest $request, Business $business): JsonResponse
    {
        $request->validated();
        $input = $request->all();

        $geolocationInput = [
            'address' => $input['address'],
            'latitude' => $input['latitude'],
            'longitude' => $input['longitude']
        ];

        $geolocation = $business->getGeoLocation();
        $geolocation->update($geolocationInput);
        $business->update($input);

        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
    }

    public function store(BusinessStoreRequest $request): JsonResponse
    {
        $request->validated();
        $input = $request->all();

        $geolocationInput = [
            'address' => $input['address'],
            'latitude' => $input['latitude'],
            'longitude' => $input['longitude']
        ];
        $geolocation = GeoLocation::create($geolocationInput);

        if ($geolocation->id) {
            $businessInput = [
                'name' => $input['name'],
                'description' => $input['description'],
                'logo' => $input['logo'],
                'user_id' => $input['user_id'],
                'geolocation_id' => $geolocation->id,
            ];

            Business::create($businessInput);

            return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
        }

        return response()->json(['success'=>'false'], app('VALIDATION_STATUS'));
    }

    public function show(Request $request, Business $business): JsonResponse
    {
        return response()->json([
            'success'=> true,
            'data' => Business::with(['user', 'geolocation'])->find($business->id)
        ]);
    }

    public function delete(Request $request)
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
        $deleted = DB::table('businesses')->whereIn('id', $ids)->delete();

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
