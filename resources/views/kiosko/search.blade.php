<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Trabajo - Kiosko</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-50 to-indigo-50 dark:from-slate-900 dark:to-indigo-950">
    <nav class="bg-white dark:bg-slate-800 shadow-md p-4">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="{{ route('kiosko.index') }}" class="flex items-center gap-2 text-gray-900 dark:text-white hover:text-indigo-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver al Inicio
            </a>
        </div>
    </nav>

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-8 border border-slate-200 dark:border-slate-700">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Rastrear Impresión</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-2 text-sm">Ingresa el código de referencia de tu trabajo</p>
                </div>

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('kiosko.search') }}" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <label for="job_reference" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">
                            Código de Referencia
                        </label>
                        <input type="text" name="job_reference" id="job_reference" required 
                            placeholder="Ej: PRT-ABC123"
                            class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 rounded-xl text-slate-900 dark:text-white font-mono text-lg focus:ring-2 focus:ring-indigo-500 transition-all text-center uppercase">
                    </div>

                    <button type="submit" class="w-full py-4 text-lg font-bold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl hover:shadow-lg transition-all shadow-md">
                        Buscar mi Trabajo
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-gray-500 dark:text-gray-400">
                    El código de referencia se encuentra en el comprobante generado al crear el trabajo.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
