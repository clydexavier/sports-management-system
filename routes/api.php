<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\IntramuralGameController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\OverallTeamController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\VarsityPlayerController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| This file defines API routes for version 1 (v1). All routes are
| assigned to the "api" middleware group. 
|
*/

// Version 1 API routes
Route::prefix('v1')->group(function () {
    
    // Public routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    // Authenticated user routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('logout', [AuthController::class, 'logout']);
        Route::get('users', [UserController::class, 'index']);
        Route::get('user', [UserController::class, 'show']);
    });

    // Admin-only routes
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Intramurals
        Route::prefix('intramurals')->group(function () {
            Route::post('create', [IntramuralGameController::class, 'store']);
            Route::get('/', [IntramuralGameController::class, 'index']);
            Route::get('{id}', [IntramuralGameController::class, 'show']);
            Route::patch('{id}/edit', [IntramuralGameController::class, 'update']);
            Route::delete('{id}', [IntramuralGameController::class, 'destroy']);

            // Nested routes within specific intramural games
            Route::prefix('{intrams_id}')->group(function () {
                
                // Venues
                Route::prefix('venues')->group(function () {
                    Route::get('/', [VenueController::class, 'index']);
                    Route::post('create', [VenueController::class, 'store']);
                    Route::get('{id}', [VenueController::class, 'show']);
                    Route::patch('{id}/edit', [VenueController::class, 'update']);
                    Route::delete('{id}', [VenueController::class, 'destroy']);
                });

                // Overall Teams
                Route::prefix('overall_teams')->group(function () {
                    Route::get('/', [OverallTeamController::class, 'index']);
                    Route::post('create', [OverallTeamController::class, 'store']);
                    Route::get('{id}', [OverallTeamController::class, 'show']);
                    Route::patch('{id}/edit', [OverallTeamController::class, 'update_info']);
                    Route::patch('{id}/update_medal', [OverallTeamController::class, 'update_medal']);
                    Route::delete('{id}', [OverallTeamController::class, 'destroy']);
                });

                // Events
                Route::prefix('events')->group(function () {
                    Route::get('/', [EventController::class, 'index']);
                    Route::post('create', [EventController::class, 'store']);
                    Route::get('{id}', [EventController::class, 'show']);
                    Route::patch('{id}/edit', [EventController::class, 'update']);
                    Route::delete('{id}', [EventController::class, 'destroy']);
                });

                // Varsity Players
                Route::prefix('varsity_players')->group(function () {
                    Route::get('/', [VarsityPlayerController::class, 'index']);
                    Route::post('create', [VarsityPlayerController::class, 'store']);
                    Route::get('{id}', [VarsityPlayerController::class, 'show']);
                    Route::patch('{id}/edit', [VarsityPlayerController::class, 'update']);
                    Route::delete('{id}', [VarsityPlayerController::class, 'destroy']);
                });

                // Documents (Placeholder for future routes)
            });
        });
    });
});
