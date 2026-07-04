<?php

use App\Http\Controllers\MissionController;
use Illuminate\Support\Facades\Route;

// Painel (interface única)
Route::view('/', 'painel')->name('painel');

// Cadastro de militares (tela em Livewire)
Route::view('/militares', 'militares')->name('militares.manage');

// API interna consumida pela interface (mesma origem, protegida por CSRF)
Route::prefix('missions')->group(function () {
    Route::get('/', [MissionController::class, 'index'])->name('missions.index');
    Route::post('/', [MissionController::class, 'store'])->name('missions.store');
    Route::post('/reset', [MissionController::class, 'reset'])->name('missions.reset');
    Route::put('/{mission}', [MissionController::class, 'update'])->name('missions.update');
    Route::delete('/{mission}', [MissionController::class, 'destroy'])->name('missions.destroy');
});
