<?php
use Illuminate\Support\Facades\Route;

// Public routes (không cần authentication)
// Route::prefix('api/public')->name('public.')->group(function () {
//     // SePay Webhook - URL chuẩn cho webhook
//     Route::post('/webhook/sepay', [\App\Http\Controllers\Api\SePayWebhookController::class, 'handleWebhook'])
//         ->name('webhook.sepay');
//
//     // Public payment info endpoint
//     Route::get('/payment/{paymentId}', [\App\Http\Controllers\PublicPaymentController::class, 'getPaymentInfo'])
//         ->name('payment.info');
// });



// Debug route for testing API controller
Route::get('/debug-api', function () {
    $searchTerm = request('search', 'uyên');

    try {
        $request = new \Illuminate\Http\Request([
            'search' => $searchTerm,
            'page' => 1,
            'per_page' => 5
        ]);

        $controller = app('App\Http\Controllers\Api\StudentController');
        $response = $controller->index($request);
        $data = $response->getData();

        return response()->json([
            'search_term' => $searchTerm,
            'total_found' => $data->total ?? 0,
            'returned_count' => count($data->data ?? []),
            'first_student' => $data->data[0] ?? null,
            'pagination' => [
                'current_page' => $data->current_page ?? null,
                'per_page' => $data->per_page ?? null,
                'last_page' => $data->last_page ?? null,
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});