<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AgentDashboardController;
use App\Http\Controllers\TicketDetailController;

// Homepage
Route::get('/', function () {
    return redirect()->route('tickets.create');
});

// Public customer ticket submission
Route::get('/tickets/create', [TicketController::class, 'create'])
    ->name('tickets.create');

Route::post('/tickets', [TicketController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('tickets.store');

Route::get('/tickets/{ticket}/success', [TicketController::class, 'success'])
    ->name('tickets.success');

// Ticket details
Route::get('/tickets/{ticket}', [TicketDetailController::class, 'show'])
    ->middleware('auth')
    ->name('tickets.show');

// Generate AI reply
Route::post('/tickets/{ticket}/generate-reply', [TicketDetailController::class, 'generateReply'])
    ->middleware(['auth', 'throttle:10,1'])
    ->name('tickets.generateReply');

// Save agent reply
Route::post('/tickets/{ticket}/save-reply', [TicketDetailController::class, 'saveReply'])
    ->middleware('auth')
    ->name('tickets.saveReply');

// Update ticket classification
Route::post('/tickets/{ticket}/update-classification', [TicketDetailController::class, 'updateClassification'])
    ->middleware('auth')
    ->name('tickets.updateClassification');

// Retry AI classification
Route::post('/tickets/{ticket}/retry-classification', [TicketDetailController::class, 'retryClassification'])
    ->middleware('auth')
    ->name('tickets.retryClassification');

// Agent authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Agent dashboard
Route::get('/dashboard', [AgentDashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');