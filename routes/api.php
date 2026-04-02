<?php

use App\Http\Controllers\PoliceOfficer\AuthController;
use App\Http\Controllers\PoliceOfficer\CityController;
use App\Http\Controllers\PoliceOfficer\OcrController;
use App\Http\Controllers\PoliceOfficer\SttController;
use App\Http\Controllers\PoliceOfficer\ViolationController;
use App\Http\Controllers\PoliceOfficer\ViolationTypeController;
use Illuminate\Support\Facades\Route;



// Authentication Routes
// Police officer
Route::post("/login", [AuthController::class, "login"]);

Route::middleware(['auth:api'])->group(function () {

    Route::get("/" , []);

// Profile Routes 
Route::get("/profile" ,[AuthController::class , "profile"])->name("profile info");

// violation Routes 
Route::post("/create" ,[ ViolationController::class , "create" ]);
Route::get("/violations" , [ ViolationController::class , 'index'] );
Route::get('/search-violations' , [ViolationController::class , 'search']);
Route::get('/cities', [CityController::class, 'index']);
Route::get('/violation-types', [ViolationTypeController::class, 'index']);


Route::post("logout", [AuthController::class, "logout"]);


Route::post('/ocr/plate', [OcrController::class, 'requestPlateOcr']);
Route::get('/ocr/result/{job_id}', [OcrController::class, 'getOcrResult']);

Route::post('/stt/transcribe', [SttController::class, 'requestStt']);
Route::get('/stt/result/{job_id}', [SttController::class, 'getSttResult']);



});



Route::get('/ai_cities', [CityController::class, 'index']);
Route::get('/ai_violation-types', [ViolationTypeController::class, 'index']);
