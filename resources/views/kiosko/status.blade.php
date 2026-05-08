<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Trabajo - Kiosko</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-50 to-indigo-50 dark:from-slate-800 dark:to-slate-800">
    <nav class="bg-white dark:bg-slate-800 shadow-md p-4">
        <div class="max-w-6xl mx-auto">
            <a href="{{ route('kiosko.index') }}" class="flex items-center gap-2 text-gray-900 dark:text-white hover:text-indigo-600 w-fit">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver
            </a>
        </div>
    </nav>

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-2xl w-full">
            <div class="bg-white dark:bg-slate-700 rounded-2xl shadow-xl p-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Estado del Trabajo</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Referencia: <span class="font-mono font-bold text-indigo-600">{{ $printJob->job_reference }}</span></p>
                </div>

                <!-- Estado del pago -->
                <div class="mb-8">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Estado del Pago</h3>
                    @php
                        $paymentStatus = $printJob->payment->status;
                    @endphp
                    @if ($paymentStatus === 'confirmed')
                        <div class="p-4 rounded-lg bg-green-50 dark:bg-slate-600 border border-green-200 dark:border-slate-500">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-green-700 dark:text-white">✓ Confirmado</span>
                                <span class="text-lg font-semibold text-green-600 dark:text-green-300">${{ number_format($printJob->payment->amount, 2) }}</span>
                            </div>
                        </div>
                    @elseif ($paymentStatus === 'pending')
                        <div class="p-4 rounded-lg bg-amber-50 dark:bg-slate-600 border border-amber-200 dark:border-slate-500">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-amber-700 dark:text-white">⏳ Pendiente</span>
                                <span class="text-lg font-semibold text-amber-600 dark:text-amber-300">${{ number_format($printJob->payment->amount, 2) }}</span>
                            </div>
                        </div>
                        <p class="text-sm text-amber-700 dark:text-amber-300 mt-2">Aguardando confirmación del administrador...</p>
                    @else
                        <div class="p-4 rounded-lg bg-red-50 dark:bg-slate-600 border border-red-200 dark:border-slate-500">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-red-700 dark:text-white">✗ Cancelado</span>
                                <span class="text-lg font-semibold text-red-600 dark:text-red-300">${{ number_format($printJob->payment->amount, 2) }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Estado de impresión -->
                <div class="mb-8">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Estado de Impresión</h3>
                    @php
                        $status = $printJob->status;
                        $progress = [
                            'pending' => 25,
                            'printing' => 75,
                            'completed' => 100,
                            'cancelled' => 0,
                        ];
                        $currentProgress = $progress[$status] ?? 0;
                    @endphp

                    <!-- Barra de progreso -->
                    <div class="space-y-2 mb-6">
                        <div class="w-full bg-gray-300 dark:bg-slate-600 rounded-full h-3 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-600 to-purple-600 transition-all" style="width: {{ $currentProgress }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs font-semibold text-gray-700 dark:text-gray-300">
                            <span>Creado</span>
                            <span>En Cola</span>
                            <span>Completado</span>
                        </div>
                    </div>

                    <!-- Estado actual -->
                    <div class="p-4 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/30">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Estado actual:</p>
                        <div class="flex items-center gap-3">
                            @if ($status === 'completed')
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-semibold text-green-700 dark:text-green-300">Completado</span>
                            @elseif ($status === 'cancelled')
                                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-semibold text-red-700 dark:text-red-300">Cancelado</span>
                            @elseif ($status === 'printing')
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span class="font-semibold text-blue-700 dark:text-blue-300">Imprimiendo...</span>
                            @else
                                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-semibold text-gray-700 dark:text-gray-300">Esperando confirmación...</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Detalles -->
                <div class="bg-gray-50 dark:bg-slate-600 rounded-lg p-6 mb-8">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Detalles del Trabajo</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600 dark:text-gray-200">Archivo:</p>
                            <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $printJob->pdfFile->original_name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-200">Páginas:</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $printJob->pdfFile->pages_count }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-200">Copias:</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $printJob->copies }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Impresión:</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                @if ($printJob->color_type === 'color') Color @else Blanco y Negro @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Creado:</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $printJob->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Costo Total:</p>
                            <p class="font-semibold text-indigo-600">${{ number_format($printJob->cost, 2) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Instrucciones -->
                @if ($status === 'completed')
                    <div class="bg-green-100 dark:bg-green-900 border-2 border-green-600 dark:border-green-500 rounded-lg p-6 mb-8">
                        <h4 class="font-bold text-green-900 dark:text-green-100 mb-2 text-lg">✓ ¡Tu trabajo está listo!</h4>
                        <p class="text-sm text-green-900 dark:text-green-100 font-semibold">
                            Ve a la ventanilla para recoger tu trabajo. Menciona tu código de referencia: <span class="font-mono text-lg">{{ $printJob->job_reference }}</span>
                        </p>
                    </div>
                @elseif ($status === 'printing' && $printJob->payment->status === 'confirmed')
                    <div class="bg-blue-100 dark:bg-blue-900 border-2 border-blue-600 dark:border-blue-500 rounded-lg p-6 mb-8">
                        <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-2 text-lg">⏳ Tu trabajo está en proceso</h4>
                        <p class="text-sm text-blue-900 dark:text-blue-100 font-semibold">
                            Tu pago ha sido confirmado. Tu trabajo será impreso en breve. Vuelve a esta página para ver cuándo está listo.
                        </p>
                    </div>
                @elseif ($printJob->payment->status === 'pending')
                    <div class="bg-amber-100 dark:bg-amber-900 border-2 border-amber-600 dark:border-amber-500 rounded-lg p-6 mb-8">
                        <h4 class="font-bold text-amber-900 dark:text-amber-100 mb-2 text-lg">⏳ Esperando confirmación de pago</h4>
                        <p class="text-sm text-amber-900 dark:text-amber-100 font-semibold">
                            Tu pago aún no ha sido confirmado. El administrador lo confirmará en breve. Vuelve a actualizar esta página.
                        </p>
                    </div>
                @elseif ($status === 'cancelled')
                    <div class="bg-red-100 dark:bg-red-900 border-2 border-red-600 dark:border-red-500 rounded-lg p-6 mb-8">
                        <h4 class="font-bold text-red-900 dark:text-red-100 mb-2 text-lg">✗ Trabajo cancelado</h4>
                        <p class="text-sm text-red-900 dark:text-red-100 font-semibold">
                            Tu trabajo ha sido cancelado. Contacta al administrador para más información.
                        </p>
                    </div>
                @endif

                <!-- Botones de acción -->
                <div class="flex flex-col gap-3">
                    <button onclick="location.reload()" class="px-6 py-3 text-center text-lg font-semibold text-indigo-600 border-2 border-indigo-600 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all">
                        Actualizar Estado
                    </button>
                    <a href="{{ route('kiosko.index') }}" class="px-6 py-3 text-center text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg hover:shadow-lg transition-all">
                        Crear Otro Trabajo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Auto-refresh cada 30 segundos si está en proceso -->
    @if ($status === 'printing' && $printJob->payment->status === 'pending')
        <script>
            setTimeout(() => location.reload(), 30000);
        </script>
    @endif
</body>
</html>
