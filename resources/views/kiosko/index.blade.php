<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosko de Impresiones</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        .gradient-text {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-50 to-indigo-50 dark:from-slate-900 dark:to-indigo-950">
    <nav class="bg-white dark:bg-slate-800 shadow-md p-4">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kiosko de Impresiones</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('kiosko.search-form') }}" class="text-sm px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm font-medium">Buscar trabajo</a>
                <a href="{{ route('login') }}" class="text-sm px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm font-medium">Administración</a>
            </div>
        </div>
    </nav>

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-2xl w-full animate-fade-in">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-8 md:p-12">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                        <span class="gradient-text">Impresos al Instante</span>
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 text-lg">
                        Sube tu PDF, configura la impresión y realiza el pago.
                    </p>
                </div>

                <div class="space-y-6">
                    <!-- Pasos -->
                    <div class="space-y-3">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-600 text-white font-semibold text-lg shadow-md">
                                    1
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sube tu PDF</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Selecciona el archivo que deseas imprimir</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 text-white font-semibold text-lg shadow-md">
                                    2
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Configura la impresión</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Elige copias, color, tamaño y orientación</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-white font-semibold text-lg shadow-md">
                                    3
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Realiza el pago</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Transfiere el monto indicado al banco</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-12 w-12 rounded-full bg-gradient-to-br from-green-500 to-green-600 text-white font-semibold text-lg shadow-md">
                                    4
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">¡Imprime!</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Espera la confirmación del admin y ve a recoger</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t dark:border-slate-700 pt-8 mt-8">
                        <a href="{{ route('kiosko.upload') }}" class="w-full block px-6 py-4 text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg text-center hover:shadow-lg transition-all">
                            Comenzar a Imprimir
                        </a>
                    </div>

                    <!-- Información de contacto -->
                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-6 text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            ¿Necesitas ayuda? Contacta al administrador del kiosko.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-6 text-gray-600 dark:text-gray-400 text-sm">
        <p>&copy; {{ date('Y') }} Kiosko de Impresiones. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
