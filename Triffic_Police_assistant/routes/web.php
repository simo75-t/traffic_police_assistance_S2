<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ViolationTypeController;
use App\Http\Controllers\Citizen\ViolationController;
use App\Http\Controllers\PoliceManager\AppealControllerPolice;
use App\Http\Controllers\PoliceManager\AuthController as PoliceManagerAuthController;
use App\Http\Controllers\PoliceManager\DashboardController as PoliceManagerDashboardController;
use App\Http\Controllers\PoliceManager\HeatmapController as PoliceManagerHeatmapController;
use App\Http\Controllers\PoliceManager\ViolationControllerPolice;
use App\Http\Controllers\QueueController;
use App\Http\Services\Admin\UserService;
use Illuminate\Support\Facades\Route;


Route::get('/test-queue', [QueueController::class, 'sendMessage']);


//citizen

Route::get('/', [ViolationController::class, 'page'])->name('citizen.page');

Route::get('/citizen/violations', [ViolationController::class, 'search'])->name('citizen.violations');

// صفحة form الاعتراض (GET) — لعرض form للمستخدم
Route::get('/citizen/appeal-form', function () {
    return view('citizen.appeal');  // ملف Blade للـ form
})->name('citizen.appeal.form');

// معالجة POST من form الاعتراض
Route::post('/citizen/appeals', [ViolationController::class, 'store'])
     ->name('citizen.appeals.store');



// Authentication Routes
// Admin
Route::prefix('admin')->name('admin.')->group(function () {

Route::get("login", [AuthController::class, "showLoginForm"])->name("login");
Route::post("login", [AuthController::class, "login"])->name("login.submit");
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


 // Violation Types
    Route::prefix("violation-types")->name("violationTypes.")->group(function () {

        Route::get("/", [ViolationTypeController::class, "index"])->name("index");
        Route::get("/create", [ViolationTypeController::class, "create"])->name("create");
        Route::post("/store", [ViolationTypeController::class, "store"])->name("store");
        
    });




});

});

// Police Manager Authentication
Route::prefix('policemanager')->name('policemanager.')->group(function () {
    Route::get('login', [PoliceManagerAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [PoliceManagerAuthController::class, 'login'])->name('login.submit');

    Route::middleware(['auth', 'police.manager'])->group(function () {
        Route::get('/', [PoliceManagerDashboardController::class, 'index'])->name('home');
        Route::get('/violations', [ViolationControllerPolice::class, 'index'])->name('violations.index');
        Route::get('/violations/heatmap', [ViolationControllerPolice::class, 'heatmap'])->name('violations.heatmap');
        Route::post('/heatmap/generate', [PoliceManagerHeatmapController::class, 'generate'])->name('heatmap.generate');
        Route::get('/heatmap/result/{job_id}', [PoliceManagerHeatmapController::class, 'result'])->name('heatmap.result');

        Route::get('/appeals', [AppealControllerPolice::class, 'index'])->name('appeals.index');
        Route::get('/appeals/{appeal}', [AppealControllerPolice::class, 'show'])->name('appeals.show');
        Route::post('/appeals/{appeal}', [AppealControllerPolice::class, 'update'])->name('appeals.update');
        Route::put('/appeals/{appeal}', [AppealControllerPolice::class, 'updateStatus'])->name('appeals.updateStatus');

        Route::post('logout', [PoliceManagerAuthController::class, 'logout'])->name('logout');
    });
});
