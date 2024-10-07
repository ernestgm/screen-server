<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\Device;
use App\Models\LoginCode;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use phpcent\Client;

class UserController extends Controller
{

    public function create(Request $request): View
    {
        return view('user.create');
    }

    public function login(): JsonResponse
    {
        if(Auth::attempt(['email' => request('email'), 'password' => request('password'), 'enabled' => 1])){
            $user = Auth::user();
            $success['user'] = User::with('role')->find($user->id);
            $success['token'] =  $user->createToken('screen_app')->plainTextToken;

            $refreshToken = $user->refresh_token;
            if ($refreshToken == null) {
                $refreshToken = hash('sha256', Str::random(60));
                $user->update(['refresh_token' => $refreshToken]);
            }

            $payload = [
                'sub' => (string)$user->id, // Subject of the token (the user ID)
                //"permissions" => ["presence:status:appOnline"]
            ];

            $jwt = JWT::encode($payload, env('WS_SECRET'), 'HS256');

            $success['refresh_token'] =  $refreshToken;
            $success['ws_token'] =  $jwt;

            return response()->json(['success' => $success], app('SUCCESS_STATUS'));
        }
        else {
            return response()->json(['error'=>'Unauthorised'], app('UNAUTHORIZED_STATUS'));
        }
    }

    public function generateLoginCode(Request $request): JsonResponse
    {
        $code = rand(10000000, 99999999);
        $deviceId = $request->input('deviceId');

        $data['code'] = $code;
        $data['device_id'] = $deviceId;

        $model = LoginCode::create($data);

        if ($model) {
            return response()->json(['code' => $code]);
        }

        // Generate an 8-digit code
        // Save the code in the database with a timestamp
        // Optionally, associate it with the device identifier
        return response()->json(['code' => null]);
    }

    public function activateDevice(Request $request): JsonResponse
    {
        $code = $request->input('code');
        // Lookup the code in the database
        $device = LoginCode::where('code', $code)->first();

        if ($device) {
            $user = Auth::user();
            $device->user_id = $user->id;
            $device->save();
            $this->sendPublishMessage("link_device_" . $device->device_id, ["message" => "login_by_code"]);

            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid code']);
        }
    }

    public function loginWithCode(Request $request): JsonResponse
    {
        $code = $request->input('code');
        $deviceId = $request->input('deviceId');
        $device = LoginCode::with('user')->where('code', $code)->where('device_id', $deviceId)->first();

        if ($device) {
            $user = $device->user;
            if ($user){
                $device->delete();
                $success['user'] = $user;
                $success['token'] =  $user->createToken('screen_app')->plainTextToken;

                $refreshToken = $user->refresh_token;
                if ($refreshToken == null) {
                    $refreshToken = hash('sha256', Str::random(60));
                    $user->update(['refresh_token' => $refreshToken]);
                }
                $success['refresh_token'] =  $refreshToken;

                return response()->json(['success' => $success], app('SUCCESS_STATUS'));
            }
        }

        return response()->json(['error'=>'Unauthorised'], app('UNAUTHORIZED_STATUS'));
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => 'required',
        ]);
        $input = $request->all();
        $hashedToken = $input['refresh_token'];
        $user = User::where('refresh_token', $hashedToken)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid refresh token'], app('UNAUTHORIZED_STATUS'));
        }

        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        $token = $user->createToken('screen_app')->plainTextToken;
        return response()->json(['token' => $token], app('SUCCESS_STATUS'));
    }

    public function logout(): JsonResponse
    {
        Auth::user()->currentAccessToken()->delete();
        return response()->json(['success' => 'success']);
    }

    public function store(UserStoreRequest $request): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        User::create($input);

        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
    }

    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $input = $request->all();
        $request->validated();
        if (key_exists('password', $input)) {
            $input['password'] = bcrypt($input['password']);
        }
        $user->update($input);


        if ( $input['enabled'] == 0) {
            $devices = Device::where('user_id', $user->id)->get();
            foreach ($devices as $device) {
                $this->sendPublishMessage("user_" . $device->code, ["message" => "logout"]);
            }
        }

        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
    }

    public function show(Request $request, User $user): JsonResponse
    {
        return response()->json([
            'success'=> true,
            'data' => User::with('role')->find($user->id)
        ]);
    }

    public function all(Request $request, User $user): JsonResponse
    {
        $users = User::with('role')->get();

        return response()->json([
            'success'=> true,
            'data' => $users->all()
        ]);
    }

    public function deleteByIds(Request $request)
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
        $deleted = DB::table('users')->whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
