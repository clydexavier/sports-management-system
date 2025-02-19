<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\IntramuralGameController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenueController;
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
    Route::put('/intramurals/{id}', [IntramuralGameController::class, 'update']); // Update game details

    //venues
    Route::prefix('intramurals/{intrams_id}')->group(function () {
        Route::get('/venues', [VenueController::class, 'index']);  // List venues for a game
        Route::post('/venues', [VenueController::class, 'store']); // Add a venue to a game
        Route::delete('/venues/{id}', [VenueController::class, 'destroy']); //delete intramural
        Route::get('/venues/{id}', [VenueController::class, 'show']); //target specific intramural
        Route::put('/venues/{id}', [VenueController::class, 'update']); // Update game details
    });
});


