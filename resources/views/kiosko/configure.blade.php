<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Impresión - Kiosko</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 dark:bg-slate-900">
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

    <div class="min-h-screen flex items-start justify-center px-4 py-12">
        <div class="max-w-7xl w-full grid lg:grid-cols-12 gap-8">
            
            <!-- Columna de Vista Previa (Izquierda) -->
            <div class="lg:col-span-7 xl:col-span-8 flex flex-col h-[85vh]">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex flex-col h-full overflow-hidden">
                    <div id="preview-outer-container" class="bg-slate-100 dark:bg-slate-900 flex-1 overflow-y-auto p-8 flex flex-col items-center gap-8" style="scroll-behavior: smooth;">
                        <!-- Simulación de múltiples páginas -->
                        @for($i = 1; $i <= min($pdf->pages_count, 3); $i++)
                        <div class="preview-page-wrapper relative shadow-[0_8px_30px_rgb(0,0,0,0.12)] bg-white border border-slate-200 transition-all duration-300 overflow-hidden flex items-center justify-center" 
                             style="width: 100%; max-width: 550px; aspect-ratio: 1 / 1.414; flex-shrink: 0;">
                            <iframe 
                                src="{{ asset('storage/' . $pdf->file_path) }}#page={{ $i }}&toolbar=0&navpanes=0&scrollbar=0&view=FitH" 
                                class="pdf-page-embed pointer-events-none border-none origin-center"
                                scrolling="no"
                                style="width: 110%; height: 100%; position: absolute; left: 0; top: 0; overflow: hidden;"
                            ></iframe>
                            <div class="absolute top-4 right-4 bg-slate-800/80 text-[10px] text-white px-2 py-1 rounded backdrop-blur-md font-bold z-20">Pág. {{ $i }}</div>
                        </div>
                        @endfor
                        
                        @if($pdf->pages_count > 3)
                        <div class="py-4 text-slate-500 text-xs font-medium italic">
                            + {{ $pdf->pages_count - 3 }} páginas adicionales
                        </div>
                        @endif

                        <div class="sticky bottom-4 bg-black/70 backdrop-blur-md text-white text-[10px] px-4 py-1.5 rounded-full z-10 font-bold tracking-widest uppercase shadow-lg">
                            Vista Previa de Impresión
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna de Configuración (Derecha) -->
            <div class="lg:col-span-5 xl:col-span-4 space-y-6">
                <!-- Resumen del PDF -->
                <div class="bg-white dark:bg-slate-700 rounded-2xl shadow-md ring-1 ring-slate-200 dark:ring-slate-600 p-6">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">📊</span>
                        Resumen de Costos
                    </h3>
                    <div class="space-y-4 text-sm">
                        <div class="pb-3 border-b border-slate-200 dark:border-slate-600">
                            <p class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold tracking-wider">Archivo Seleccionado</p>
                            <p class="font-semibold text-slate-900 dark:text-white truncate">{{ $pdf->original_name }}</p>
                        </div>
                        
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 space-y-3 border border-indigo-100 dark:border-indigo-800/50">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-slate-300">Páginas × Copias:</span>
                                <span id="pagesCopies" class="font-bold text-slate-900 dark:text-white">0</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-slate-300">Costo por página:</span>
                                <span id="costPerPage" class="font-bold text-slate-900 dark:text-white">$0.00</span>
                            </div>
                            <div class="border-t border-indigo-200 dark:border-indigo-800 pt-3 mt-3 flex justify-between items-baseline">
                                <span class="text-slate-900 dark:text-white font-bold text-base">Total a pagar:</span>
                                <span id="totalCost" class="text-4xl font-black text-indigo-600 dark:text-indigo-400">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="bg-white dark:bg-slate-700 rounded-2xl shadow-xl p-6 border border-slate-100 dark:border-slate-600">
                    <form action="{{ route('kiosko.create-job', $pdf->id) }}" method="POST" class="space-y-5">
                        @csrf

                        <!-- Páginas -->
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                                Páginas
                            </label>
                            <select id="page_selection" name="page_selection" onchange="toggleCustomPages()" class="w-full px-4 py-3 border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-900 dark:text-white font-semibold focus:ring-2 focus:ring-indigo-500 transition-all">
                                <option value="all">Todas</option>
                                <option value="custom">Personalizado</option>
                            </select>
                            
                            <div id="custom_pages_container" class="hidden animate-in fade-in slide-in-from-top-2 duration-300">
                                <input type="text" name="custom_pages" id="custom_pages" placeholder="ej: 1-5, 8, 11-13" 
                                    class="w-full px-4 py-2 border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                                    oninput="updateCost()">
                                <p class="text-[10px] text-slate-400 mt-1 ml-1 italic">Total de páginas: {{ $pdf->pages_count }}</p>
                            </div>
                        </div>

                        <!-- Copias -->
                        <div>
                            <label for="copies" class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">
                                Copias
                            </label>
                            <div class="relative">
                                <input type="number" name="copies" id="copies" value="1" min="1" 
                                    max="{{ config('printing.max_copies', 100) }}" required
                                    class="w-full pl-4 pr-12 py-3 border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-900 dark:text-white font-bold text-lg focus:ring-2 focus:ring-indigo-500 transition-all"
                                    oninput="updateCost()">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-medium">unids</div>
                            </div>
                        </div>

                        <!-- Tipo de color -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-3">
                                Modo de Color
                            </label>
                            <div class="grid grid-cols-1 gap-3">
                                <label class="relative flex items-center p-4 border-2 border-slate-100 dark:border-slate-600 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-600/50 transition-all has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/30 dark:has-[:checked]:bg-indigo-900/20">
                                    <input type="radio" name="color_type" value="bw" checked class="hidden peer" onchange="updateCost()">
                                    <div class="flex flex-1 justify-between items-center">
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white">Blanco y Negro</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">${{ number_format($costBW, 2) }} / pág</p>
                                        </div>
                                        <div class="w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-500 peer-checked:border-indigo-500 peer-checked:bg-indigo-500 flex items-center justify-center">
                                            <div class="w-2 h-2 bg-white rounded-full"></div>
                                        </div>
                                    </div>
                                </label>
                                <label class="relative flex items-center p-4 border-2 border-slate-100 dark:border-slate-600 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-600/50 transition-all has-[:checked]:border-purple-500 has-[:checked]:bg-purple-50/30 dark:has-[:checked]:bg-purple-900/20">
                                    <input type="radio" name="color_type" value="color" class="hidden peer" onchange="updateCost()">
                                    <div class="flex flex-1 justify-between items-center">
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white">Color</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">${{ number_format($costColor, 2) }} / pág</p>
                                        </div>
                                        <div class="w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-500 peer-checked:border-purple-500 peer-checked:bg-purple-500 flex items-center justify-center">
                                            <div class="w-2 h-2 bg-white rounded-full"></div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Más opciones colapsables -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="paper_size" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Papel</label>
                                <select name="paper_size" id="paper_size" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                                    <option value="a4">A4</option>
                                    <option value="letter">Carta</option>
                                    <option value="legal">Oficio</option>
                                </select>
                            </div>
                            <div>
                                <label for="orientation" class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Diseño</label>
                                <select name="orientation" id="orientation" onchange="updateCost()" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 rounded-lg text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                                    <option value="portrait">Vertical</option>
                                    <option value="landscape">Horizontal</option>
                                </select>
                            </div>
                        </div>

                        <!-- Botón -->
                        <button type="submit" class="w-full group relative px-6 py-4 text-lg font-black text-white bg-indigo-600 dark:bg-indigo-500 rounded-2xl hover:bg-indigo-700 dark:hover:bg-indigo-400 shadow-lg shadow-indigo-200 dark:shadow-none transition-all overflow-hidden">
                            <span class="relative z-10 flex items-center justify-center gap-2">
                                CONTINUAR AL PAGO
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                            </span>
                            <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const totalPdfPages = {{ $pdf->pages_count }};
        const costBW = {{ $costBW }};
        const costColor = {{ $costColor }};

        function toggleCustomPages() {
            const selection = document.getElementById('page_selection').value;
            const container = document.getElementById('custom_pages_container');
            if (selection === 'custom') {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
                document.getElementById('custom_pages').value = '';
            }
            updateCost();
        }

        function parsePageRange(rangeStr) {
            if (!rangeStr || rangeStr.trim() === '') return totalPdfPages;
            
            const ranges = rangeStr.split(',');
            const pages = new Set();
            
            ranges.forEach(range => {
                const parts = range.trim().split('-');
                if (parts.length === 1) {
                    const page = parseInt(parts[0]);
                    if (!isNaN(page) && page >= 1 && page <= totalPdfPages) pages.add(page);
                } else if (parts.length === 2) {
                    const start = parseInt(parts[0]);
                    const end = parseInt(parts[1]);
                    if (!isNaN(start) && !isNaN(end)) {
                        for (let i = Math.min(start, end); i <= Math.max(start, end); i++) {
                            if (i >= 1 && i <= totalPdfPages) pages.add(i);
                        }
                    }
                }
            });
            
            return pages.size > 0 ? pages.size : 0;
        }

        function updateCost() {
            const copies = parseInt(document.getElementById('copies').value) || 1;
            const colorType = document.querySelector('input[name="color_type"]:checked').value;
            const orientation = document.getElementById('orientation').value;
            const pageSelection = document.getElementById('page_selection').value;
            const customPagesVal = document.getElementById('custom_pages').value;

            // 1. Calcular cuántas páginas se imprimirán
            let pagesToPrint = totalPdfPages;
            if (pageSelection === 'custom') {
                pagesToPrint = parsePageRange(customPagesVal);
            }
            
            // 2. Actualizar Costos
            const costPerPage = colorType === 'color' ? costColor : costBW;
            const totalItems = pagesToPrint * copies;
            const total = totalItems * costPerPage;

            document.getElementById('pagesCopies').textContent = totalItems + ' impresiones';
            document.getElementById('costPerPage').textContent = '$' + costPerPage.toFixed(2);
            document.getElementById('totalCost').textContent = '$' + total.toFixed(2);

            // 3. Actualizar Previsualización Real (Corregido sin rotar el scroll)
            const embeds = document.querySelectorAll('.pdf-page-embed');
            const wrappers = document.querySelectorAll('.preview-page-wrapper');
            
            wrappers.forEach((wrapper, index) => {
                const embed = embeds[index];
                
                // Filtro de Color
                if (colorType === 'bw') {
                    embed.style.filter = 'grayscale(100%) contrast(1.2)';
                } else {
                    embed.style.filter = 'none';
                }

                // Cambio de Orientación con Rotación Limpia
                if (orientation === 'landscape') {
                    wrapper.style.aspectRatio = '1.414 / 1';
                    wrapper.style.maxWidth = '800px';
                    
                    iframe.style.transform = 'rotate(-90deg) scale(1.4)';
                    iframe.style.width = '120%'; // Expandido para ocultar scroll al rotar
                    iframe.style.height = '120%';
                } else {
                    wrapper.style.aspectRatio = '1 / 1.414';
                    wrapper.style.maxWidth = '550px';
                    
                    iframe.style.transform = 'none';
                    iframe.style.width = '110%'; // Oculta scroll en vertical
                    iframe.style.height = '100%';
                }
                
                iframe.style.filter = colorType === 'bw' ? 'grayscale(100%)' : 'none';
            });
        }

        updateCost();
    </script>
</body>
</html>
