<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatusFactory;
use Illuminate\Http\JsonResponse;

/**
 * StatusController - API Controller cho status definitions
 * Tuân thủ Single Responsibility Principle
 */
class StatusController extends Controller
{
    /**
     * Lấy tất cả status definitions cho JavaScript
     */
    public function definitions(): JsonResponse
    {
        $definitions = [];
        
        $types = StatusFactory::getAvailableTypes();
        
        foreach ($types as $type) {
            $definitions[$type] = StatusFactory::createJavaScriptCollection($type);
        }
        
        return response()->json([
            'success' => true,
            'definitions' => $definitions
        ]);
    }

    /**
     * Lấy options cho một loại status cụ thể
     */
    public function options(string $type): JsonResponse
    {
        $options = StatusFactory::getOptions($type);
        
        if (empty($options)) {
            return response()->json([
                'success' => false,
                'message' => 'Status type không tồn tại'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'type' => $type,
            'options' => $options
        ]);
    }

    /**
     * Tạo badge HTML cho status
     */
    public function badge(string $type, string $value): JsonResponse
    {
        $badge = StatusFactory::createBadge($type, $value);
        
        return response()->json([
            'success' => true,
            'badge' => $badge
        ]);
    }

    /**
     * Validate status value
     */
    public function validate(string $type, string $value): JsonResponse
    {
        $isValid = StatusFactory::isValid($type, $value);
        
        return response()->json([
            'success' => true,
            'valid' => $isValid
        ]);
    }
}
