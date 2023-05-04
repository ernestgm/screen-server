<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageStoreRequest;
use App\Http\Requests\ImageUpdateRequest;
use App\Models\Image;
use App\Models\Price;
use App\Models\Product;
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
            $all = Image::with(['products'])->where('screen_id', $screen)->get();
        }

        dd($all);
        return response()->json([
            'success' => true,
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
                    'description' => 'a',
                    'image_id' => $image->id
                ]);

                if ($_product->id) {
                    Price::create([
                        'value' => $product['price'],
                        'product_id' => $_product->id
                    ]);
                }
            }
        }

        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
    }

    public function show(Request $request, Image $image): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Image::with(['screen', 'products'])->find($image->id)
        ]);
    }

    public function update(ImageUpdateRequest $request, Image $image): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        $image->update($input);

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
        $deleted = DB::table('images')->whereIn('id', $ids)->delete();

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
