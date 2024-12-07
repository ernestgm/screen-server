<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageStoreRequest;
use App\Http\Requests\ImageUpdateRequest;
use App\Models\Image;
use App\Models\Screen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImageController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $screen = $request->input('screen_id');
        $all = Image::with(['screen'])->get();
        if ($screen) {
            $all = Image::with(['products.prices'])->where('screen_id', $screen)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $all
        ]);
    }

    public function allByDeviceCode(Request $request): JsonResponse
    {
        $code = $request->input('code');
        $screen = DB::table("screens")->where('code', $code)->get()->first();
        $all = array();
        if ($screen != null) {
            $all = Image::with(['products.prices'])->where('screen_id', $screen->id)->get();
        }

        return response()->json([
            'success' => true,
            'screen_updated_at' => $screen->updated_at,
            'data' => $all
        ]);
    }

    public function store(ImageStoreRequest $request): JsonResponse
    {
        $request->validated();
        $inputs = $request->all();

        $screen_id = $inputs['screen_id'];
        $duration = $inputs['duration'];
        $images = $inputs['images'];
        foreach ($images as $image) {
            $data = [
                'name' => $image['name'],
                'description' => '',
                'description_position' => 'none',
                'description_size' => 'none',
                'qr_info' => '',
                'qr_position' => 'br',
                'image' => $image['data'],
                'screen_id' => $screen_id,
                'is_static' => 1,
                'duration' => $duration,
            ];
            Image::create($data);
        }

        $this->updateScreens($screen_id);

        return response()->json(['success' => 'success'], app('SUCCESS_STATUS'));
    }

    public function updateScreens($screenId): void
    {
        $screen = Screen::with('devices')->find($screenId);
        if ($screen && $screen->devices) {
            foreach ($screen->devices as $device) {
                $this->sendPublishMessage("player_images_" . $device->code, ["message" => "check_images_update"]);
            }
        }
    }

    public function getUserId($screenId): int|bool
    {
        $screen = Screen::with('business.user')->find($screenId);
        if ($screen && $screen->business && $screen->business->user) {
            return $screen->business->user->id;
        }

        return false;
    }

    public function show(Request $request, Image $image): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Image::with(['screen', 'products.prices'])->find($image->id)
        ]);
    }

    public function update(ImageUpdateRequest $request, Image $image): JsonResponse
    {
        $input = $request->all();
        $image->update($input);
        $this->updateScreens($request->input('screen_id'));

        return response()->json(['success' => 'success'], app('SUCCESS_STATUS'));
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

        $images = DB::table('images')->whereIn('id', $ids);

        $images_aux = $images->get();

        // delete records
        $deleted = $images->delete();

        $this->updateScreens($images_aux->first()->screen_id);


        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
