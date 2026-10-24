<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController,DashboardController,OrderController,RecordController,SettingsController};
Route::middleware('guest')->group(function () {
    Route::view('/login','auth.login')->name('login');
    Route::post('/login',[AuthController::class,'login'])->middleware('throttle:20,1');
});
Route::get('/branding/logo',[SettingsController::class,'logo'])->middleware('throttle:120,1');
Route::middleware(['auth','auth.session','throttle:120,1'])->group(function () {
    Route::post('/logout',[AuthController::class,'logout']);
    Route::get('/',[DashboardController::class,'index'])->name('dashboard');
    Route::get('/orders',[OrderController::class,'index'])->middleware('can:queue');
    Route::get('/orders/new',[OrderController::class,'create'])->middleware('can:operations');
    Route::post('/orders',[OrderController::class,'store'])->middleware('can:operations');
    Route::get('/orders/{order}',[OrderController::class,'show'])->whereNumber('order')->middleware('can:queue');
    Route::post('/orders/{order}/status',[OrderController::class,'transition'])->middleware('can:queue');
    Route::post('/orders/{order}/payments',[OrderController::class,'payment'])->middleware('can:finance');
    Route::get('/orders/{order}/receipt',[OrderController::class,'receipt'])->middleware('can:finance');
    Route::get('/manage/{module}',[RecordController::class,'index']);
    Route::get('/manage/{module}/new',[RecordController::class,'form']);
    Route::get('/manage/{module}/{id}/edit',[RecordController::class,'form'])->whereNumber('id');
    Route::post('/manage/{module}',[RecordController::class,'save']);
    Route::put('/manage/{module}/{id}',[RecordController::class,'save'])->whereNumber('id');
    Route::post('/inventory/{id}/stock',[RecordController::class,'stock'])->whereNumber('id');
    Route::get('/inventory/{id}/movements',[RecordController::class,'movements'])->whereNumber('id');
    Route::get('/reports',[DashboardController::class,'reports'])->middleware('can:reports');
    Route::get('/audit',[DashboardController::class,'audit'])->middleware('can:audit');
    Route::get('/settings',[SettingsController::class,'edit'])->middleware('can:settings');
    Route::put('/settings',[SettingsController::class,'save'])->middleware('can:settings');
    Route::view('/account','account');
    Route::put('/account/password',[SettingsController::class,'password']);
    Route::get('/api/v1/summary',[DashboardController::class,'summary'])->middleware('can:reports');
});
