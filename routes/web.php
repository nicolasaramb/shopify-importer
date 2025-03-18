<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IMSController;
use App\Http\Controllers\ShopifyWebhookController;

use App\Http\Controllers\DashboardController;
use App\Jobs\SyncProductsJob;




Route::get('/', function () {
    return view('welcome');
});



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
    Route::post('/sync-products', function () {
        SyncProductsJob::dispatch();
        return redirect()->route('dashboard')->with('success', 'Sincronización de productos iniciada correctamente');
    })->middleware(['auth'])->name('sync.products');
});


Route::get('/get-products', [IMSController::class, 'getProducts'])->middleware('auth');

Route::post('/shopify/webhook', [ShopifyWebhookController::class, 'handleWebhook'])
->withoutMiddleware(['web']);



require __DIR__.'/auth.php';
