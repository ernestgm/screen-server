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
        $image = Image::create($request->validated());
        if ($image->id) {
            foreach ($request->get('products') as $product) {
                $_product = Product::create([
                    'name' => $product['name'],
                    'description' => $product['description'],
                    'image_id' => $image->id,
                ]);

                if ($_product->id) {
                    Price::create([
                        'value' => $product['price'],
                        'product_id' => $_product->id
                    ]);
                }
            }
            $this->updateScreen($request->input('screen_id'));
        }

        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
    }

    private function updateScreen($screenId): void
    {
        $screen = Screen::find($screenId);
        $screen->touch();
        $this->sendPublishMessage("player_images_".$screen->code, ["message" => "check_images_update"]);
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
        $request->validated();
        $input = $request->all();
        $image->update($input);
        $this->updateScreen($request->input('screen_id'));

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

        $images = DB::table('images')->whereIn('id', $ids);
        $this->updateScreen($images->first()->screen_id);

        // delete records
        $deleted = $images->delete();

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
