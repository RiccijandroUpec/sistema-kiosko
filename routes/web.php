<?php

use App\Http\Controllers\KioskoController;
use App\Http\Controllers\KioskPanelAuthController;
use App\Http\Controllers\KioskPanelController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;

// ===== RUTAS PÚBLICAS DEL KIOSKO =====

// Página de inicio
Route::get('/', [KioskoController::class, 'index'])->name('kiosko.index');

// Política de Privacidad
Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

// Eliminación de datos
Route::get('/data-deletion', function () {
    return view('data-deletion');
})->name('data-deletion');

// URL fija por kiosko (ej. /k/kiosko1) - evita que el cliente elija el lugar equivocado
Route::get('/k/{slug}', [KioskoController::class, 'enterKiosk'])->name('kiosko.enter');

// Flujo de impresión
// La pagina generica de /subir ya no existe: cada kiosko entra por su propio /k/{slug}.
// Estos dos nombres de ruta se mantienen porque otras vistas (panel, privacy-policy) los referencian.
Route::get('/subir', fn () => redirect()->route('kiosko.index'))->name('kiosko.upload');
Route::get('/subir-pdf', fn () => redirect()->route('kiosko.index'))->name('pdf.upload');
Route::post('/subir', [KioskoController::class, 'uploadPdf'])->middleware('throttle:20,1')->name('kiosko.upload-pdf');
Route::get('/whatsapp-qr', [KioskoController::class, 'generateQr'])->name('kiosko.whatsapp-qr');
Route::get('/kioskos/{kiosk}/whatsapp-qr', [KioskoController::class, 'generateKioskQr'])->name('kiosko.whatsapp-qr.kiosk');
Route::get('/kioskos/{kiosk}/poster', [KioskoController::class, 'poster'])->name('kiosko.poster');
Route::view('/qrs', 'qrs')->name('kiosko.qrs');

Route::get('/configurar/{pdf}/{kiosko?}', [KioskoController::class, 'configureForm'])->name('kiosko.configure');
Route::post('/crear-trabajo/{pdf}', [KioskoController::class, 'createPrintJob'])->middleware('throttle:20,1')->name('kiosko.create-job');

Route::get('/pago/{printJob}', [KioskoController::class, 'paymentForm'])->name('kiosko.payment');
Route::post('/pago/{printJob}/referencia', [KioskoController::class, 'saveReference'])->middleware('throttle:20,1')->name('kiosko.save-reference');
Route::get('/estado/{jobReference}', [KioskoController::class, 'status'])->name('kiosko.status');
Route::get('/buscar', [KioskoController::class, 'searchForm'])->name('kiosko.search-form');
Route::post('/buscar', [KioskoController::class, 'searchJob'])->name('kiosko.search');
Route::post('/api/release-with-pin/{printJob}', [KioskoController::class, 'releaseWithPin'])
    ->middleware('throttle:5,1')
    ->name('kiosko.api.release-with-pin');

// ===== PANEL LOCAL POR KIOSKO (PIN) =====
Route::get('/kiosko/panel/login', [KioskPanelAuthController::class, 'showLoginForm'])->name('kiosk.panel.login.form');
Route::post('/kiosko/panel/login', [KioskPanelAuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('kiosk.panel.login.submit');

Route::middleware('kiosk.pin')->group(function () {
    Route::get('/kiosko/panel', [KioskPanelController::class, 'dashboard'])->name('kiosk.panel.dashboard');
    Route::post('/kiosko/panel/logout', [KioskPanelAuthController::class, 'logout'])->name('kiosk.panel.logout');
    Route::post('/kiosko/panel/trabajos/{printJob}/impreso', [KioskPanelController::class, 'markAsPrinted'])->name('kiosk.panel.mark-printed');
    Route::post('/kiosko/panel/trabajos/{printJob}/cancelar', [KioskPanelController::class, 'cancelJob'])->name('kiosk.panel.cancel-job');
});

// ===== RUTAS DE AUTENTICACIÓN =====
require __DIR__.'/auth.php';


// FINAL BOT ROUTE (CSRF EXEMPT)
Route::post('/webhook-bot', [App\Http\Controllers\WhatsAppController::class, 'webhook']);
Route::get('/webhook-bot', [App\Http\Controllers\WhatsAppController::class, 'webhook']);



// Redirigir el dashboard legacy al panel de FilamentPHP
Route::get('/dashboard', function () {
    return redirect('/admin');
})->name('dashboard');
