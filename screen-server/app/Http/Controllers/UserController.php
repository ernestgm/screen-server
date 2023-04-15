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

    public $successStatus = 200;
    public $unauthorisedStatus = 401;

    public function create(Request $request): View
    {
        return view('user.create');
    }

    public function login(): JsonResponse
    {
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){
            $user = Auth::user();
            $success['user'] = $user;
            $success['token'] =  $user->createToken('screen_app')->plainTextToken;
            return response()->json(['success' => $success], $this-> successStatus);
        }
        else{
            return response()->json(['error'=>'Unauthorised'], $this->unauthorisedStatus);
        }
    }

    public function logout(): JsonResponse
    {
        Auth::user()->tokens()->delete();
        return response()->json(['success' => 'success']);
    }

    public function store(UserStoreRequest $request)
    {
        $request->validated();
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        User::create($input);

        return response()->json(['success'=>'success'], $this->successStatus);
    }

    public function update(UserStoreRequest $request, User $user)
    {
        $request->validate();
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user->update($input);

        return response()->json(['success'=>'success'], $this->successStatus);
    }

    public function getUser(Request $request, User $user)
    {
        return response()->json([
            'success'=> true,
            'data' => $user
        ]);
    }

    public function show(Request $request, User $user)
    {
        $users = User::all()->filter(function($item) {
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
            return response()->json(['error' => $validator->errors()], 400);
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
