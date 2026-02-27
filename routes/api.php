<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TollController;
use App\Http\Controllers\Api\IncidentController;
// Aquí importaremos los demás controladores (TollController, IncidentController, etc.) más adelante

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
| No requieren token de autenticación.
*/
Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| Rutas Protegidas
|--------------------------------------------------------------------------
| Requieren enviar el 'access_token' en los headers de la petición HTTP.
| Header -> Authorization: Bearer {tu_token_aqui}
*/
Route::middleware('auth:sanctum')->group(function () {
    
    // Ruta para cerrar sesión
    Route::post('/logout', [AuthController::class, 'logout']);

    // Ruta de prueba para verificar quién está logueado
    Route::get('/user', function (Illuminate\Http\Request $request) {
        return $request->user();
    });

    Route::get('/dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);

    Route::apiResource('incidents', IncidentController::class);

  
    Route::apiResource('tolls', TollController::class);
    Route::apiResource('incidents', IncidentController::class);
});