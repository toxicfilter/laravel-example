<?php

use App\Http\Controllers\ToxicFilterWebhookController;
use App\Http\Controllers\WallController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WallController::class, 'index'])->name('wall');
Route::post('/comments', [WallController::class, 'store'])->name('comments.store');
Route::post('/webhooks/toxicfilter', ToxicFilterWebhookController::class)->name('webhooks.toxicfilter');
