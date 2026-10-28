<?php

use App\Http\Controllers\CreditDecisionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiskAssessmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/decisions', [CreditDecisionController::class, 'index'])->name('decisions.index');
    Route::post('/invoices/{invoice}/decision', [CreditDecisionController::class, 'store'])->name('decisions.store');

    Route::resource('clients', \App\Http\Controllers\ClientController::class);
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class)->except(['show']);

        Route::get('/assess', [RiskAssessmentController::class, 'create'])->name('assess.create');
    Route::get('/assess/result', [RiskAssessmentController::class, 'proposal'])->name('assess.result');
    Route::post('/clients/{client}/decision', [RiskAssessmentController::class, 'storeDecision'])
        ->name('assess.decision');

        Route::get('/dashboard/export/pdf', [\App\Http\Controllers\DashboardController::class, 'exportPdf'])
        ->name('dashboard.pdf');
    Route::get('/dashboard/export/csv', [\App\Http\Controllers\DashboardController::class, 'exportCsv'])
        ->name('dashboard.csv');    

    Route::get('/invoices/{invoice}/risk', [RiskAssessmentController::class, 'show'])
        ->name('invoices.risk');

    Route::get('budgets', [\App\Http\Controllers\MonthlyBudgetController::class, 'index'])->name('budgets.index');
    Route::post('budgets', [\App\Http\Controllers\MonthlyBudgetController::class, 'store'])->name('budgets.store');
    Route::delete('budgets/{budget}', [\App\Http\Controllers\MonthlyBudgetController::class, 'destroy'])->name('budgets.destroy');
});

require __DIR__.'/auth.php';