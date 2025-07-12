<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CourseItemController;
use App\Http\Controllers\Api\ClassesController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/healthy', function () {
    return response()->json(['message' => 'API is running']);
});

// API cho cấu trúc cây khóa học mới
Route::get('/course-items', [CourseItemController::class, 'index']);
Route::post('/course-items', [CourseItemController::class, 'store']);
Route::get('/course-items/{id}', [CourseItemController::class, 'show']);
Route::put('/course-items/{id}', [CourseItemController::class, 'update']);
Route::delete('/course-items/{id}', [CourseItemController::class, 'destroy']);
Route::get('/tree', [CourseItemController::class, 'tree']);

// API cho lớp học
Route::get('/classes', [ClassesController::class, 'index']);
Route::post('/classes', [ClassesController::class, 'store']);
Route::get('/classes/{id}', [ClassesController::class, 'show']);
Route::put('/classes/{id}', [ClassesController::class, 'update']);
Route::delete('/classes/{id}', [ClassesController::class, 'destroy']);

