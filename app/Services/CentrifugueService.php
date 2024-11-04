<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use phpcent\Client;

class CentrifugueService
{
    public function sendPublishMessage($channel, $data): void
    {
        try {
            $client = new Client(env('URL_BASE_OF_WS'));
            $client->setApiKey(env('WS_API_KEY'));
            $client->publish($channel, $data);
        } catch (Exception $e) {
            Log::error("Centrifugue: " . $e->getMessage());
        }
    }
}
