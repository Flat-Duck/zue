<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\CenterController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PassengerController;
use App\Http\Controllers\ResidenceController;
use App\Http\Controllers\TimeSheetController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Models\TimeSheet;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/ss', function () {
     
    $month_name = "April";
    $chunk = TimeSheet::whereMonth('day', 10)->whereYear('day', '2023')->limit('3100')->get();
    $chunks = $chunk->groupBy('employee_id')->chunk(10);
    $month_days = Carbon\Carbon::now()->month($month_name)->daysInMonth + 1;

    return view('app.time_sheets.approve', compact('chunks', 'month_name', 'month_days'));
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::prefix('/')
    ->middleware('auth')
    ->group(function () {
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);

        Route::resource('administrations', AdministrationController::class);
        Route::resource('centers', CenterController::class);
        Route::resource('departments', DepartmentController::class);
        Route::resource('flights', FlightController::class);
        Route::resource('locations', LocationController::class);
        Route::resource('passengers', PassengerController::class);
        Route::resource('residences', ResidenceController::class);
        Route::resource('rooms', RoomController::class);
        Route::resource('stocks', StockController::class);
        Route::get('time-sheets/create/{employee}', [TimeSheetController::class, 'create'])->name('time-sheets.fill');
        Route::get('time-sheets/edit/{employee}', [TimeSheetController::class, 'edit'])->name('time-sheets.revise');
        Route::resource('time-sheets', TimeSheetController::class);
        Route::resource('users', UserController::class);
        Route::resource('employees', EmployeeController::class);
        Route::get('profile', [ProfileController::class, 'show' ])->name('profile.show');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('reports', [ReportController::class, 'index'])->name('admin.reports.index');
        Route::post('reports/minus', [ReportController::class, 'minus'])->name('admin.reports.minus');
    });
