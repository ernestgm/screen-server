<?php

namespace App\Http\Controllers;

use App\Services\CentrifugueService;
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
        (new CentrifugueService())->sendPublishMessage($channel, $data);
    }
}
