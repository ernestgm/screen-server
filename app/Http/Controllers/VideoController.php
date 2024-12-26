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
            //$uploaded = Storage::disk('ftp')->put($filename, file_get_contents($file));
            $uploaded = $this->uploadToFtp($filename, $file, $request->get('channel_id'));
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
            $uploaded = $this->uploadToFtp($filename, $file, $request->get('channel_id'));
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

    private function uploadToFtp($filename, $file, $channelId): JsonResponse|bool
    {
        $tempPath = $file->store('temp');
        $localPath = storage_path("app/{$tempPath}");


        $fileSize = filesize($localPath);


        $ftpHost = env('FTP_HOST');
        $ftpUser = env('FTP_USER');
        $ftpPass = env('FTP_PASSWORD');
        $ftpRoot = env('FTP_ROOT');
        $remotePath = $ftpRoot . '/' . $filename;

        $ftpConnection = ftp_ssl_connect($ftpHost);
        if (!$ftpConnection) {
            return false;
        }

        $login = ftp_login($ftpConnection, $ftpUser, $ftpPass);
        if (!$login) {
            ftp_close($ftpConnection);
            return false;
        }

        ftp_pasv($ftpConnection, true); // Modo pasivo

        $stream = fopen($localPath, 'r');

        $upload = ftp_nb_fput($ftpConnection, $remotePath, $stream, FTP_BINARY);

        while ($upload === FTP_MOREDATA) {
            $uploaded = ftell($stream); // Calcular bytes subidos
            $progress = round(($uploaded / $fileSize) * 100);

            // Aquí puedes enviar el progreso al cliente (por ejemplo, con WebSockets o SSE)
            $this->sendPublishMessage("ftp_upload_progress_" . $channelId, ["progress" => $progress]);

            // Continuar la subida
            $upload = ftp_nb_continue($ftpConnection);
        }

        fclose($stream);
        ftp_close($ftpConnection);

        Storage::delete($tempPath);

        if ($upload === FTP_FINISHED) {
            return true;
        }

        return false;
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
