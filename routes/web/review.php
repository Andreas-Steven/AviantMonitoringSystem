<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Review\AttendanceReviewCaseController;

Route::prefix('review')->name('review.')->group(function () {
    Route::get('attendance-cases', [AttendanceReviewCaseController::class, 'index'])
        ->middleware('permission:attendance_review.view')
        ->name('attendance-cases.index');

    Route::get('attendance-cases/{attendanceReviewCase}', [AttendanceReviewCaseController::class, 'show'])
        ->middleware('permission:attendance_review.view')
        ->name('attendance-cases.show');

    Route::put('attendance-cases/{attendanceReviewCase}', [AttendanceReviewCaseController::class, 'update'])
        ->middleware('permission:attendance_review.manage')
        ->name('attendance-cases.update');
});