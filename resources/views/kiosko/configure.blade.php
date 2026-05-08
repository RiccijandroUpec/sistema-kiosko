<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Impresión - Kiosko</title>
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
        <div class="max-w-2xl w-full grid md:grid-cols-3 gap-6">
            <!-- Resumen del PDF -->
            <div class="md:col-span-1">
                <div class="bg-white dark:bg-slate-700 rounded-lg shadow-md ring-1 ring-slate-200 dark:ring-slate-600 p-6 sticky top-6">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">📊</span>
                        Resumen
                    </h3>
                    <div class="space-y-4 text-sm">
                        <div class="pb-4 border-b border-slate-200 dark:border-slate-600">
                            <p class="text-slate-700 dark:text-slate-300 text-xs uppercase font-semibold">Archivo:</p>
                            <p class="font-semibold text-slate-950 dark:text-white truncate">{{ $pdf->original_name }}</p>
                        </div>
                        <div class="pb-4 border-b border-slate-200 dark:border-slate-600">
                            <p class="text-slate-700 dark:text-slate-300 text-xs uppercase font-semibold">Páginas:</p>
                            <p class="font-semibold text-lg text-indigo-700 dark:text-indigo-300">{{ $pdf->pages_count }} págs</p>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-600/70 rounded-lg p-4 space-y-3 border border-slate-200 dark:border-slate-500">
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-700 dark:text-slate-200">Páginas × Copias:</span>
                                <span id="pagesCopies" class="font-semibold text-slate-950 dark:text-white">0</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-700 dark:text-slate-200">Costo unitario:</span>
                                <span id="costPerPage" class="font-semibold text-slate-950 dark:text-white">$0.00</span>
                            </div>
                            <div class="border-t border-slate-200 dark:border-slate-500 pt-3 mt-3 flex justify-between text-lg">
                                <span class="text-slate-900 dark:text-white font-semibold">Total:</span>
                                <span id="totalCost" class="text-3xl font-semibold text-indigo-700 dark:text-indigo-200">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de configuración -->
            <div class="md:col-span-2">
                <div class="bg-white dark:bg-slate-700 rounded-2xl shadow-xl p-8">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Configurar Impresión</h2>

                    <form action="{{ route('kiosko.create-job', $pdf->id) }}" method="POST" class="space-y-6">
                        @csrf

                        <!-- Copias -->
                        <div>
                            <label for="copies" class="block text-sm font-semibold text-slate-950 dark:text-gray-100 mb-2">
                                Número de Copias
                            </label>
                            <input type="number" name="copies" id="copies" value="1" min="1" 
                                max="{{ config('printing.max_copies') }}" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-600 rounded-lg text-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                oninput="updateCost()">
                        </div>

                        <!-- Tipo de color -->
                        <div>
                            <label class="block text-sm font-bold text-slate-950 dark:text-gray-100 mb-3 uppercase">
                                Tipo de Impresión
                            </label>
                            <div class="space-y-3">
                                <label class="flex items-center p-4 border border-gray-300 dark:border-slate-500 rounded-lg cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/50 dark:hover:bg-slate-600 transition-colors bg-white dark:bg-slate-600">
                                    <input type="radio" name="color_type" value="bw" checked class="mr-3 w-4 h-4" onchange="updateCost()">
                                    <div>
                                        <p class="font-semibold text-black dark:text-white">Blanco y Negro</p>
                                        <p class="text-sm text-black dark:text-gray-300">${{ number_format($costBW, 2) }} por página</p>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 border border-gray-300 dark:border-slate-500 rounded-lg cursor-pointer hover:border-purple-400 hover:bg-purple-50/50 dark:hover:bg-slate-600 transition-colors bg-white dark:bg-slate-600">
                                    <input type="radio" name="color_type" value="color" class="mr-3 w-4 h-4" onchange="updateCost()">
                                    <div>
                                        <p class="font-semibold text-black dark:text-white">Color</p>
                                        <p class="text-sm text-black dark:text-gray-300">${{ number_format($costColor, 2) }} por página</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Tamaño de papel -->
                        <div>
                            <label for="paper_size" class="block text-sm font-semibold text-slate-950 dark:text-gray-100 mb-2">
                                Tamaño de Papel
                            </label>
                            <select name="paper_size" id="paper_size" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-600 rounded-lg text-slate-950 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="a4">A4 (21 × 29.7 cm)</option>
                                <option value="letter">Letter (8.5 × 11 pulgadas)</option>
                                <option value="legal">Legal (8.5 × 14 pulgadas)</option>
                            </select>
                        </div>

                        <!-- Orientación -->
                        <div>
                            <label class="block text-sm font-bold text-slate-950 dark:text-gray-100 mb-3 uppercase">
                                Orientación
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center p-4 border border-gray-300 dark:border-slate-500 rounded-lg cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/50 dark:hover:bg-slate-600 transition-colors bg-white dark:bg-slate-600">
                                    <input type="radio" name="orientation" value="portrait" checked class="mr-3 w-4 h-4">
                                    <span class="font-semibold text-slate-950 dark:text-white">Vertical</span>
                                </label>
                                <label class="flex items-center p-4 border border-gray-300 dark:border-slate-500 rounded-lg cursor-pointer hover:border-purple-400 hover:bg-purple-50/50 dark:hover:bg-slate-600 transition-colors bg-white dark:bg-slate-600">
                                    <input type="radio" name="orientation" value="landscape" class="mr-3 w-4 h-4">
                                    <span class="font-semibold text-slate-950 dark:text-white">Horizontal</span>
                                </label>
                            </div>
                        </div>

                        <!-- Botón -->
                        <button type="submit" class="w-full px-6 py-3 text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg hover:shadow-lg transition-all mt-8">
                            Ir a Pagar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const pagesCount = {{ $pdf->pages_count }};
        const costBW = {{ $costBW }};
        const costColor = {{ $costColor }};

        function updateCost() {
            const copies = parseInt(document.getElementById('copies').value) || 1;
            const colorType = document.querySelector('input[name="color_type"]:checked').value;
            const costPerPage = colorType === 'color' ? costColor : costBW;
            const totalPages = pagesCount * copies;
            const total = totalPages * costPerPage;

            document.getElementById('pagesCopies').textContent = totalPages + ' páginas';
            document.getElementById('costPerPage').textContent = '$' + costPerPage.toFixed(2);
            document.getElementById('totalCost').textContent = '$' + total.toFixed(2);
        }

        updateCost();
    </script>
</body>
</html>
