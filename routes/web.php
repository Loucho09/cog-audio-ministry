<?php

use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DutyResponseController;
use App\Http\Controllers\DutySwapController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JoinApplicationController;
use App\Http\Controllers\NotificationController;
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
    Route::post('/duties/{assignment}/accept', [DutyResponseController::class, 'accept'])->whereNumber('assignment')->name('duties.accept');
    Route::post('/duties/{assignment}/decline', [DutyResponseController::class, 'decline'])->whereNumber('assignment')->name('duties.decline');
    Route::post('/duties/{assignment}/swap', [DutySwapController::class, 'store'])->whereNumber('assignment')->name('swaps.store');
    Route::post('/swaps/{swap}/claim', [DutySwapController::class, 'claim'])->whereNumber('swap')->name('swaps.claim');
    Route::post('/swaps/{swap}/cancel', [DutySwapController::class, 'cancel'])->whereNumber('swap')->name('swaps.cancel');
    Route::post('/availability', [AvailabilityController::class, 'store'])->name('availability.store');
    Route::delete('/availability/{unavailability}', [AvailabilityController::class, 'destroy'])->whereNumber('unavailability')->name('availability.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('throttle:password-change')->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('throttle:password-change')->name('profile.destroy');
});

require __DIR__.'/auth.php';
