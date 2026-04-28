<?php

use App\Http\Controllers\Citizen\ReportController as CitizenReportController;
use App\Http\Controllers\PoliceManager\HeatmapController as PoliceManagerHeatmapController;
use App\Http\Controllers\PoliceOfficer\AuthController;
use App\Http\Controllers\PoliceOfficer\CityController;
use App\Http\Controllers\PoliceOfficer\DispatchController;
use App\Http\Controllers\PoliceOfficer\OcrController;
use App\Http\Controllers\PoliceOfficer\SttController;
use App\Http\Controllers\PoliceOfficer\ViolationController;
use App\Http\Controllers\PoliceOfficer\ViolationTypeController;
use Illuminate\Support\Facades\Route;



// Authentication Routes
// Police officer
Route::post("/login", [AuthController::class, "login"]);
Route::post('/citizen/reports', [CitizenReportController::class, 'store']);

Route::middleware(['auth:api'])->group(function () {

    Route::get("/" , []);

// Profile Routes 
Route::get("/profile" ,[AuthController::class , "profile"])->name("profile info");
Route::post("/profile/update" ,[AuthController::class , "updateProfile"]);
Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);

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

Route::post('/officers/live-location', [DispatchController::class, 'updateLocation']);
Route::get('/officers/assignments', [DispatchController::class, 'myAssignments']);
Route::post('/officers/assignments/{assignment}/start', [DispatchController::class, 'start']);
Route::post('/officers/assignments/{assignment}/complete', [DispatchController::class, 'complete']);


});



Route::get('/ai_cities', [CityController::class, 'index']);
Route::get('/ai_violation-types', [ViolationTypeController::class, 'index']);
Route::get('/ai_violations', [ViolationController::class, 'aiIndex']);
Route::get('/heatmap-predictions/{request_id}', [PoliceManagerHeatmapController::class, 'predictionStatus']);
