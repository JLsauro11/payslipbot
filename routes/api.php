<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacebookController;

Route::match(['get', 'post'], 'facebook/webhook', [FacebookController::class, 'index']);
