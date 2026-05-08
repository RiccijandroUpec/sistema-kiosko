<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Pago - Kiosko</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-50 to-indigo-50 dark:from-slate-800 dark:to-slate-800">
    <nav class="bg-white dark:bg-slate-800 shadow-md p-4">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="{{ route('kiosko.index') }}" class="flex items-center gap-2 text-gray-900 dark:text-white hover:text-indigo-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver
            </a>
        </div>
    </nav>

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-2xl w-full">
            <div class="bg-white/95 dark:bg-slate-700 rounded-2xl shadow-xl ring-1 ring-slate-200 dark:ring-slate-600 p-8">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Realizar Pago</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Tu referencia de trabajo: <span class="font-mono font-bold text-lg text-indigo-600">{{ $printJob->job_reference }}</span></p>
                </div>

                <!-- Detalles del trabajo -->
                <div class="bg-slate-50 dark:bg-slate-600 rounded-lg p-6 mb-8 border border-slate-200 dark:border-slate-500">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Detalles de tu trabajo</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-slate-700 dark:text-slate-200">Páginas:</p>
                            <p class="font-semibold text-slate-950 dark:text-white">{{ $printJob->pdfFile->pages_count }}</p>
                        </div>
                        <div>
                            <p class="text-slate-700 dark:text-slate-200">Copias:</p>
                            <p class="font-semibold text-slate-950 dark:text-white">{{ $printJob->copies }}</p>
                        </div>
                        <div>
                            <p class="text-slate-700 dark:text-slate-200">Impresión:</p>
                            <p class="font-semibold text-slate-950 dark:text-white">
                                @if ($printJob->color_type === 'color')
                                    Color
                                @else
                                    B&N
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-slate-700 dark:text-slate-200">Tamaño:</p>
                            <p class="font-semibold text-slate-950 dark:text-white">{{ strtoupper($printJob->paper_size) }}</p>
                        </div>
                        <div>
                            <p class="text-slate-700 dark:text-slate-200">Orientación:</p>
                            <p class="font-semibold text-slate-950 dark:text-white">
                                @if ($printJob->orientation === 'portrait')
                                    Vertical
                                @else
                                    Horizontal
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Monto a pagar -->
                <div class="rounded-lg p-8 mb-8 bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/30 dark:to-indigo-800/30 border border-indigo-200 dark:border-indigo-700">
                    <p class="text-indigo-700 dark:text-indigo-300 text-xs mb-2 font-semibold uppercase tracking-wide">Monto a pagar</p>
                    <p class="text-5xl font-semibold text-indigo-950 dark:text-indigo-100">${{ number_format($payment->amount, 2) }}</p>
                    <p class="text-sm text-indigo-800 dark:text-indigo-300 mt-3">USD</p>
                </div>

                <!-- Instrucciones de pago -->
                <div class="space-y-6 mb-8">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4 text-lg">Instrucciones de Pago</h3>
                        <p class="text-gray-700 dark:text-gray-200 mb-4 font-semibold">
                            Realiza una transferencia bancaria con los siguientes datos:
                        </p>
                        <div class="bg-blue-50 dark:bg-slate-600 rounded-lg p-4 space-y-3 text-sm border border-blue-200 dark:border-slate-500 shadow-sm">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-blue-700 dark:text-blue-300 font-semibold text-xs uppercase">Banco</p>
                                    <p class="font-semibold text-blue-950 dark:text-white text-lg mt-1">{{ config('printing.bank.name') }}</p>
                                </div>
                                <div>
                                    <p class="text-blue-700 dark:text-blue-300 font-semibold text-xs uppercase">Tipo de Cuenta</p>
                                    <p class="font-semibold text-blue-950 dark:text-white text-lg mt-1">{{ config('printing.bank.account_type') }}</p>
                                </div>
                                <div>
                                    <p class="text-blue-700 dark:text-blue-300 font-semibold text-xs uppercase">Número de Cuenta</p>
                                    <p class="font-mono font-semibold text-blue-950 dark:text-white text-lg mt-1">{{ config('printing.bank.account_number') }}</p>
                                </div>
                                <div>
                                    <p class="text-blue-700 dark:text-blue-300 font-semibold text-xs uppercase">RUC</p>
                                    <p class="font-mono font-semibold text-blue-950 dark:text-white text-lg mt-1">{{ config('printing.bank.ruc') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Referencia de pago -->
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Referencia de Pago</h4>
                        <div class="bg-amber-50 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-600 rounded-lg p-4 shadow-sm">
                            <p class="text-amber-900 dark:text-amber-100 text-sm mb-3 font-bold">⚠️ IMPORTANTE: Copia esta referencia en el concepto</p>
                            <div class="flex items-center justify-between bg-white dark:bg-slate-700 p-4 rounded border border-amber-300 dark:border-amber-600">
                                <code class="font-mono font-bold text-xl text-amber-800 dark:text-amber-300">{{ $payment->reference_code }}</code>
                                <button type="button" onclick="copyToClipboard('{{ $payment->reference_code }}')" class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-2 rounded font-semibold transition-colors shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Próximos pasos -->
                <div class="bg-green-50 dark:bg-green-900/30 border border-green-300 dark:border-green-600 rounded-lg p-4 mb-8 shadow-sm">
                    <h4 class="font-bold text-green-900 dark:text-green-100 mb-3">✓ Después de pagar:</h4>
                    <ol class="text-sm text-green-900 dark:text-green-100 space-y-2 list-decimal list-inside font-semibold">
                        <li>El administrador confirmará tu pago</li>
                        <li>Tu trabajo pasará a la cola de impresión</li>
                        <li>Podrás ver el estado en cualquier momento</li>
                        <li>¡Retira tu trabajo cuando esté listo!</li>
                    </ol>
                </div>

                <!-- Botones de acción -->
                <div class="flex flex-col gap-4">
                    <a href="{{ route('kiosko.status', $printJob->job_reference) }}" class="block px-6 py-3 text-center text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg hover:shadow-lg transition-all shadow-sm">
                        Ver Estado del Trabajo
                    </a>
                    <a href="{{ route('kiosko.index') }}" class="block px-6 py-3 text-center text-lg font-semibold text-indigo-600 border-2 border-indigo-600 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all">
                        Ir a la Página Principal
                    </a>
                </div>
            </div>

            <!-- Ayuda -->
            <div class="text-center mt-6 text-gray-600 dark:text-gray-400 text-sm">
                <p>¿Necesitas ayuda? Contacta al administrador del kiosko.</p>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Referencia copiada al portapapeles');
            });
        }
    </script>
</body>
</html>
