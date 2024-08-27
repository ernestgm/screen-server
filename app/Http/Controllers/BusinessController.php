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
        $userId = $request->query('userId');
        if ($userId != null) {
            $bussines = Business::where('user_id', $userId)->get();
        } else {
            $bussines = Business::all();
        }
        return response()->json([
            'success' => true,
            'data' => $bussines
        ]);
    }

    public function update(BusinessStoreRequest $request, Business $business): JsonResponse
    {
        $request->validated();
        $input = $request->all();


        if ($input['address'] != "") {
            $geolocationInput = [
                'address' => $input['address'],
                'latitude' => $input['latitude'],
                'longitude' => $input['longitude']
            ];
            $geolocation = $business->geolocation();
            if ($geolocation->exists()) {
                $geolocation->update($geolocationInput);
            } else {
                $geolocationInput['business_id'] = $business->id;
                GeoLocation::create($geolocationInput);
            }
        }
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
            if ($input['address'] != "") {
                $geolocationInput = [
                    'address' => $input['address'],
                    'latitude' => $input['latitude'],
                    'longitude' => $input['longitude'],
                    'business_id' => $business->id
                ];

                GeoLocation::create($geolocationInput);
            }

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

    public function getResumeByUserId(Request $request): JsonResponse {
        $userId = $request->query('userId');

        $bussinesCount = 0;
        $screenCount = 0;
        $imagesCount = 0;

        if ($userId === null) {
            $businesses = Business::with('screens.medias.products.prices')->get()->toArray();
        } else {
            $businesses = Business::with('screens.medias.products.prices')->where('user_id', $userId)->get()->toArray();
        }

        $bussinesCount = count($businesses);
        foreach ($businesses as $business) {
            $screenCount = $screenCount + count($business['screens']);
            foreach ($business['screens'] as $screen) {
                $imagesCount = $imagesCount + count($screen['medias']);
            }
        }

        return response()->json([
                'bussines' => $bussinesCount,
                'screens' => $screenCount,
                'medias' => $imagesCount,
            ]
        );
    }

    public function findRoute(Request $request): JsonResponse {
        $id = (int) $request->get('id');
        $field = $request->get('field');
        $attr = $request->get('attr');

        $businesses = Business::with('areas.screens.medias.products.prices')->get()->toArray();
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
