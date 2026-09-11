<?php

use App\Http\Controllers\BeamMaterialCatalogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'hello there',
    ]);
});

Route::get('/beam/material-catalog', BeamMaterialCatalogController::class);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

require_once __DIR__.'/auth.php';
