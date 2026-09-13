<?php

use App\Http\Controllers\BeamMaterialCatalogController;
use App\Http\Controllers\BeamCalculationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'hello there',
    ]);
});

Route::get('/beam/material-catalog', BeamMaterialCatalogController::class);
Route::post('/beam/calculations', BeamCalculationController::class);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

require_once __DIR__.'/auth.php';
