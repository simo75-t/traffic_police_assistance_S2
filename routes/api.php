<?php

use App\Http\Controllers\PoliceOfficer\AuthController;
use App\Http\Controllers\PoliceOfficer\ViolationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Authentication Routes
// Police officer
Route::post("/login", [AuthController::class, "login"]);
Route::post("logout", [AuthController::class, "logout"]);

Route::middleware(['auth:api'])->group(function () {

    Route::get("/" , []);

// Profile Routes 
Route::get("/profile" ,[AuthController::class , "profile"])->name("profile info");

// violation Routes 
Route::post("/create" ,[ ViolationController::class , "create" ]);
Route::get("/violations" , [ ViolationController::class , 'index'] );

});