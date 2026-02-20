<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\FacebookController;
use App\Http\Controllers\PrivacyController;

// 🔥 #1 PUBLIC ROUTES - NO MIDDLEWARE
Route::get('/payslipbot/payslips/{filename}', function ($filename) {
    $path = public_path('payslips/' . $filename);

    \Log::info('📄 PAYSLIP ROUTE HIT', [
        'filename' => $filename,
        'path' => $path,
        'exists' => file_exists($path)
    ]);

    if (!file_exists($path)) {
        abort(404, 'Payslip not found');
    }

    return response()->file($path);
})->where('filename', '.*');

Route::get('/privacy-policy', [PrivacyController::class, 'index'])->name('privacy-policy');

//Route::match(['get', 'post'], '/facebook/webhook', [FacebookController::class, 'index']);

// Root route - redirect based on auth
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});



Route::controller(AuthController::class)->middleware('guest')->group( function() {
    Route::match(['post', 'get'], 'login', 'login')->name('login');
});

Route::controller(AuthController::class)->middleware('auth')->group( function() {
    Route::match(['post', 'get'], 'logout', 'logout')->name('logout');
});

Route::controller(HomeController::class)->middleware('auth')->group(function() {
    Route::get('dashboard', 'index')->name('home.index');
});

Route::controller(EmployeeController::class)->middleware('auth')->group(function() {
    Route::get('employees', 'index')->name('employees.index');
    Route::get('employees/data', 'data')->name('employees.data');
    Route::post('employees', 'store')->name('employees.store');
    Route::get('employees/{employee}', 'show')->name('employees.show');
    Route::put('employees/{employee}',  'update')->name('employees.update');
    Route::delete('employees/{employee}', 'destroy')->name('employees.destroy');
});

Route::controller(PayslipController::class)->middleware('auth')->group(function() {
    Route::get('payslips', 'index')->name('payslips.index');
    Route::get('payslips/data', 'data')->name('payslips.data');
    Route::post('payslips', 'store')->name('payslips.store');
    Route::get('payslips/{payslip}', 'show')->name('payslips.show');
    Route::put('payslips/{payslip}', 'update')->name('payslips.update');
    Route::delete('payslips/{payslip}', 'destroy')->name('payslips.destroy');
});

Route::post('/payslips/delete-selected', [PayslipController::class, 'bulkDelete'])
    ->middleware('auth')
    ->name('payslips.multi-delete');



