<?php

use App\Http\Controllers\KioskoController;
use App\Http\Controllers\KioskPanelAuthController;
use App\Http\Controllers\KioskPanelController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\Admin\KioskController as AdminKioskController;

// ===== RUTAS PÚBLICAS DEL KIOSKO =====

// Página de inicio
Route::get('/', [KioskoController::class, 'index'])->name('kiosko.index');

// Vista previa pública del panel central para demostración local
Route::get('/central-preview', [AdminController::class, 'dashboard'])->name('central.preview');

// Política de Privacidad
Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

// Eliminación de datos
Route::get('/data-deletion', function () {
    return view('data-deletion');
})->name('data-deletion');

// Flujo de impresión
Route::get('/subir', [KioskoController::class, 'uploadForm'])->name('kiosko.upload');
Route::get('/subir-pdf', [KioskoController::class, 'uploadForm'])->name('pdf.upload'); // Alias para el menú
Route::post('/subir', [KioskoController::class, 'uploadPdf'])->name('kiosko.upload-pdf');
Route::get('/whatsapp-qr', [KioskoController::class, 'generateQr'])->name('kiosko.whatsapp-qr');
Route::get('/kioskos/{kiosk}/whatsapp-qr', [KioskoController::class, 'generateKioskQr'])->name('kiosko.whatsapp-qr.kiosk');

Route::get('/configurar/{pdf}', [KioskoController::class, 'configureForm'])->name('kiosko.configure');
Route::post('/crear-trabajo/{pdf}', [KioskoController::class, 'createPrintJob'])->name('kiosko.create-job');

Route::get('/pago/{printJob}', [KioskoController::class, 'paymentForm'])->name('kiosko.payment');
Route::get('/estado/{jobReference}', [KioskoController::class, 'status'])->name('kiosko.status');
Route::get('/buscar', [KioskoController::class, 'searchForm'])->name('kiosko.search-form');
Route::post('/buscar', [KioskoController::class, 'searchJob'])->name('kiosko.search');
Route::post('/api/release-with-pin/{printJob}', [KioskoController::class, 'releaseWithPin'])->name('kiosko.api.release-with-pin');

// ===== PANEL LOCAL POR KIOSKO (PIN) =====
Route::get('/kiosko/panel/login', [KioskPanelAuthController::class, 'showLoginForm'])->name('kiosk.panel.login.form');
Route::post('/kiosko/panel/login', [KioskPanelAuthController::class, 'login'])->name('kiosk.panel.login.submit');

Route::middleware('kiosk.pin')->group(function () {
    Route::get('/kiosko/panel', [KioskPanelController::class, 'dashboard'])->name('kiosk.panel.dashboard');
    Route::post('/kiosko/panel/logout', [KioskPanelAuthController::class, 'logout'])->name('kiosk.panel.logout');
    Route::post('/kiosko/panel/trabajos/{printJob}/impreso', [KioskPanelController::class, 'markAsPrinted'])->name('kiosk.panel.mark-printed');
    Route::post('/kiosko/panel/trabajos/{printJob}/cancelar', [KioskPanelController::class, 'cancelJob'])->name('kiosk.panel.cancel-job');
});

// ===== RUTAS DEL ADMIN (solo login de admin) =====

Route::middleware('auth')->group(function () {
    // Profile del admin
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard admin unificado
    Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Gestión de kioskos
    Route::get('/admin/kioskos', [AdminKioskController::class, 'index'])->name('admin.kiosks.index');
    Route::get('/admin/kioskos/{kiosk}/qr', [AdminKioskController::class, 'printableQr'])->name('admin.kiosks.qr');
    Route::get('/admin/kioskos/{kiosk}/qr.pdf', [AdminKioskController::class, 'printableQrPdf'])->name('admin.kiosks.qr-pdf');
    Route::post('/admin/kioskos', [AdminKioskController::class, 'store'])->name('admin.kiosks.store');
    Route::patch('/admin/kioskos/{kiosk}', [AdminKioskController::class, 'update'])->name('admin.kiosks.update');
    Route::delete('/admin/kioskos/{kiosk}', [AdminKioskController::class, 'destroy'])->name('admin.kiosks.destroy');

    // APIs para el panel admin
    Route::get('/admin/api/stats', [AdminController::class, 'apiStats'])->name('admin.api.stats');
    Route::get('/admin/api/jobs', [AdminController::class, 'apiJobs'])->name('admin.api.jobs');
    Route::get('/admin/api/pending-payments', [AdminController::class, 'apiPendingPayments'])->name('admin.api.pending-payments');

    // Pruebas de WhatsApp Business
    Route::get('/admin/whatsapp/validate-credentials', [WhatsAppController::class, 'validateCredentials'])->name('admin.whatsapp.validate-credentials');
    Route::post('/admin/whatsapp/test-message', [WhatsAppController::class, 'sendTestMessage'])->name('admin.whatsapp.test-message');

    // Actualizar precios
    Route::post('/admin/update-prices', [AdminController::class, 'updatePrices'])->name('admin.update-prices');

    // Gestión de trabajos (legacy, mantener para compatibilidad)
    Route::get('/admin/trabajos', [AdminController::class, 'printJobs'])->name('admin.print-jobs');
    Route::get('/admin/trabajos/{printJob}', [AdminController::class, 'jobDetails'])->name('admin.job-details');
    Route::get('/admin/trabajos/{printJob}/descargar', [AdminController::class, 'downloadPdf'])->name('admin.download-pdf');
    Route::post('/admin/trabajos/{printJob}/impreso', [AdminController::class, 'markAsPrinted'])->name('admin.mark-printed');
    Route::post('/admin/trabajos/{printJob}/cancelar', [AdminController::class, 'cancelJob'])->name('admin.cancel-job');
    Route::delete('/admin/trabajos/{printJob}', [AdminController::class, 'deleteJob'])->name('admin.delete-job');

    // Confirmar pago
    Route::post('/admin/trabajos/{printJob}/confirmar-pago', [AdminController::class, 'markAsPrinted'])->name('admin.confirm-job-payment');

    // Gestión de pagos
    Route::post('/admin/pagos/{payment}/confirmar', [PaymentController::class, 'confirmPayment'])->name('admin.confirm-payment');
    Route::post('/admin/pagos/{payment}/cancelar', [PaymentController::class, 'cancelPayment'])->name('admin.cancel-payment');

    // Transacciones y reportes
    Route::get('/admin/transacciones', [AdminController::class, 'transactions'])->name('admin.transactions');
    Route::get('/admin/estadisticas', [AdminController::class, 'statistics'])->name('admin.statistics');
});

// ===== RUTAS DE AUTENTICACIÓN =====
require __DIR__.'/auth.php';


// FINAL BOT ROUTE (CSRF EXEMPT)
Route::post('/webhook-bot', [App\Http\Controllers\WhatsAppController::class, 'webhook']);
Route::get('/webhook-bot', [App\Http\Controllers\WhatsAppController::class, 'webhook']);

// PIN Login
Route::get('/login/pin', [App\Http\Controllers\Auth\PinLoginController::class, 'showForm'])->name('login.pin');
Route::post('/login/pin', [App\Http\Controllers\Auth\PinLoginController::class, 'login']);
