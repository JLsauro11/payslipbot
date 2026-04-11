<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\FacebookController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\VerifiedSenderController;

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



Route::controller(AuthController::class)->middleware('guest')->group(function() {
    Route::match(['get', 'post'], 'login', 'login')->name('login');
    Route::get('forgot-password', 'forgot_password')->name('forgot-password');
    Route::post('forgot-password', 'verify_submit')->name('forgot-password.submit');
    Route::get('change-password', 'change_password')->name('change-password');
    Route::post('change-password', 'change_password_submit')->name('change-password.submit');
});


Route::controller(AuthController::class)->middleware('auth')->group( function() {
    Route::match(['post', 'get'], 'logout', 'logout')->name('logout');
});

Route::controller(HomeController::class)->middleware('auth')->group(function() {
    Route::get('dashboard', 'index')->name('home.index');
    Route::post('account/update','updateAccount')->name('account.update');
});

Route::controller(EmployeeController::class)->middleware('auth')->group(function() {
    Route::get('employees', 'index')->name('employees.index');
    Route::get('employees/data', 'data')->name('employees.data');
    Route::get('employees/payslip-data', 'employeesForPayslip')->name('employees.payslip-data');
    Route::post('employees', 'store')->name('employees.store');
    Route::get('employees/{employee}', 'show')->name('employees.show');
    Route::put('employees/{employee}',  'update')->name('employees.update');
    Route::delete('employees/{employee}', 'destroy')->name('employees.destroy');
    Route::post('/employees/delete-selected', 'bulkDelete')->name('employees.multi-delete');

});

Route::controller(PositionController::class)->middleware('auth')->group(function() {
    Route::get('positions', 'index')->name('positions.index');
    Route::get('positions/data', 'data')->name('positions.data');
    Route::post('positions', 'store')->name('positions.store');
    Route::get('positions/{position}', 'show')->name('positions.show');
    Route::put('positions/{position}',  'update')->name('positions.update');
    Route::delete('positions/{position}', 'destroy')->name('positions.destroy');
    Route::post('/positions/delete-selected', 'bulkDelete')->name('positions.multi-delete');

});

Route::controller(AreaController::class)->middleware('auth')->group(function() {
    Route::get('areas', 'index')->name('areas.index');
    Route::get('areas/data', 'data')->name('areas.data');
    Route::post('areas', 'store')->name('areas.store');
    Route::get('areas/{area}', 'show')->name('areas.show');
    Route::put('areas/{area}',  'update')->name('areas.update');
    Route::delete('areas/{area}', 'destroy')->name('areas.destroy');
    Route::post('/areas/delete-selected', 'bulkDelete')->name('areas.multi-delete');

});

Route::controller(DepartmentController::class)->middleware('auth')->group(function() {
    Route::get('departments', 'index')->name('departments.index');
    Route::get('departments/data', 'data')->name('departments.data');
    Route::post('departments', 'store')->name('departments.store');
    Route::get('departments/{department}', 'show')->name('departments.show');
    Route::put('departments/{department}',  'update')->name('departments.update');
    Route::delete('departments/{department}', 'destroy')->name('departments.destroy');
    Route::post('/departments/delete-selected', 'bulkDelete')->name('departments.multi-delete');
});

Route::controller(PayslipController::class)->middleware('auth')->group(function() {
    Route::get('payslips', 'index')->name('payslips.index');
    Route::get('payslips/data', 'data')->name('payslips.data');
    Route::post('payslips', 'store')->name('payslips.store');
    Route::get('payslips/{payslip}', 'show')->name('payslips.show');
    Route::put('payslips/{payslip}', 'update')->name('payslips.update');
    Route::delete('payslips/{payslip}', 'destroy')->name('payslips.destroy');
    Route::post('payslips/multi-store', 'multiStore')->name('payslips.multi-store');
    Route::post('/payslips/delete-selected', 'bulkDelete')->name('payslips.multi-delete');
});

Route::controller(VerifiedSenderController::class)->middleware('auth')->group(function() {
    Route::get('verified_senders', 'index')->name('verified_senders.index');
    Route::get('verified_senders/data', 'data')->name('verified_senders.data');
    Route::post('verified_senders', 'store')->name('verified_senders.store');
    Route::delete('verified_senders/{verified_sender}', 'destroy')->name('verified_senders.destroy');
    Route::post('verified_senders/multi-store', 'multiStore')->name('verified_senders.multi-store');
    Route::post('/verified_senders/delete-selected', 'bulkDelete')->name('verified_senders.multi-delete');
});



