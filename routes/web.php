<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DutyResponseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JoinApplicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceCalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/services/{service}/calendar.ics', ServiceCalendarController::class)
    ->whereNumber('service')
    ->middleware('throttle:60,1')
    ->name('services.calendar');

Route::get('/join', [JoinApplicationController::class, 'create'])->name('join.create');
Route::post('/join', [JoinApplicationController::class, 'store'])->middleware('throttle:join-form')->name('join.store');
Route::get('/join/thanks', [JoinApplicationController::class, 'thanks'])->name('join.thanks');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'throttle:30,1'])->group(function () {
    Route::post('/duties/{assignment}/accept', [DutyResponseController::class, 'accept'])->name('duties.accept');
    Route::post('/duties/{assignment}/decline', [DutyResponseController::class, 'decline'])->name('duties.decline');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('throttle:password-change')->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('throttle:password-change')->name('profile.destroy');
});

require __DIR__.'/auth.php';
