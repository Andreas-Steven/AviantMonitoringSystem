<?php

use App\Http\Controllers\Web\Master\BranchController;
use App\Http\Controllers\Web\Master\DepartmentController;
use App\Http\Controllers\Web\Master\EmployeeAssignmentController;
use App\Http\Controllers\Web\Master\EmployeeController;
use App\Http\Controllers\Web\Master\EmploymentTypeController;
use App\Http\Controllers\Web\Master\GradeController;
use App\Http\Controllers\Web\Master\PositionRoleController;
use App\Http\Controllers\Web\Master\EmployeeImportController;
use App\Http\Controllers\Web\Master\EmployeeAssignmentImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('master')->name('master.')->group(function () {
    Route::get('branches', [BranchController::class, 'index'])
        ->middleware('permission:branch.view')
        ->name('branches.index');
    Route::get('branches/create', [BranchController::class, 'create'])
        ->middleware('permission:branch.manage')
        ->name('branches.create');
    Route::post('branches', [BranchController::class, 'store'])
        ->middleware('permission:branch.manage')
        ->name('branches.store');
    Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])
        ->middleware('permission:branch.manage')
        ->name('branches.edit');
    Route::put('branches/{branch}', [BranchController::class, 'update'])
        ->middleware('permission:branch.manage')
        ->name('branches.update');

    Route::get('departments', [DepartmentController::class, 'index'])
        ->middleware('permission:department.view')
        ->name('departments.index');
    Route::get('departments/create', [DepartmentController::class, 'create'])
        ->middleware('permission:department.manage')
        ->name('departments.create');
    Route::post('departments', [DepartmentController::class, 'store'])
        ->middleware('permission:department.manage')
        ->name('departments.store');
    Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])
        ->middleware('permission:department.manage')
        ->name('departments.edit');
    Route::put('departments/{department}', [DepartmentController::class, 'update'])
        ->middleware('permission:department.manage')
        ->name('departments.update');

    Route::get('grades', [GradeController::class, 'index'])
        ->middleware('permission:grade.view')
        ->name('grades.index');
    Route::get('grades/create', [GradeController::class, 'create'])
        ->middleware('permission:grade.manage')
        ->name('grades.create');
    Route::post('grades', [GradeController::class, 'store'])
        ->middleware('permission:grade.manage')
        ->name('grades.store');
    Route::get('grades/{grade}/edit', [GradeController::class, 'edit'])
        ->middleware('permission:grade.manage')
        ->name('grades.edit');
    Route::put('grades/{grade}', [GradeController::class, 'update'])
        ->middleware('permission:grade.manage')
        ->name('grades.update');

    Route::get('employment-types', [EmploymentTypeController::class, 'index'])
        ->middleware('permission:employment_type.view')
        ->name('employment-types.index');
    Route::get('employment-types/create', [EmploymentTypeController::class, 'create'])
        ->middleware('permission:employment_type.manage')
        ->name('employment-types.create');
    Route::post('employment-types', [EmploymentTypeController::class, 'store'])
        ->middleware('permission:employment_type.manage')
        ->name('employment-types.store');
    Route::get('employment-types/{employment_type}/edit', [EmploymentTypeController::class, 'edit'])
        ->middleware('permission:employment_type.manage')
        ->name('employment-types.edit');
    Route::put('employment-types/{employment_type}', [EmploymentTypeController::class, 'update'])
        ->middleware('permission:employment_type.manage')
        ->name('employment-types.update');

    Route::get('position-roles', [PositionRoleController::class, 'index'])
        ->middleware('permission:position_role.view')
        ->name('position-roles.index');
    Route::get('position-roles/create', [PositionRoleController::class, 'create'])
        ->middleware('permission:position_role.manage')
        ->name('position-roles.create');
    Route::post('position-roles', [PositionRoleController::class, 'store'])
        ->middleware('permission:position_role.manage')
        ->name('position-roles.store');
    Route::get('position-roles/{position_role}/edit', [PositionRoleController::class, 'edit'])
        ->middleware('permission:position_role.manage')
        ->name('position-roles.edit');
    Route::put('position-roles/{position_role}', [PositionRoleController::class, 'update'])
        ->middleware('permission:position_role.manage')
        ->name('position-roles.update');

    Route::get('employees', [EmployeeController::class, 'index'])
        ->middleware('permission:employee.view')
        ->name('employees.index');
    Route::get('employees/create', [EmployeeController::class, 'create'])
        ->middleware('permission:employee.manage')
        ->name('employees.create');
    Route::post('employees', [EmployeeController::class, 'store'])
        ->middleware('permission:employee.manage')
        ->name('employees.store');

    Route::get('/employees/import', [EmployeeImportController::class, 'create'])
        ->middleware('permission:employee.manage')
        ->name('employees.import.create');

    Route::post('/employees/import', [EmployeeImportController::class, 'store'])
        ->middleware('permission:employee.manage')
        ->name('employees.import.store');

    Route::get('employees/{employee}', [EmployeeController::class, 'show'])
        ->middleware('permission:employee.view')
        ->name('employees.show');
    Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])
        ->middleware('permission:employee.manage')
        ->name('employees.edit');
    Route::put('employees/{employee}', [EmployeeController::class, 'update'])
        ->middleware('permission:employee.manage')
        ->name('employees.update');

    Route::get('employee-assignments', [EmployeeAssignmentController::class, 'index'])
        ->middleware('permission:assignment.view')
        ->name('employee-assignments.index');

    Route::get('employee-assignments/create', [EmployeeAssignmentController::class, 'create'])
        ->middleware('permission:assignment.manage')
        ->name('employee-assignments.create');

    Route::post('employee-assignments', [EmployeeAssignmentController::class, 'store'])
        ->middleware('permission:assignment.manage')
        ->name('employee-assignments.store');

    Route::get('/employee-assignments/import', [EmployeeAssignmentImportController::class, 'create'])
        ->middleware('permission:assignment.manage')
        ->name('employee-assignments.import.create');

    Route::post('/employee-assignments/import', [EmployeeAssignmentImportController::class, 'store'])
        ->middleware('permission:assignment.manage')
        ->name('employee-assignments.import.store');

    Route::get('employee-assignments/coverage', [EmployeeAssignmentController::class, 'coverage'])
        ->middleware('permission:assignment.view')
        ->name('employee-assignments.coverage');

    Route::get('employee-assignments/bulk-create', [EmployeeAssignmentController::class, 'bulkCreate'])
        ->middleware('permission:assignment.manage')
        ->name('employee-assignments.bulk-create');

    Route::post('employee-assignments/bulk-store', [EmployeeAssignmentController::class, 'bulkStore'])
        ->middleware('permission:assignment.manage')
        ->name('employee-assignments.bulk-store');

    Route::get('employee-assignments/{employee_assignment}', [EmployeeAssignmentController::class, 'show'])
        ->middleware('permission:assignment.view')
        ->name('employee-assignments.show');

    Route::get('employee-assignments/{employee_assignment}/edit', [EmployeeAssignmentController::class, 'edit'])
        ->middleware('permission:assignment.manage')
        ->name('employee-assignments.edit');

    Route::put('employee-assignments/{employee_assignment}', [EmployeeAssignmentController::class, 'update'])
        ->middleware('permission:assignment.manage')
        ->name('employee-assignments.update');
    
});
