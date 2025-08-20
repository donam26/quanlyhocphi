<?php

namespace App\Http\Controllers\Api;

use App\Services\WebhookService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class SePayWebhookController extends Controller
{
    protected $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Xử lý webhook từ SePay - Version đơn giản
     */
    public function handleWebhook(Request $request)
    {
        Log::info('SePay Webhook received', $request->all());

        $result = $this->webhookService->processSePay($request->all());

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'payment_id' => $result['payment_id'] ?? null
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);
        }
    }
}