<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageStoreRequest;
use App\Http\Requests\ImageUpdateRequest;
use App\Models\Image;
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
        $newImage = $this->compressImage($inputs['image']);
        $inputs['image'] = $newImage;

        $image = Image::create($inputs);
        if ($image->id) {
//            $products = json_decode($request->get('products'), true);
//            foreach ($products as $product) {
//                $_product = Product::create([
//                    'name' => $product['name'],
//                    'description' => $product['description'],
//                    'image_id' => $image->id,
//                ]);
//
//                if ($_product->id) {
//                    Price::create([
//                        'value' => $product['price'],
//                        'product_id' => $_product->id
//                    ]);
//                }
//            }
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
        $imageFile = $input['image'];

        if ($imageFile instanceof UploadedFile && $imageFile->isFile()) {
            $newImage = $this->compressImage($imageFile);
            $input['image'] = $newImage;
        }

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
        $this->updateScreens($images->first()->screen_id);

        // delete records
        $deleted = $images->delete();

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }

    private function compressImage($image): string
    {
        ini_set('memory_limit', '256M');
        // Use Intervention Image to create an image instance
        $manager = new ImageManager(Driver::class);
        $image = $manager->read($image);

        return $image->toJpeg(70)->toDataUri();
    }
}
