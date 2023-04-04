<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function show(Request $request, User $user)
    {
        $users = User::all();

        return response()->json($users);
    }
}
