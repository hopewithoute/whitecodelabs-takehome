<?php

use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\CompanyEmployeeController;
use App\Http\Controllers\Api\V1\CompanyProjectController;
use App\Http\Controllers\Api\V1\CompanyTaskController;
use App\Http\Controllers\Api\V1\TimeEntryController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/api/v1', 302);

Route::prefix('v1')->group(function (): void {
    Route::get('companies', [CompanyController::class, 'index']);
    Route::get('companies/{company}/employees', [CompanyEmployeeController::class, 'index']);
    Route::get('companies/{company}/projects', [CompanyProjectController::class, 'index']);
    Route::get('companies/{company}/tasks', [CompanyTaskController::class, 'index']);
    Route::get('time-entries', [TimeEntryController::class, 'index']);
    Route::post('time-entries', [TimeEntryController::class, 'store']);
    Route::patch('time-entries/{timeEntry}', [TimeEntryController::class, 'update']);
});
