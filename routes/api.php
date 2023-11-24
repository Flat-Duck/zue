<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\CenterController;
use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\PassengerController;
use App\Http\Controllers\Api\ResidenceController;
use App\Http\Controllers\Api\TimeSheetController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoomEmployeesController;
use App\Http\Controllers\Api\EmployeeRoomsController;
use App\Http\Controllers\Api\AdministrationController;
use App\Http\Controllers\Api\ResidenceRoomsController;
use App\Http\Controllers\Api\UserTimeSheetsController;
use App\Http\Controllers\Api\CenterEmployeesController;
use App\Http\Controllers\Api\FlightEmployeesController;
use App\Http\Controllers\Api\EmployeeFlightsController;
use App\Http\Controllers\Api\employee_flightController;
use App\Http\Controllers\Api\FlightPassengersController;
use App\Http\Controllers\Api\PassengerFlightsController;
use App\Http\Controllers\Api\flight_passengerController;
use App\Http\Controllers\Api\LocationEmployeesController;
use App\Http\Controllers\Api\EmployeeTimeSheetsController;
use App\Http\Controllers\Api\DepartmentEmployeesController;
use App\Http\Controllers\Api\AdministrationDepartmentsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')
    ->get('/user', function (Request $request) {
        return $request->user();
    })
    ->name('api.user');

Route::name('api.')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('permissions', PermissionController::class);

        Route::apiResource('administrations', AdministrationController::class);

        // Administration Departments
        Route::get('/administrations/{administration}/departments', [
            AdministrationDepartmentsController::class,
            'index',
        ])->name('administrations.departments.index');
        Route::post('/administrations/{administration}/departments', [
            AdministrationDepartmentsController::class,
            'store',
        ])->name('administrations.departments.store');

        Route::apiResource('centers', CenterController::class);

        // Center Employees
        Route::get('/centers/{center}/employees', [
            CenterEmployeesController::class,
            'index',
        ])->name('centers.employees.index');
        Route::post('/centers/{center}/employees', [
            CenterEmployeesController::class,
            'store',
        ])->name('centers.employees.store');

        Route::apiResource('departments', DepartmentController::class);

        // Department Employees
        Route::get('/departments/{department}/employees', [
            DepartmentEmployeesController::class,
            'index',
        ])->name('departments.employees.index');
        Route::post('/departments/{department}/employees', [
            DepartmentEmployeesController::class,
            'store',
        ])->name('departments.employees.store');

        Route::apiResource('flights', FlightController::class);

        // Flight Passengers
        Route::get('/flights/{flight}/passengers', [
            FlightPassengersController::class,
            'index',
        ])->name('flights.passengers.index');
        Route::post('/flights/{flight}/passengers/{passenger}', [
            FlightPassengersController::class,
            'store',
        ])->name('flights.passengers.store');
        Route::delete('/flights/{flight}/passengers/{passenger}', [
            FlightPassengersController::class,
            'destroy',
        ])->name('flights.passengers.destroy');

        // Flight Employees
        Route::get('/flights/{flight}/employees', [
            FlightEmployeesController::class,
            'index',
        ])->name('flights.employees.index');
        Route::post('/flights/{flight}/employees/{employee}', [
            FlightEmployeesController::class,
            'store',
        ])->name('flights.employees.store');
        Route::delete('/flights/{flight}/employees/{employee}', [
            FlightEmployeesController::class,
            'destroy',
        ])->name('flights.employees.destroy');

        Route::apiResource('locations', LocationController::class);

        // Location Employees
        Route::get('/locations/{location}/employees', [
            LocationEmployeesController::class,
            'index',
        ])->name('locations.employees.index');
        Route::post('/locations/{location}/employees', [
            LocationEmployeesController::class,
            'store',
        ])->name('locations.employees.store');

        Route::apiResource('passengers', PassengerController::class);

        // Passenger Flights
        Route::get('/passengers/{passenger}/flights', [
            PassengerFlightsController::class,
            'index',
        ])->name('passengers.flights.index');
        Route::post('/passengers/{passenger}/flights/{flight}', [
            PassengerFlightsController::class,
            'store',
        ])->name('passengers.flights.store');
        Route::delete('/passengers/{passenger}/flights/{flight}', [
            PassengerFlightsController::class,
            'destroy',
        ])->name('passengers.flights.destroy');

        Route::apiResource('residences', ResidenceController::class);

        // Residence Rooms
        Route::get('/residences/{residence}/rooms', [
            ResidenceRoomsController::class,
            'index',
        ])->name('residences.rooms.index');
        Route::post('/residences/{residence}/rooms', [
            ResidenceRoomsController::class,
            'store',
        ])->name('residences.rooms.store');

        Route::apiResource('rooms', RoomController::class);

        // Room Employees
        Route::get('/rooms/{room}/employees', [
            RoomEmployeesController::class,
            'index',
        ])->name('rooms.employees.index');
        Route::post('/rooms/{room}/employees/{employee}', [
            RoomEmployeesController::class,
            'store',
        ])->name('rooms.employees.store');
        Route::delete('/rooms/{room}/employees/{employee}', [
            RoomEmployeesController::class,
            'destroy',
        ])->name('rooms.employees.destroy');

        Route::apiResource('stocks', StockController::class);

        Route::apiResource('time-sheets', TimeSheetController::class);

        Route::apiResource('users', UserController::class);

        // User Time Sheets
        Route::get('/users/{user}/time-sheets', [
            UserTimeSheetsController::class,
            'index',
        ])->name('users.time-sheets.index');
        Route::post('/users/{user}/time-sheets', [
            UserTimeSheetsController::class,
            'store',
        ])->name('users.time-sheets.store');

        Route::apiResource('employees', EmployeeController::class);

        // Employee Time Sheets
        Route::get('/employees/{employee}/time-sheets', [
            EmployeeTimeSheetsController::class,
            'index',
        ])->name('employees.time-sheets.index');
        Route::post('/employees/{employee}/time-sheets', [
            EmployeeTimeSheetsController::class,
            'store',
        ])->name('employees.time-sheets.store');

        // Employee Rooms
        Route::get('/employees/{employee}/rooms', [
            EmployeeRoomsController::class,
            'index',
        ])->name('employees.rooms.index');
        Route::post('/employees/{employee}/rooms/{room}', [
            EmployeeRoomsController::class,
            'store',
        ])->name('employees.rooms.store');
        Route::delete('/employees/{employee}/rooms/{room}', [
            EmployeeRoomsController::class,
            'destroy',
        ])->name('employees.rooms.destroy');

        // Employee Flights
        Route::get('/employees/{employee}/flights', [
            EmployeeFlightsController::class,
            'index',
        ])->name('employees.flights.index');
        Route::post('/employees/{employee}/flights/{flight}', [
            EmployeeFlightsController::class,
            'store',
        ])->name('employees.flights.store');
        Route::delete('/employees/{employee}/flights/{flight}', [
            EmployeeFlightsController::class,
            'destroy',
        ])->name('employees.flights.destroy');
    });
