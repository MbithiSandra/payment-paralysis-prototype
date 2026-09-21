<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::resource('clients', \App\Http\Controllers\ClientController::class);
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class)->except(['show']);
    Route::get('budgets', [\App\Http\Controllers\MonthlyBudgetController::class, 'index'])->name('budgets.index');
    Route::post('budgets', [\App\Http\Controllers\MonthlyBudgetController::class, 'store'])->name('budgets.store');
    Route::delete('budgets/{budget}', [\App\Http\Controllers\MonthlyBudgetController::class, 'destroy'])->name('budgets.destroy');

});

require __DIR__.'/auth.php';
