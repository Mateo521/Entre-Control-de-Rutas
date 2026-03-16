<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TollController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\ContractorWorkController;


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

    Route::post('/tolls/{id}/image', [App\Http\Controllers\Api\TollController::class, 'uploadImage']);

    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::post('/inventory', [InventoryController::class, 'store']);
    Route::put('/inventory/{id}', [InventoryController::class, 'update']);
    Route::delete('/inventory/{id}', [InventoryController::class, 'destroy']);

    Route::post('/inventory/movements', [InventoryController::class, 'storeMovement']);


    Route::apiResource('contractors', ContractorWorkController::class);

    Route::get('/notes/alerts', [App\Http\Controllers\Api\NoteController::class, 'alerts']);
    Route::patch('/notes/{note}/status', [App\Http\Controllers\Api\NoteController::class, 'changeStatus']);
    Route::apiResource('notes', App\Http\Controllers\Api\NoteController::class);

 

    Route::apiResource('work-types', App\Http\Controllers\Api\WorkTypeController::class)->only(['index', 'store']);

    Route::post('/contractors/{id}/restore', [App\Http\Controllers\ContractorWorkController::class, 'restore']);

    Route::apiResource('tow-services', App\Http\Controllers\Api\TowServiceController::class);
    Route::post('/tow-services/{id}/restore', [App\Http\Controllers\Api\TowServiceController::class, 'restore']);  

    Route::post('/actions/{id}/restore', [\App\Http\Controllers\ActionController::class, 'restore']);

});