<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $image = $request->input('image_id');
        $all = Product::with(['prices'])->get();
        if ($image) {
            $all = Product::with(['prices'])->where('image_id', $image)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $all
        ]);
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());
        if ($product->id) {
            Price::create([
                'value' => $request['price'],
                'product_id' => $product->id
            ]);
        }

        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Product::with(['prices'])->find($product->id)
        ]);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        $product->update($input);

        $last_price = $product->prices()->get()->last();
        if ($last_price->value != $input['price']) {
            Price::create([
                'value' => $input['price'],
                'product_id' => $product->id
            ]);
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

        // delete records
        $deleted = DB::table('products')->whereIn('id', $ids)->delete();

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
