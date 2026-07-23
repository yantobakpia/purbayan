<?php

use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RoomController::class, 'index']);
Route::get('/monitor', [RoomController::class, 'monitor'])->name('monitor');
Route::get('/check-quota', [RoomController::class, 'checkQuota'])->name('check-quota');
Route::get('/check-status', [RoomController::class, 'checkStatus'])->name('check-status');
Route::get('/jadwal', [RoomController::class, 'jadwalPage'])->name('jadwal.page');
Route::get('/peminjaman', [RoomController::class, 'peminjamanPage'])->name('peminjaman.page');
Route::get('/keluhan', [RoomController::class, 'complaintPage'])->name('complaint.page');
Route::post('/book', [RoomController::class, 'book'])->name('book')->middleware('throttle:10,1');
Route::post('/complaint', [RoomController::class, 'complaint'])->name('complaint')->middleware('throttle:5,1');
Route::post('/bookings/{booking}/cancel', [RoomController::class, 'cancelBooking'])->name('bookings.cancel');

Route::middleware('auth')->group(function () {
    Route::get('/user/profile', [RoomController::class, 'profilePage'])->name('profile.page');
    Route::post('/user/profile', [RoomController::class, 'updateProfile'])->name('profile.update');
});

Route::get('/admin/unread-notifications', [RoomController::class, 'unreadNotifications'])
    ->name('admin.unread-notifications');

Route::redirect('/login', '/user/login')->name('login');
