<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use phpcent\Client;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function sendPublishMessage($channel, $data): void
    {
        try {
            $client = new Client(env('URL_BASE_OF_WS'));
            $client->setApiKey(env('WS_API_KEY'));
            $client->publish($channel, $data);
        } catch (Exception $e) {
            Log::error("Centrifugue: ".$e->getMessage());
        }
    }
}
