<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\Api\AuthController;

Route::get('test', function (Request $request) {
    return "success";
})->middleware(JwtMiddleware::class);

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
