<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\IntramuralGameController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\OverallTeamController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\VarsityPlayerController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\BracketController;
use App\Http\Controllers\ParticipatingTeamController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\TeamGalleryController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\PodiumController;



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

Route::post('{intrams_id}/{event_id}/{team_id}/gallery/generate/', [TeamGalleryController::class,'generateGalleryDocx']);

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
    
    //GAM
    //Secretariat
    //tournament secretary

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
                

                //varsity players sport
                Route::get('vplayers_sport', [VarsityPlayerController::class, 'vplayer_sports']);

                //tally
                Route::get('overall_tally', [IntramuralGameController::class, 'overall_tally']);
                //tally by category

                
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
                
                //Podium routes
                Route::get('podiums', [PodiumController::class, 'index']);

                // Events //fucntions as tournaments in challonge
                Route::prefix('events')->group(function () {
                    //local db
                    //basic event CRUD
                    Route::get('/', [EventController::class, 'index']);
                    Route::post('create', [EventController::class, 'store']);
                    Route::get('{id}', [EventController::class, 'show']);
                    Route::patch('{id}/edit', [EventController::class, 'update']);
                    Route::delete('{id}', [EventController::class, 'destroy']);

                    Route::get('{id}/status', [EventController::class, 'event_status']);

                    

                    //start and submit results of event
                    Route::post('{id}/start', [EventController::class, 'start']);
                    Route::post('{id}/finalize', [EventController::class, 'finalize']);
                    Route::post('{id}/reset', [EventController::class, 'reset']);

                    Route::prefix('{event_id}') ->group(function () {
                       
                        //Players Route                       
                        Route::get('players', [PlayerController::class, 'index']);
                        Route::post('players/create', [PlayerController::class, 'store']);
                        Route::patch('players/{id}/edit', [PlayerController::class, 'update']);
                        Route::delete('players/{id}', [PlayerController::class, 'destroy']);
                        
                        //bracket, matches, and schedule of events
                        Route::get('bracket', [EventController::class, 'bracket']);
                        Route::get('matches', [GameController::class, 'index']);

                        Route::get('schedule', [ScheduleController::class, 'index']);
                        Route::post('schedule/create', [ScheduleController::class, 'store']);
                        Route::get('schedule/{id}', [ScheduleController::class, 'show']);
                        Route::patch('schedule/{id}/edit', [ScheduleController::class, 'update']);
                        Route::delete('schedule/{id}', [ScheduleController::class, 'destroy']);



                        //participants route
                        Route::get('team_names', [OverallTeamController::class, 'index_team_name']);
                        //Route::get('participants', [ParticipatingTeamController::class, 'index']);
                       // Route::post('participants/create', [ParticipatingTeamController::class, 'store']);
                        //Route::patch('participants/{id}/edit', [ParticipatingTeamController::class, 'update']);
                       // Route::delete('participants/{id}', [ParticipatingTeamController::class, 'destroy']);
                       // Route::get('participants/{id}', [ParticipatingTeamController::class, 'show']);

                       //Podium routes
                       Route::post('podium/create', [PodiumController::class, 'store']);
                       
                       Route::get('podium', [PodiumController::class, 'show']);
                       Route::patch('podium/update', [PodiumController::class, 'update']);
                       Route::delete('podium', [PodiumController::class, 'destroy']);
                    });

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
                Route::prefix('documents')->group(function () {
                    Route::get('/', [DocumentController::class, 'index']); // List all documents
                    Route::post('create', [DocumentController::class, 'store']); // Upload a document
                    Route::get('{id}', [DocumentController::class, 'show']); // Get a specific document
                    Route::patch('{id}/edit', [DocumentController::class, 'update']); // Update document details
                    Route::delete('{id}', [DocumentController::class, 'destroy']); // Delete a document
                    Route::get('{id}/download', [DocumentController::class, 'download']); // Download a document 
                });
            });
        });
    });
});