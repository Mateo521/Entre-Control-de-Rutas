<?php

use Illuminate\Support\Facades\Route;

// Cualquier URL ingresada en el navegador cargará la vista 'welcome'
// y delegará la navegación a Vue Router.
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');