<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\IntramuralGameController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\OverallTeamController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\VarsityPlayerController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


//version 1 of all the routes
Route::prefix('v1')->group(function () {
    
    // Public routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    // User routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('logout', [AuthController::class, 'logout']);
        Route::get('users', [UserController::class, 'index']);
        Route::get('user', [UserController::class, 'show']);
    });

    // Admin routes
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        // Intramurals
        Route::post('intramurals/create', [IntramuralGameController::class, 'store']);
        Route::get('intramurals', [IntramuralGameController::class, 'index']);
        Route::delete('intramurals/{id}', [IntramuralGameController::class, 'destroy']);
        Route::get('intramurals/{id}', [IntramuralGameController::class, 'show']);
        Route::patch('intramurals/{id}/edit', [IntramuralGameController::class, 'update']);

        // Routes inside intramural games
        Route::prefix('intramurals/{intrams_id}')->group(function () {
            // Venues
            Route::get('venues', [VenueController::class, 'index']);
            Route::post('venues/create', [VenueController::class, 'store']);
            Route::delete('venues/{id}', [VenueController::class, 'destroy']);
            Route::get('venues/{id}', [VenueController::class, 'show']);
            Route::patch('venues/{id}/edit', [VenueController::class, 'update']);

            // Overall teams
            Route::get('overall_teams', [OverallTeamController::class, 'index']);
            Route::post('overall_teams/create', [OverallTeamController::class, 'store']);
            Route::get('overall_teams/{id}', [OverallTeamController::class, 'show']);
            Route::patch('overall_teams/{id}/edit', [OverallTeamController::class, 'update_info']);
            Route::patch('overall_teams/{id}/update_medal', [OverallTeamController::class, 'update_medal']);
            Route::delete('overall_teams/{id}', [OverallTeamController::class, 'destroy']);

            // Events
            Route::get('events', [EventController::class, 'index']);
            Route::post('events/create', [EventController::class, 'store']);
            Route::get('events/{id}', [EventController::class, 'show']);
            Route::patch('events/{id}/edit', [EventController::class, 'update']);
            Route::delete('events/{id}', [EventController::class, 'destroy']);

            // Varsity players
            Route::get('varsity_players', [VarsityPlayerController::class, 'index']);
            Route::post('varsity_players/create', [VarsityPlayerController::class, 'store']);
            Route::get('varsity_players/{id}', [VarsityPlayerController::class, 'show']);
            Route::patch('varsity_players/{id}', [VarsityPlayerController::class, 'update']);
            Route::delete('varsity_players/{id}', [VarsityPlayerController::class, 'destroy']);
        });
    });
});
