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
        $request->validated();
        $inputs = $request->all();
        $newImage = $this->compressImage($inputs['image']);
        $inputs['image'] = $newImage;

        $image = Image::create($inputs);
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
        $request->validated();
        $input = $request->all();
        $newImage = $this->compressImage($input['image']);
        $input['image'] = $newImage;
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

    private function compressImage($base64_string)
    {
// Identificar el encabezado
        $header = '';
        if (strpos($base64_string, 'base64,') !== false) {
            list($header, $base64_string) = explode('base64,', $base64_string);
            $header .= 'base64,';
        }

// Decodificar la cadena base64
        $image_data = base64_decode($base64_string);

// Verificar si la decodificación fue exitosa
        if ($image_data === false) {
            die("No se pudo decodificar la cadena base64");
        }

// Crear una imagen a partir del string decodificado
        $image = imagecreatefromstring($image_data);
        if ($image === false) {
            die("No se pudo crear la imagen desde el string base64, formato no reconocido.");
        }

// Paso 2: Redimensionar y recomprimir la imagen
        $original_width = imagesx($image);
        $original_height = imagesy($image);
        $new_width = $original_width; // Ajusta según sea necesario
        $new_height = $original_height; // Ajusta según sea necesario

        $new_image = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

        $quality = 80; // Ajusta la calidad para mayor o menor compresión

// Guardar la imagen comprimida en un buffer
        ob_start();
        imagejpeg($new_image, null, $quality);
        $compressed_image_data = ob_get_clean();

// Limpiar la memoria
        imagedestroy($image);
        imagedestroy($new_image);

// Paso 3: Reconvertir la imagen comprimida a base64
        $compressed_base64_string = base64_encode($compressed_image_data);

// Paso 4: Agregar de nuevo el encabezado
        // Resultado final
        return $header . $compressed_base64_string;
    }
}
