<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Autenticação: login à mão em Livewire, sem Breeze/Fortify/npm.
Route::view('/login', 'auth.login')->middleware('guest')->name('login');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Tudo abaixo exige sessão autenticada. Guest cai em /login (middleware auth).
Route::middleware('auth')->group(function () {
    // Painel (interface única) — qualquer usuário logado gere missões.
    Route::view('/', 'painel')->name('painel');

    // Administração: só is_admin (militares e usuários).
    Route::middleware('admin')->group(function () {
        Route::view('/militares', 'militares')->name('militares.manage');
        Route::view('/usuarios', 'usuarios')->name('usuarios.manage');
    });
});
