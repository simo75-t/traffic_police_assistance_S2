<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Services\Admin\UserService;
use Illuminate\Support\Facades\Route;


// Authentication Routes
// Admin
Route::prefix('admin')->name('admin.')->group(function () {

Route::get("login", [AuthController::class, "showLoginForm"])->name("login");
Route::post("login", [AuthController::class, "login"])->name("login_action");
Route::post("logout", [AuthController::class, "logout"])->name("logout");

// Protected Admin Routes
Route::middleware(['auth' , 'admin.role'])->group(function () {
Route::get("dashboard",[DashboardController::class , "index"] )->name("home");

//user management Routes
Route::prefix("Users")->name("users")->group(function(){
    Route::get("/" , [UserController::class , "index"])->name(".index");
    Route::get("/create" , [UserController::class , "create"])->name(".create");
    Route::post("/store" , [UserController::class , "store"])->name(".store");
    Route::get("/{user}" , [UserController::class , "show"])->name(".show");
    Route::get("edit/{user}" , [UserController::class , "edit"] )->name('.edit');
    Route::patch("/update/{user}" , [UserController::class , "saveupdate"] )->name('.saveupdate');
    Route::post("/{user}" , [UserController::class , "updateStatus"])->name(".updateStatus");
    Route::delete("/{user}" ,[ UserController::class , "destroy"])->name(".delete");
    Route::patch("{user}/toggle" , [UserController::class , 'toggleStatus'])->name('.toggle');
});

});


});