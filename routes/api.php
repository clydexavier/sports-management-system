<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\IntramuralGameController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\OverallTeamController;
use App\Http\Controllers\EventController;

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

//user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::apiResource('/users', UserController::class);
});


//public routes
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

//admin routes
Route::middleware(['auth:sanctum', 'admin'])->group(function (){
    
    //intramurals
    Route::post('/intramurals', [IntramuralGameController::class, 'store']); //add new intramural
    Route::get('/intramurals', [IntramuralGameController::class, 'index']); //show all intramural
    Route::delete('/intramurals/{id}', [IntramuralGameController::class, 'destroy']); //delete intramural
    Route::get('/intramurals/{id}', [IntramuralGameController::class, 'show']); //target specific intramural
    Route::patch('/intramurals/{id}', [IntramuralGameController::class, 'update']); // Update game details


    //routes inside intramural games
    Route::prefix('intramurals/{intrams_id}')->group(function () {
        //venues
        Route::get('/venues', [VenueController::class, 'index']);  // List venues for a game
        Route::post('/venues', [VenueController::class, 'store']); // Add a venue to a game

        Route::delete('/venues/{id}', [VenueController::class, 'destroy']); //delete intramural
        Route::get('/venues/{id}', [VenueController::class, 'show']); //target specific intramural
        Route::patch('/venues/{id}', [VenueController::class, 'update']); // Update game details
            
        // Events nested under Venues
        Route::prefix('/venues/{venue_id}')->group(function () {
            Route::get('/events', [EventController::class, 'index']);
            Route::post('/events', [EventController::class, 'store']);
            Route::get('/events/{id}', [EventController::class, 'show']);
            Route::patch('/events/{id}', [EventController::class, 'update']);
            Route::delete('/events/{id}', [EventController::class, 'destroy']);
    });

        //overall teams
        Route::get('/overall_teams', [OverallTeamController::class, 'index']);
        Route::post('/overall_teams', [OverallTeamController::class, 'store']);
        Route::get('/overall_teams/{id}', [OverallTeamController::class, 'show']);
        Route::patch('/overall_teams/{id}/update_info', [OverallTeamController::class, 'update_info']); // update basic team info
        Route::patch('/overall_teams/{id}/update_medal', [OverallTeamController::class, 'update_medal']); //updated total medals of a team  
        Route::delete('/overall_teams/{id}', [OverallTeamController::class, 'destroy']);


        
    });
});


