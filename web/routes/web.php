<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CreditDecisionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceImportController;
use App\Http\Controllers\MonthlyBudgetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\RiskAssessmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // decisions (FR09)
    Route::get('/decisions', [CreditDecisionController::class, 'index'])->name('decisions.index');
    Route::post('/invoices/{invoice}/decision', [CreditDecisionController::class, 'store'])->name('decisions.store');

    // clients and invoices
    Route::resource('clients', ClientController::class);

    Route::get('/invoices/import', [InvoiceImportController::class, 'create'])->name('invoices.import');
    Route::get('/invoices/import/template', [InvoiceImportController::class, 'template'])->name('invoices.import.template');
    Route::post('/invoices/import', [InvoiceImportController::class, 'store'])->name('invoices.import.store');

    Route::resource('invoices', InvoiceController::class)->except(['show']);

    // risk assessment (FR05 to FR08)
    Route::get('/invoices/{invoice}/risk', [RiskAssessmentController::class, 'show'])->name('invoices.risk');
    Route::get('/assess', [RiskAssessmentController::class, 'create'])->name('assess.create');
    Route::get('/assess/result', [RiskAssessmentController::class, 'proposal'])->name('assess.result');
    Route::post('/clients/{client}/decision', [RiskAssessmentController::class, 'storeDecision'])->name('assess.decision');

    // reports (FR11)
    Route::get('/reports', [DashboardController::class, 'reports'])->name('reports.index');
    Route::get('/reports/pdf', [DashboardController::class, 'exportPdf'])->name('dashboard.pdf');
    Route::get('/reports/csv', [DashboardController::class, 'exportCsv'])->name('dashboard.csv');

    // payment reminders
    Route::get('/reminders', [ReminderController::class, 'index'])->name('reminders.index');

    // monthly budget (FR04)
    Route::get('budgets', [MonthlyBudgetController::class, 'index'])->name('budgets.index');
    Route::post('budgets', [MonthlyBudgetController::class, 'store'])->name('budgets.store');
    Route::get('budgets/{budget}/edit', [MonthlyBudgetController::class, 'edit'])->name('budgets.edit');
    Route::put('budgets/{budget}', [MonthlyBudgetController::class, 'update'])->name('budgets.update');
    Route::delete('budgets/{budget}', [MonthlyBudgetController::class, 'destroy'])->name('budgets.destroy');
});

require __DIR__.'/auth.php';