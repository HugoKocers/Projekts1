<?php

use App\Http\Controllers\Admin\GameContentController;
use App\Http\Controllers\GameApiController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $frontendPath = public_path('spa/index.html');

    abort_unless(is_file($frontendPath), 503, 'The game website has not been built yet.');

    return response()->file($frontendPath);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('game-api')->group(function () {
    Route::get('/bootstrap', [GameApiController::class, 'bootstrap']);
    Route::post('/register', [GameApiController::class, 'register'])->middleware(['guest', 'throttle:6,1']);
    Route::post('/login', [GameApiController::class, 'login'])->middleware(['guest', 'throttle:6,1']);
    Route::post('/logout', [GameApiController::class, 'logout'])->middleware('auth');

    Route::middleware('auth')->group(function () {
        Route::get('/sessions', [GameApiController::class, 'indexSessions']);
        Route::post('/sessions', [GameApiController::class, 'storeSession']);
        Route::get('/sessions/{gameSession}', [GameApiController::class, 'showSession']);
        Route::put('/sessions/{gameSession}', [GameApiController::class, 'updateSession']);

        Route::prefix('admin')->middleware('admin')->group(function () {
            Route::get('/', [GameContentController::class, 'index']);
            Route::post('/scenarios', [GameContentController::class, 'storeScenario']);
            Route::put('/scenarios/{scenario}', [GameContentController::class, 'updateScenario']);
            Route::delete('/scenarios/{scenario}', [GameContentController::class, 'destroyScenario']);
            Route::post('/items', [GameContentController::class, 'storeItem']);
            Route::put('/items/{item}', [GameContentController::class, 'updateItem']);
            Route::delete('/items/{item}', [GameContentController::class, 'destroyItem']);
        });
    });
});

require __DIR__.'/auth.php';
