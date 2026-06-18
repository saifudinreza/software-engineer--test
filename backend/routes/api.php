<?php

use App\Http\Controllers\FormController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/form', [FormController::class, 'show']);
Route::post('/submissions', [SubmissionController::class, 'store']);
Route::get('/submissions/{submission}', [SubmissionController::class, 'show']);
