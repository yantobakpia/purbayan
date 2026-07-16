<?php

use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RoomController::class, 'index']);
Route::get('/monitor', [RoomController::class, 'monitor'])->name('monitor');
Route::get('/check-quota', [RoomController::class, 'checkQuota'])->name('check-quota');
Route::post('/book', [RoomController::class, 'book'])->name('book');
Route::post('/complaint', [RoomController::class, 'complaint'])->name('complaint');
