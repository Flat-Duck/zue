<?php

use App\Http\Controllers\ClinicApointmentController;
use App\Http\Controllers\ManagementScopeController;
use App\Http\Controllers\PlaneController;
use App\Http\Controllers\RunController;
use App\Imports\RoomsImport;
use App\Models\Employee;
use App\Models\Residence;
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
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\OccupationalInjuryReportController;

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

Route::post('/rr', function () {

    // $name = 1;
    // $type = "VILLA";
    // $re = Residence::where('name',$name)->where('type',$type)->first();
    // if(is_null($re)){
    //     $re = new Residence();
    //     $re->name =  $name;
    //     $re->type = $type;
    //     $re->save();
    // }
    // dd($re);
    Excel::import(new RoomsImport, request()->file('rooms'));

    return view('app.time_sheets.approve', compact('chunks', 'month_name', 'month_days', 'employees'));
})->name('rr');
// Route::get('/ss', function () {

//     $month_name = "April";
//     $chunk = Timesheet::whereHas('employee', function ($query) {
//     $query->whereNull('archived_at');
// })->whereMonth('day', 10)->whereYear('day', '2025')->limit('310')->get();

//     $chunks = $chunk->groupBy('employee_id')->chunk(8);
//     $first_chunk = $chunks->first()->first()->first();
//     $signatures['time_keeper']['sign'] = $first_chunk->time_keeper->signature->image_path;
//     $signatures['time_keeper']['name'] = $first_chunk->time_keeper->name;
//     $signatures['super_visor']['sign'] = $first_chunk->super_visor?->signature->image_path;
//     $signatures['super_visor']['name'] = $first_chunk->super_visor?->name;
//     $signatures['super_intendent']['sign'] = $first_chunk->super_intendent?->signature->image_path;
//     $signatures['super_intendent']['name'] = $first_chunk->super_intendent?->name;

//     $month_days = Carbon\Carbon::now()->month($month_name)->daysInMonth + 1;
//     $employees = Employee::pluck('english_name','number');
//     // return $chunks;
//     return view('app.time_sheets.approve', compact('chunks', 'month_name', 'month_days','employees','signatures'));
// })->name('time-sheets.approve');

Auth::routes();

Route::get('time-sheets/approve_preview', [TimeSheetController::class, 'approve_preview'])->name('time-sheets.approve_preview');
Route::get('time-sheets/approves', [TimeSheetController::class, 'approves'])->name('time-sheets.approves');
Route::get('time-sheets/approve', [TimeSheetController::class, 'approve'])->name('time-sheets.approve');





Route::post('time-sheets/print', [TimeSheetController::class, 'print'])->name('time-sheets.print');


Route::get('time-sheets/print_preview', [TimeSheetController::class, 'print_preview'])->name('time-sheets.print_preview');
Route::get('time-sheets/create/{employee}', [TimeSheetController::class, 'create'])->name('time-sheets.fill');
Route::get('time-sheets/edit/{employee}', [TimeSheetController::class, 'edit'])->name('time-sheets.revise');
Route::resource('time-sheets', TimeSheetController::class);





Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::prefix('/')
    ->middleware('auth')
    ->group(function () {
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);
        Route::resource('management-scopes', ManagementScopeController::class);

        Route::resource('injury-reports', OccupationalInjuryReportController::class);

        Route::resource('administrations', AdministrationController::class);
        Route::resource('centers', CenterController::class);

        Route::post('clinic/annual_screening/{employee}', [ClinicApointmentController::class, 'annual_screening'])->name('clinic.annual_screening.save');
        Route::get('clinic/annual_screening/{employee}', [ClinicApointmentController::class, 'annual_screening'])->name('clinic.annual_screening');
        Route::get('clinic/history/{employee}', [ClinicApointmentController::class, 'history'])->name('clinic.history');
        Route::get('clinic/diagnosis/{employee}', [ClinicApointmentController::class, 'diagnosis'])->name('clinic.diagnosis');
        Route::resource('clinic', ClinicApointmentController::class);//->name('clinic');
    

        Route::resource('departments', DepartmentController::class);
        Route::delete('flights/{flight}/approve', [FlightController::class, 'approve'])->name('flights.approve');
        Route::resource('flights', FlightController::class);
        Route::resource('locations', LocationController::class);
        Route::resource('passengers', PassengerController::class);
        Route::resource('residences', ResidenceController::class);
        Route::resource('planes', PlaneController::class);
        Route::resource('rooms', RoomController::class);
        Route::resource('stocks', StockController::class);
        Route::get('users/template', [UserController::class, 'downloadTemplate'])->name('users.template');
        Route::post('users/import', [UserController::class, 'import'])->name('users.import');
        Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
        Route::delete('users/impersonate', [UserController::class, 'stopImpersonation'])->name('users.impersonate.stop');
        Route::resource('users', UserController::class);
        Route::post('users/{user}/upload-signature', [UserController::class, 'uploadSignature'])->name('users.upload-signature');
        Route::get('dir', [EmployeeController::class, 'dir']);
        Route::get('employees/imports', [EmployeeController::class, 'imports']);
        Route::post('import-archived-employees', [EmployeeController::class, 'importArchivedEmployees'])->name('employees.import-archived-employees');
        Route::resource('employees', EmployeeController::class);

        Route::get('signature', [ProfileController::class, 'signature'])->name('signature.show');
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports/timesheets', [ReportController::class, 'timesheets'])->name('reports.timesheets');
        Route::post('reports/balances', [ReportController::class, 'balances'])->name('reports.balances');
        Route::post('reports/run', [ReportController::class, 'run'])->name('reports.run');
        Route::post('reports/to_date', [ReportController::class, 'to_date'])->name('reports.to_date');
        Route::post('reports/monthly-attendance', [ReportController::class, 'monthlyAttendance'])->name('reports.monthly-attendance');
        Route::get('operations', [OperationsController::class, 'index'])->name('operations.index');
        Route::post('operations/archive-by-timesheet', [OperationsController::class, 'archiveByTimesheet'])->name('operations.archive-by-timesheet');
        Route::post('operations/unarchive-by-number', [OperationsController::class, 'unarchiveByNumber'])->name('operations.unarchive-by-number');
        Route::post('operations/unarchive-all', [OperationsController::class, 'unarchiveAll'])->name('operations.unarchive-all');

        Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
        Route::post('maintenance/export', [MaintenanceController::class, 'export'])->name('maintenance.export');
        Route::post('maintenance/import', [MaintenanceController::class, 'import'])->name('maintenance.import');
        Route::post('maintenance/restore/{filename}', [MaintenanceController::class, 'restore'])->name('maintenance.restore');
        Route::get('maintenance/download/{filename}', [MaintenanceController::class, 'download'])->name('maintenance.download');
        Route::delete('maintenance/delete/{filename}', [MaintenanceController::class, 'delete'])->name('maintenance.delete');
        Route::post('maintenance/settings', [MaintenanceController::class, 'updateSettings'])->name('maintenance.settings.update');
        Route::post('maintenance/quick-backup', [MaintenanceController::class, 'runQuickBackup'])->name('maintenance.quick-backup');
    });

include __DIR__ . '/appraisals.php';
