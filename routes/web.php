<?php

use App\Livewire\CheckIn\Index as CheckInIndex;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Invoices\Index as InvoicesIndex;
use App\Livewire\Members\Index as MembersIndex;
use App\Livewire\Plans\Index as PlansIndex;
use App\Livewire\PtSessions\Index as PtSessionsIndex;
use App\Livewire\Trainers\Index as TrainersIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('check-in.index')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Frontdesk Terminal
    Route::get('check-in', CheckInIndex::class)->name('check-in.index');

    Route::get('dashboard', DashboardIndex::class)->name('dashboard');

    // Master Data
    Route::get('members', MembersIndex::class)->name('members.index');
    Route::get('trainers', TrainersIndex::class)->name('trainers.index');
    Route::get('plans', PlansIndex::class)->name('plans.index');

    // Transaksi & Layanan
    Route::get('invoices', InvoicesIndex::class)->name('invoices.index');
    Route::get('pt-sessions', PtSessionsIndex::class)->name('pt-sessions.index');
});

require __DIR__.'/settings.php';
