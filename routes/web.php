<?php

use Illuminate\Support\Facades\Route;

// Redirigir todas las rutas a la vista de Vue, EXCEPTO las que comienzan con 'api'
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');