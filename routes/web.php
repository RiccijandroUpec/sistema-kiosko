<?php

use App\Http\Controllers\KioskoController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;

// ===== RUTAS PÚBLICAS DEL KIOSKO =====

// Página de inicio
Route::get('/', [KioskoController::class, 'index'])->name('kiosko.index');

// Flujo de impresión
Route::get('/subir', [KioskoController::class, 'uploadForm'])->name('kiosko.upload');
Route::get('/subir-pdf', [KioskoController::class, 'uploadForm'])->name('pdf.upload'); // Alias para el menú
Route::post('/subir', [KioskoController::class, 'uploadPdf'])->name('kiosko.upload-pdf');

Route::get('/configurar/{pdf}', [KioskoController::class, 'configureForm'])->name('kiosko.configure');
Route::post('/crear-trabajo/{pdf}', [KioskoController::class, 'createPrintJob'])->name('kiosko.create-job');

Route::get('/pago/{printJob}', [KioskoController::class, 'paymentForm'])->name('kiosko.payment');
Route::get('/estado/{jobReference}', [KioskoController::class, 'status'])->name('kiosko.status');
Route::get('/buscar', [KioskoController::class, 'searchForm'])->name('kiosko.search-form');
Route::post('/buscar', [KioskoController::class, 'searchJob'])->name('kiosko.search');

// ===== RUTAS DEL ADMIN (solo login de admin) =====

Route::middleware('auth')->group(function () {
    // Profile del admin
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard del admin
    Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard'); // Alias para compatibilidad con Laravel Breeze/Auth

    // Gestión de trabajos
    Route::get('/admin/trabajos', [AdminController::class, 'printJobs'])->name('admin.print-jobs');
    Route::get('/admin/trabajos/{printJob}', [AdminController::class, 'jobDetails'])->name('admin.job-details');
    Route::get('/admin/trabajos/{printJob}/descargar', [AdminController::class, 'downloadPdf'])->name('admin.download-pdf');
    Route::post('/admin/trabajos/{printJob}/impreso', [AdminController::class, 'markAsPrinted'])->name('admin.mark-printed');
    Route::post('/admin/trabajos/{printJob}/cancelar', [AdminController::class, 'cancelJob'])->name('admin.cancel-job');
    Route::delete('/admin/trabajos/{printJob}', [AdminController::class, 'deleteJob'])->name('admin.delete-job');

    // Gestión de pagos
    Route::post('/admin/pagos/{payment}/confirmar', [PaymentController::class, 'confirmPayment'])->name('admin.confirm-payment');
    Route::post('/admin/pagos/{payment}/cancelar', [PaymentController::class, 'cancelPayment'])->name('admin.cancel-payment');

    // Transacciones y reportes
    Route::get('/admin/transacciones', [AdminController::class, 'transactions'])->name('admin.transactions');
    Route::get('/admin/estadisticas', [AdminController::class, 'statistics'])->name('admin.statistics');
});

// ===== RUTAS DE AUTENTICACIÓN =====
require __DIR__.'/auth.php';

// Twilio webhook for incoming WhatsApp messages
Route::post('/webhook/twilio', [WhatsAppController::class, 'webhook']);

