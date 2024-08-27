<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageStoreRequest;
use App\Http\Requests\ImageUpdateRequest;
use App\Models\Media;
use App\Models\Price;
use App\Models\Product;
use App\Models\Screen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class MediaController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $screen = $request->input('screen_id');
        $all = Media::with(['screen'])->get();
        if ($screen) {
            $all = Media::with(['products.prices'])->where('screen_id', $screen)->get();
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
            $all = Media::with(['products.prices'])->where('screen_id', $screen->id)->get();
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
        $media = Media::create($inputs);
        if ($media->id) {
            $this->updateScreens($request->input('screen_id'));
        }

        return response()->json(['success' => 'success'], app('SUCCESS_STATUS'));
    }

    private function updateScreens($screenId): void
    {
        $screen = Screen::with('devices')->find($screenId);
        if ($screen && $screen->devices) {
            foreach ($screen->devices as $device) {
                $this->sendPublishMessage("player_images_" . $device->code, ["message" => "check_images_update"]);
            }
        }
    }

    public function show(Request $request, Media $image): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Media::with(['screen', 'products.prices'])->find($image->id)
        ]);
    }

    public function update(ImageUpdateRequest $request, Media $media): JsonResponse
    {
        $input = $request->all();
        $media->update($input);
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

        $medias = DB::table('medias')->whereIn('id', $ids);

        $images_aux = $medias->get();

        // delete records
        $deleted = $medias->delete();

        $this->updateScreens($images_aux->first()->screen_id);


        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
