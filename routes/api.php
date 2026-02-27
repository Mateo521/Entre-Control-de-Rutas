<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TollController;
use App\Http\Controllers\Api\IncidentController;

/*
|--------------------------------------------------------------------------
| rtas publicas
|--------------------------------------------------------------------------
| 
*/
Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| rtas protegidas
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {


    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Illuminate\Http\Request $request) {
        return $request->user();
    });

    Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);

    Route::apiResource('incidents', IncidentController::class);


    Route::apiResource('tolls', TollController::class);
    Route::apiResource('incidents', IncidentController::class);

    Route::apiResource('actions', \App\Http\Controllers\ActionController::class);

});