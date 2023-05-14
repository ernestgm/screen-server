<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessStoreRequest;
use App\Models\Business;
use App\Models\GeoLocation;
use App\Models\User;
use Faker\Core\Number;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use function Webmozart\Assert\Tests\StaticAnalysis\integer;


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

        $geolocation = $business->geolocation();
        $geolocation->update($geolocationInput);
        $business->update($input);

        return response()->json(['success' => 'success'], app('SUCCESS_STATUS'));
    }

    public function store(BusinessStoreRequest $request): JsonResponse
    {
        $request->validated();
        $input = $request->all();


        $businessInput = [
            'name' => $input['name'],
            'description' => $input['description'],
            'logo' => $input['logo'],
            'user_id' => $input['user_id'],
        ];

        $business = Business::create($businessInput);


        if ($business->id) {
            $geolocationInput = [
                'address' => $input['address'],
                'latitude' => $input['latitude'],
                'longitude' => $input['longitude'],
                'business_id' => $business->id
            ];

            GeoLocation::create($geolocationInput);
            return response()->json(['success' => 'success'], app('SUCCESS_STATUS'));
        }

        return response()->json(['success' => 'false'], app('VALIDATION_STATUS'));
    }

    public function show(Request $request, Business $business): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Business::with(['user', 'geolocation'])->find($business->id)
        ]);
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
        $deleted = DB::table('businesses')->whereIn('id', $ids)->delete();

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }

    public function generateJson(Business $business): JsonResponse {
        $_business = Business::with('areas.screens.images.products.prices')->find($business->id);

        $_toarray = $_business->toArray();
        unset($_toarray['created_at']);
        unset($_toarray['updated_at']);

        $_toarray['areas'] = array_map(function ($area) {
            unset($area['created_at']);
            unset($area['updated_at']);
            $area['screens'] = array_map(function ($screen) {
                unset($screen['created_at']);
                unset($screen['updated_at']);
                $screen['images'] = array_map(function ($image) {
                    unset($image['created_at']);
                    unset($image['updated_at']);
                    $image['products'] = array_map(function ($product) {
                        unset($product['created_at']);
                        unset($product['updated_at']);
                        $product['prices'] = array_map(function ($price) {
                            unset($price['created_at']);
                            unset($price['updated_at']);
                            return $price;
                        }, $product['prices']);
                        return $product;
                    }, $image['products']);
                    return $image;
                }, $screen['images']);
                return $screen;
            }, $area['screens']);
            return $area;
        }, $_toarray['areas']);

        $dir = '/screen-server/screen-server/public/jsons/';
        $basename = strtolower($business->name).'_'.$business->id.'_file.json';
        $json = json_encode($_toarray);
        $success = Storage::disk('ftp')->put($dir.$basename, $json);

        $response = [
            'success' => true,
            'json_url' => env('URL_BASE_OF_JSON').$basename
        ];

        if (!$success) {
            $response = [
                'success' => false,
                'error' => 'Could not generate the corresponding JSON'
            ];
        }

        return response()->json($response);
    }

    public function findRoute(Request $request): JsonResponse {
        $id = (int) $request->get('id');
        $field = $request->get('field');
        $attr = $request->get('attr');

        $businesses = Business::with('areas.screens.images.products.prices')->get()->toArray();
        $routes = '';
        foreach ($businesses as $index => $business) {
            $route = 'object';
            $routes = $this->findRouteAttr($business, $field, $attr, $id, $route);
            if ($routes != '') {
                break;
            }
        }

        return response()->json([
            'success' => true,
            'route' => $routes
        ]);
    }

    private function findRouteAttr($_array, $field, $attr, $id, $route = '') {
        //dd(count($_array));
        if (count($_array) > 0) {
            if (array_key_exists($field, $_array)) {
                foreach ($_array[$field] as $idx => $value) {
                    if ($value['id'] === $id) {
                        $route = $route.'['.$field.']'.'['.$idx.'].'.$attr;
                        return $route;
                    }
                }
                return '';
            } else {
                $keys = array_keys($_array);
                foreach ($keys as $index => $k) {
                    if (is_array($_array[$k])) {
                        $route = $route.'['.$k.']';
                        return $this->findRouteAttr($_array[$k], $field, $attr, $id, $route);
                    }
                }
            }
        }

        return $route;
    }
}
