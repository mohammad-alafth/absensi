<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramBotService $botService): JsonResponse
    {
        $update = $request->all();
        $result = $botService->processUpdate($update);

        return response()->json($result);
    }
}
