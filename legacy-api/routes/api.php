<?php

use App\Http\Controllers\Api\AdministrationController;
use App\Http\Controllers\Api\AdministrationDepartmentsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CenterController;
use App\Http\Controllers\Api\CenterEmployeesController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DepartmentEmployeesController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeFlightsController;
use App\Http\Controllers\Api\EmployeeRoomsController;
use App\Http\Controllers\Api\EmployeeTimeSheetsController;
use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\FlightEmployeesController;
use App\Http\Controllers\Api\FlightPassengersController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\LocationEmployeesController;
use App\Http\Controllers\Api\PassengerController;
use App\Http\Controllers\Api\PassengerFlightsController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ResidenceController;
use App\Http\Controllers\Api\ResidenceRoomsController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\RoomEmployeesController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\TimeSheetController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserTimeSheetsController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::middleware('auth:sanctum')->name('api.')->group(function (): void {
    Route::get('/user', fn () => request()->user())->name('user');

    foreach ([
        'roles' => RoleController::class,
        'permissions' => PermissionController::class,
        'administrations' => AdministrationController::class,
        'centers' => CenterController::class,
        'departments' => DepartmentController::class,
        'flights' => FlightController::class,
        'locations' => LocationController::class,
        'passengers' => PassengerController::class,
        'residences' => ResidenceController::class,
        'rooms' => RoomController::class,
        'stocks' => StockController::class,
        'time-sheets' => TimeSheetController::class,
        'users' => UserController::class,
        'employees' => EmployeeController::class,
    ] as $resource => $controller) {
        Route::apiResource($resource, $controller);
    }

    Route::get('/administrations/{administration}/departments', [AdministrationDepartmentsController::class, 'index'])->name('administrations.departments.index');
    Route::post('/administrations/{administration}/departments', [AdministrationDepartmentsController::class, 'store'])->name('administrations.departments.store');
    Route::get('/centers/{center}/employees', [CenterEmployeesController::class, 'index'])->name('centers.employees.index');
    Route::post('/centers/{center}/employees', [CenterEmployeesController::class, 'store'])->name('centers.employees.store');
    Route::get('/departments/{department}/employees', [DepartmentEmployeesController::class, 'index'])->name('departments.employees.index');
    Route::post('/departments/{department}/employees', [DepartmentEmployeesController::class, 'store'])->name('departments.employees.store');
    Route::get('/locations/{location}/employees', [LocationEmployeesController::class, 'index'])->name('locations.employees.index');
    Route::post('/locations/{location}/employees', [LocationEmployeesController::class, 'store'])->name('locations.employees.store');
    Route::get('/residences/{residence}/rooms', [ResidenceRoomsController::class, 'index'])->name('residences.rooms.index');
    Route::post('/residences/{residence}/rooms', [ResidenceRoomsController::class, 'store'])->name('residences.rooms.store');
    Route::get('/users/{user}/time-sheets', [UserTimeSheetsController::class, 'index'])->name('users.time-sheets.index');
    Route::post('/users/{user}/time-sheets', [UserTimeSheetsController::class, 'store'])->name('users.time-sheets.store');
    Route::get('/employees/{employee}/time-sheets', [EmployeeTimeSheetsController::class, 'index'])->name('employees.time-sheets.index');
    Route::post('/employees/{employee}/time-sheets', [EmployeeTimeSheetsController::class, 'store'])->name('employees.time-sheets.store');
    Route::get('/employees/{employee}/rooms', [EmployeeRoomsController::class, 'index'])->name('employees.rooms.index');
    Route::post('/employees/{employee}/rooms/{room}', [EmployeeRoomsController::class, 'store'])->name('employees.rooms.store');
    Route::delete('/employees/{employee}/rooms/{room}', [EmployeeRoomsController::class, 'destroy'])->name('employees.rooms.destroy');
    Route::get('/employees/{employee}/flights', [EmployeeFlightsController::class, 'index'])->name('employees.flights.index');
    Route::post('/employees/{employee}/flights/{flight}', [EmployeeFlightsController::class, 'store'])->name('employees.flights.store');
    Route::delete('/employees/{employee}/flights/{flight}', [EmployeeFlightsController::class, 'destroy'])->name('employees.flights.destroy');
    Route::get('/flights/{flight}/passengers', [FlightPassengersController::class, 'index'])->name('flights.passengers.index');
    Route::post('/flights/{flight}/passengers/{passenger}', [FlightPassengersController::class, 'store'])->name('flights.passengers.store');
    Route::delete('/flights/{flight}/passengers/{passenger}', [FlightPassengersController::class, 'destroy'])->name('flights.passengers.destroy');
    Route::get('/flights/{flight}/employees', [FlightEmployeesController::class, 'index'])->name('flights.employees.index');
    Route::post('/flights/{flight}/employees/{employee}', [FlightEmployeesController::class, 'store'])->name('flights.employees.store');
    Route::delete('/flights/{flight}/employees/{employee}', [FlightEmployeesController::class, 'destroy'])->name('flights.employees.destroy');
    Route::get('/passengers/{passenger}/flights', [PassengerFlightsController::class, 'index'])->name('passengers.flights.index');
    Route::post('/passengers/{passenger}/flights/{flight}', [PassengerFlightsController::class, 'store'])->name('passengers.flights.store');
    Route::delete('/passengers/{passenger}/flights/{flight}', [PassengerFlightsController::class, 'destroy'])->name('passengers.flights.destroy');
    Route::get('/rooms/{room}/employees', [RoomEmployeesController::class, 'index'])->name('rooms.employees.index');
    Route::post('/rooms/{room}/employees/{employee}', [RoomEmployeesController::class, 'store'])->name('rooms.employees.store');
    Route::delete('/rooms/{room}/employees/{employee}', [RoomEmployeesController::class, 'destroy'])->name('rooms.employees.destroy');
});
