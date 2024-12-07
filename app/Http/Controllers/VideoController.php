<?php

namespace App\Http\Controllers;

use App\Helpers\VideoHelpers;
use App\Http\Requests\VideoStoreRequest;
use App\Http\Requests\VideoUpdateRequest;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends ImageController
{
    public function showVideo(Request $request, Image $video): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Image::with(['screen'])->find($video->id)
        ]);
    }

    public function storeVideo(VideoStoreRequest $request): JsonResponse
    {
        $file = $request->file('video');
        $videoHelper = new VideoHelpers($file);

        if (!$videoHelper->isDurationValid()) {
            return response()->json(['statusText' => "Video Not Valid. Duraction must be under 30s"], app('VALIDATION_STATUS'));
        }

        $filename = uniqid() . '.' . $file->getClientOriginalName();

        if (Storage::disk('ftp')->exists('/')) {
            $uploaded = Storage::disk('ftp')->put($filename, file_get_contents($file));
            if ($uploaded) {
                $inputs = $request->all();
                $screen_id = $inputs['screen_id'];
                $subfolder = "user_" . $this->getUserId($screen_id);
                $move = $this->moveFile($subfolder, $filename);
                if ($move) {
                    $duration = $videoHelper->getDuration();
                    $data = [
                        'name' => $filename,
                        'description' => '',
                        'description_position' => 'bc',
                        'qr_info' => '',
                        'image' => '',
                        'video' => env('URL_BASE_OF_VIDEO') . '/' . $subfolder . '/' . $filename,
                        'screen_id' => $screen_id,
                        'is_static' => 1,
                        'duration' => $duration,
                    ];
                    Image::create($data);
                    $this->updateScreens($screen_id);
                    return response()->json(['success' => 'success'], app('SUCCESS_STATUS'));
                } else {
                    return response()->json(['statusText' => "Error: Can't move video"], app('VALIDATION_STATUS'));
                }
            } else {
                return response()->json(['statusText' => "Can't upload video"], app('VALIDATION_STATUS'));
            }
        } else {
            return response()->json(['statusText' => "Can't connect to FTP server"], app('VALIDATION_STATUS'));
        }
    }

    public function updateVideo(VideoUpdateRequest $request, Image $video): JsonResponse
    {
        $file = $request->file('video');
        $videoHelper = new VideoHelpers($file);

        if (!$videoHelper->isDurationValid()) {
            return response()->json(['statusText' => "Video Not Valid. Duraction must be under 30s"], app('VALIDATION_STATUS'));
        }

        $filename = uniqid() . '.' . $file->getClientOriginalName();

        if (Storage::disk('ftp')->exists('/')) {
            $uploaded = Storage::disk('ftp')->put($filename, file_get_contents($file));
            if ($uploaded) {
                $screen_id = $request->input('screen_id');
                $subfolder = "user_" . $this->getUserId($screen_id);
                $move = $this->moveFile($subfolder, $filename);
                if ($move) {
                    $duration = $videoHelper->getDuration();
                    $data = [
                        'name' => $filename,
                        'video' => env('URL_BASE_OF_VIDEO') . '/' . $subfolder . '/' . $filename,
                        'duration' => $duration,
                    ];
                    $video->update($data);
                    $this->updateScreens($screen_id);
                    return response()->json(['success' => 'success'], app('SUCCESS_STATUS'));
                } else {
                    return response()->json(['statusText' => "Error: Can't upload video"], app('VALIDATION_STATUS'));
                }
            } else {
                return response()->json(['statusText' => "Can't upload video"], app('VALIDATION_STATUS'));
            }
        } else {
            return response()->json(['statusText' => "Can't connect to FTP server"], app('VALIDATION_STATUS'));
        }
    }

    private function moveFile($subfolder, $filename): bool
    {
        if (Storage::disk('ftp')->exists($subfolder)) {
            $move = Storage::disk('ftp')->move($filename, $subfolder . '/' . $filename);
        } else {
            if (Storage::disk('ftp')->makeDirectory($subfolder)) {
                $move = Storage::disk('ftp')->move($filename, $subfolder . '/' . $filename);
            } else {
                return false;
            }
        }

        return $move;
    }
}
