<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Company\AdminController;
use App\Http\Controllers\Company\DepartmentController;
use App\Http\Controllers\Company\HomeController as CompanyHomeController;
use App\Http\Controllers\Company\SurveyController;
use App\Http\Controllers\Company\UserController;
use App\Http\Controllers\Company\UserCsvController;
use App\Http\Controllers\Super\CompanyController;
use App\Http\Controllers\Super\CompanySwitchController;
use App\Http\Controllers\User\HomeController as UserHomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->middleware(['auth'])->name('logout');

Route::middleware(['auth', 'active', 'role:super_user'])
    ->prefix('super')
    ->name('super.')
    ->group(function () {
        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('companies/create', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
        Route::put('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::post('companies/{company}/deactivate', [CompanyController::class, 'deactivate'])->name('companies.deactivate');
        Route::post('companies/{company}/activate', [CompanyController::class, 'activate'])->name('companies.activate');

        Route::post('companies/{company}/switch', [CompanySwitchController::class, 'enter'])->name('switch.enter');
        Route::post('switch/exit', [CompanySwitchController::class, 'exit'])->name('switch.exit');
    });

Route::middleware(['auth', 'active', 'role:super_user,admin', 'ctx'])
    ->prefix('company')
    ->name('company.')
    ->group(function () {
        Route::get('home', [CompanyHomeController::class, 'index'])->name('home');

        Route::get('users/csv', [UserCsvController::class, 'show'])->name('users.csv');
        Route::post('users/csv', [UserCsvController::class, 'store'])->name('users.csv.store');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');

        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('departments/create', [DepartmentController::class, 'create'])->name('departments.create');
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::post('departments/{department}/deactivate', [DepartmentController::class, 'deactivate'])->name('departments.deactivate');
        Route::post('departments/{department}/activate', [DepartmentController::class, 'activate'])->name('departments.activate');

        Route::get('surveys', [SurveyController::class, 'index'])->name('surveys.index');
        Route::get('surveys/create', [SurveyController::class, 'create'])->name('surveys.create');
        Route::post('surveys', [SurveyController::class, 'store'])->name('surveys.store');
        Route::get('surveys/{survey}', [SurveyController::class, 'show'])->name('surveys.show');
        Route::get('surveys/{survey}/edit', [SurveyController::class, 'edit'])->name('surveys.edit');
        Route::put('surveys/{survey}', [SurveyController::class, 'update'])->name('surveys.update');
        Route::delete('surveys/{survey}', [SurveyController::class, 'destroy'])->name('surveys.destroy');
        Route::post('surveys/{survey}/publish', [SurveyController::class, 'publish'])->name('surveys.publish');
    });

Route::middleware(['auth', 'active', 'role:super_user', 'ctx'])
    ->prefix('company')
    ->name('company.')
    ->group(function () {
        Route::get('admins', [AdminController::class, 'index'])->name('admins.index');
        Route::get('admins/create', [AdminController::class, 'create'])->name('admins.create');
        Route::post('admins', [AdminController::class, 'store'])->name('admins.store');
        Route::get('admins/{admin}/edit', [AdminController::class, 'edit'])->name('admins.edit');
        Route::put('admins/{admin}', [AdminController::class, 'update'])->name('admins.update');
        Route::post('admins/{admin}/deactivate', [AdminController::class, 'deactivate'])->name('admins.deactivate');
        Route::post('admins/{admin}/activate', [AdminController::class, 'activate'])->name('admins.activate');
    });

Route::middleware(['auth', 'active', 'role:user'])
    ->get('/home', [UserHomeController::class, 'index'])
    ->name('user.home');
