<?php

use App\Http\Controllers\Api\BookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are assigned to the "api" middleware group.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    Route::get('/books', [BookController::class, 'index'])
        ->name('api.v1.books.index');

    Route::get('/books/{book}', [BookController::class, 'show'])
        ->name('api.v1.books.show');

    Route::post('/books', [BookController::class, 'store'])
        ->name('api.v1.books.store');

    Route::put('/books/{book}', [BookController::class, 'update'])
        ->name('api.v1.books.update');

    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->name('api.v1.books.destroy');
});
