<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes v1
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix("/auth")->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post("/register", "register")->name("user.register");
        Route::post("/login", "login")->name("user.login");
        Route::get("/verify/{token}", "verify")->name("user.email.verify");
        Route::get("/send-email-to-verify", "sendVerify")->name("user.email.sendVerify")->middleware(["auth:sanctum", "throttle:3,1"]);
        Route::post("forgot-password", "forgotPassword")->name("user.forgotPassword");
        Route::post("/reset-password/{token}", "resetPassword")->name("user.resetPassword");
    });
});
