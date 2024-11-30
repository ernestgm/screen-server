<?php

namespace App\Http\Controllers;

use App\Http\Requests\QrStoreRequest;
use App\Http\Requests\QrUpdateRequest;
use App\Models\Qr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QrController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $all = Qr::with(['business', 'devices'])->get();
        return response()->json([
            'success' => true,
            'data' => $all
        ]);
    }

    public function store(QrStoreRequest $request): JsonResponse
    {
        Qr::create($request->validated());
        return response()->json(['success' => 'success'], app('SUCCESS_STATUS'));
    }

    public function show(Request $request, Qr $qr): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Qr::with(['business', 'devices'])->find($qr->id)
        ]);
    }

    public function update(QrUpdateRequest $request, Qr $qr): JsonResponse
    {
        $qr->update($request->validated());
        $qr = Qr::with('devices')->find($qr->id);
        foreach ($qr->devices as $device) {
            $this->sendPublishMessage("player_qr_" . $device->code, ["message" => "check_qr_update"]);
        }

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

        $marquees = DB::table('qrs')->whereIn('id', $ids);

        // delete records
        $deleted = $marquees->delete();

        return response()->json([
                'success' => true,
                'message' => "$deleted record(s) deleted."
            ]
        );
    }
}
