<?php

namespace App\Http\Controllers;

use App\Http\Requests\AreaStoreRequest;
use App\Http\Requests\AreaUpdateRequest;
use App\Models\Area;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $business = $request->input('business_id');
        $all = Area::with(['business'])->get();
        if ($business) {
            $all = Area::with(['business'])->where('business_id', $business)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $all
        ]);
    }

    public function store(AreaStoreRequest $request): JsonResponse
    {
        $area = Area::create($request->validated());

        return response()->json(['success'=>'success'], app('SUCCESS_STATUS'));
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
