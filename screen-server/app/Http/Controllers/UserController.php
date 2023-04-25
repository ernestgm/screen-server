<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class UserController extends Controller
{

    public function create(Request $request): View
    {
        return view('user.create');
    }

    public function login(): JsonResponse
    {
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){
            $user = Auth::user();
            $success['user'] = User::with('role')->find($user->id);
            $success['token'] =  $user->createToken('screen_app')->plainTextToken;
            return response()->json(['success' => $success], app('SUCCESS_STATUS'));
        }
        else{
            return response()->json(['error'=>'Unauthorised'], app('UNAUTHORIZED_STATUS'));
        }
    }

    public function logout(): JsonResponse
    {
        Auth::user()->tokens()->delete();
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

    public function update(UserStoreRequest $request, User $user): JsonResponse
    {
        $request->validated();
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user->update($input);

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
        $users = User::with('role')->get()->filter(function($item) {
            return $item['id'] != Auth::id();
        });


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
