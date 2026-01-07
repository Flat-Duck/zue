<?php

use App\Http\Controllers\Appraisals\EmployeeAppraisalFormController;
use App\Http\Controllers\Appraisals\AppraisalItemController;
use App\Http\Controllers\Appraisals\AppraisalFormController;
use App\Http\Controllers\Appraisals\AppraisalFormVersionController;
use App\Http\Controllers\Appraisals\AppraisalFormVersionItemsController;
use App\Http\Controllers\Appraisals\AppraisalPeriodController;
use App\Http\Controllers\Appraisals\AppraisalReviewController;
use App\Http\Controllers\Appraisals\AppraisalOfficialController;

Route::middleware(['auth'])->prefix('/appraisals')->name('appraisals.')->group(function () {

    // Items
    Route::get('/items', [AppraisalItemController::class, 'index'])->name('items.index');
    Route::get('/items/create', [AppraisalItemController::class, 'create'])->name('items.create');
    Route::post('/items', [AppraisalItemController::class, 'store'])->name('items.store');
    Route::get('/items/{item}/edit', [AppraisalItemController::class, 'edit'])->name('items.edit');
    Route::put('/items/{item}', [AppraisalItemController::class, 'update'])->name('items.update');

    // Forms
    Route::get('/forms', [AppraisalFormController::class, 'index'])->name('forms.index');
    Route::get('/forms/create', [AppraisalFormController::class, 'create'])->name('forms.create');
    Route::post('/forms', [AppraisalFormController::class, 'store'])->name('forms.store');
    Route::get('/forms/{form}/edit', [AppraisalFormController::class, 'edit'])->name('forms.edit');
    Route::put('/forms/{form}', [AppraisalFormController::class, 'update'])->name('forms.update');

    // Versions per form
    Route::get('/forms/{form}/versions', [AppraisalFormVersionController::class, 'index'])->name('versions.index');
    Route::post('/forms/{form}/versions', [AppraisalFormVersionController::class, 'store'])->name('versions.store');
    Route::post('/versions/{version}/activate', [AppraisalFormVersionController::class, 'activate'])->name('versions.activate');

    // Manage items inside a version
    Route::get('/versions/{version}/items', [AppraisalFormVersionItemsController::class, 'edit'])->name('version-items.edit');
    Route::post('/versions/{version}/items', [AppraisalFormVersionItemsController::class, 'addItem'])->name('version-items.add');
    Route::put('/versions/{version}/items', [AppraisalFormVersionItemsController::class, 'bulkUpdate'])->name('version-items.update');
    Route::delete('/versions/{version}/items/{versionItem}', [AppraisalFormVersionItemsController::class, 'destroy'])->name('version-items.destroy');

    // Route::middleware(['auth'])->group(function () {

    Route::resource('periods', AppraisalPeriodController::class);

    Route::post('/periods/generate-yearly', [AppraisalPeriodController::class, 'generateYearly'])
        ->name('periods.generate-yearly');

    // Reviews (manager submissions)
    Route::get('/reviews', [AppraisalReviewController::class, 'index'])
        ->name('reviews.index');

    Route::get('/reviews/create', [AppraisalReviewController::class, 'create'])
        ->name('reviews.create');

    Route::post('/reviews', [AppraisalReviewController::class, 'store'])
        ->name('reviews.store');

    Route::get('/reviews/{review}/edit', [AppraisalReviewController::class, 'edit'])
        ->name('reviews.edit');

    Route::put('/reviews/{review}', [AppraisalReviewController::class, 'update'])
        ->name('reviews.update');

    Route::post('/reviews/{review}/submit', [AppraisalReviewController::class, 'submit'])
        ->name('reviews.submit');

    // Official (aggregated)
    Route::get('/official', [AppraisalOfficialController::class, 'index'])
        ->name('official.index');

    Route::get('/official/{period}/{employee}', [AppraisalOfficialController::class, 'show'])
        ->name('official.show');

    Route::post('/official/{period}/{employee}/finalize', [AppraisalOfficialController::class, 'finalize'])
        ->name('official.finalize');

    Route::post('/official/{period}/{employee}/approve', [AppraisalOfficialController::class, 'approve'])
        ->name('official.approve');

    Route::get('/employees/{employee}/appraisal-form', [EmployeeAppraisalFormController::class, 'edit'])
        ->name('employees.appraisal-form.edit');

    Route::put('/employees/{employee}/appraisal-form', [EmployeeAppraisalFormController::class, 'update'])
        ->name('employees.appraisal-form.update');
});

// });
