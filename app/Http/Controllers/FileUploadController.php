<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    public function uploadFile(Request $request): JsonResponse
    {
        // Guardar el archivo en el almacenamiento local (o en S3, etc.)
        $file = $request->file('file');
        if ($file) {
            $name = $file->getClientOriginalName();
            $request->file('file')->storeAs('appLogs', $name,'public');
            return response()->json(['success' => true], 200);
        }

        return response()->json(['success' => false], 400);
    }

    public function listLogFiles(Request $request): JsonResponse
    {
        $logs = [];
        $files = Storage::files("public/appLogs/");
        foreach ($files as $file) {
            $f['name'] = $file;
            $logs[] = $f;
        }
        return response()->json(['files' => $logs], 200);
    }

    public function viewContentLogFile(Request $request): JsonResponse
    {
        $file = $request->get('file');
        $contents = Storage::get($file);
        return response()->json(['content' => $contents], 200);
    }
}
