<?php

namespace App\Http\Controllers;

use App\Http\Requests\BussineStoreRequest;
use App\Models\Bussine;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BussineController extends Controller
{
    public function index(Request $request): View
    {
        $bussines = Bussine::all();

        return view('bussine.index', compact('bussines'));
    }

    public function create(Request $request): View
    {
        $user = User::find($id);

        return view('bussine.create', compact('user'));
    }

    public function store(BussineStoreRequest $request): RedirectResponse
    {
        $bussine = Bussine::create($request->validated());

        return redirect()->route('bussine.show', ['bussine' => $bussine]);
    }

    public function show(Request $request, Bussine $bussine)
    {
        $bussines = Bussine::all();

        return response()->json($bussines);
    }
}
