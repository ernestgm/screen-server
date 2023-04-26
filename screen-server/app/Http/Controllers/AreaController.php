<?php

namespace App\Http\Controllers;

use App\Http\Requests\AreaStoreRequest;
use App\Http\Requests\AreaUpdateRequest;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function all(Request $request): View
    {
        $areas = Area::all();

        return view('areas.index', compact('area'));
    }

    public function store(AreaStoreRequest $request): RedirectResponse
    {
        $area = Area::create($request->validated());

        return redirect()->route('area.show', ['area' => $area]);
    }

    public function show(Request $request, Area $area): View
    {
        $areas = Area::all();

        return view('area.show', compact('area'));
    }

    public function update(AreaUpdateRequest $request, Area $area): RedirectResponse
    {
        $area->update($request->validated());
        return redirect()->route('area.index');
    }

    public function delete(Request $request, Area $area): RedirectResponse
    {
        $area->delete();
        return redirect()->route('area.index');
    }
}
