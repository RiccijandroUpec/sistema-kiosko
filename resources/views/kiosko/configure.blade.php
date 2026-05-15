<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar - RickTech</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow-x: hidden; }
        .glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); }
        [x-cloak] { display: none !important; }
        @media (min-width: 1024px) {
            .no-scroll-pc { height: calc(100vh - 65px); overflow: hidden; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased" x-data="configurator()">

    <nav class="bg-white border-b border-slate-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-2 flex justify-between items-center">
            <a href="{{ route('kiosko.index') }}" class="flex items-center gap-2 text-slate-400 hover:text-indigo-600 font-bold text-[10px] uppercase tracking-widest transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver
            </a>
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/app-icon.png') }}" alt="Logo" class="w-5 h-5 rounded-md">
                <span class="text-xs font-black text-slate-800 tracking-tight">RickTech</span>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-4 no-scroll-pc flex items-center">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 w-full items-center">
            
            <!-- VISTA PREVIA (PC) -->
            <div class="hidden lg:block lg:col-span-7 xl:col-span-8">
                <div class="bg-slate-200/40 rounded-[2rem] p-6 h-[75vh] overflow-y-auto space-y-6 scrollbar-hide flex flex-col items-center">
                    @for($i = 1; $i <= min($pdf->pages_count, 3); $i++)
                    <div class="bg-white shadow-xl mx-auto transition-all duration-500 overflow-hidden relative flex items-center justify-center shrink-0" 
                         :class="orientation === 'landscape' ? 'aspect-[1.414/1] w-full max-w-[650px]' : 'aspect-[1/1.414] h-[65vh] w-auto'"
                         :style="colorType === 'bw' ? 'filter: grayscale(1) contrast(1.1)' : ''">
                        <iframe src="{{ asset('storage/' . $pdf->file_path) }}#page={{ $i }}&toolbar=0&navpanes=0&scrollbar=0" 
                                class="w-[115%] h-full border-none pointer-events-none transition-transform duration-500"
                                :style="orientation === 'landscape' ? 'transform: rotate(-90deg) scale(1.4); width: 140%; height: 140%;' : 'transform: none;'"></iframe>
                        <div class="absolute top-3 right-3 bg-black/40 backdrop-blur-md text-white text-[9px] font-black px-2 py-0.5 rounded-full z-20">PÁG {{ $i }}</div>
                    </div>
                    @endfor
                </div>
            </div>

            <!-- CONFIGURACIÓN -->
            <div class="lg:col-span-5 xl:col-span-4 space-y-4">
                
                <div class="bg-indigo-600 rounded-[2rem] p-5 text-white shadow-xl relative overflow-hidden">
                    <p class="text-indigo-100 text-[9px] font-bold uppercase tracking-widest mb-1 opacity-80">Total Estimado</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-black tracking-tight" x-text="'$' + total.toFixed(2)">$0.00</span>
                        <span class="text-indigo-200 font-bold uppercase text-[9px] tracking-widest">USD</span>
                    </div>
                </div>

                <form action="{{ route('kiosko.create-job', $pdf->id) }}" method="POST" class="space-y-4 bg-white rounded-[2rem] p-5 border border-slate-100 shadow-sm">
                    @csrf
                    
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer group">
                            <input type="radio" name="color_type" value="bw" class="hidden" x-model="colorType">
                            <div class="border-2 rounded-2xl p-3 text-center transition-all"
                                 :class="colorType === 'bw' ? 'border-slate-800 bg-slate-50' : 'border-transparent bg-slate-50/50'">
                                <p class="font-black text-[10px] text-slate-800 uppercase">Blanco y Negro</p>
                                <p class="text-[8px] text-slate-400 font-bold">${{ number_format($costBW, 2) }}</p>
                            </div>
                        </label>
                        <label class="cursor-pointer group">
                            <input type="radio" name="color_type" value="color" class="hidden" x-model="colorType">
                            <div class="border-2 rounded-2xl p-3 text-center transition-all"
                                 :class="colorType === 'color' ? 'border-indigo-600 bg-indigo-50/30' : 'border-transparent bg-slate-50/50'">
                                <p class="font-black text-[10px] text-slate-800 uppercase">Color</p>
                                <p class="text-[8px] text-slate-400 font-bold">${{ number_format($costColor, 2) }}</p>
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-slate-400 uppercase tracking-widest ml-1">Copias</label>
                            <div class="bg-slate-50 rounded-xl p-1.5 flex items-center justify-between border border-slate-100">
                                <button type="button" @click="if(copies > 1) copies--" class="w-7 h-7 bg-white text-slate-400 rounded-lg font-black">-</button>
                                <input type="number" name="copies" x-model="copies" class="w-8 text-center font-black text-slate-800 border-none bg-transparent p-0 text-sm">
                                <button type="button" @click="copies++" class="w-7 h-7 bg-indigo-50 text-indigo-600 rounded-lg font-black">+</button>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[8px] font-black text-slate-400 uppercase tracking-widest ml-1">Papel</label>
                            <div class="bg-slate-50 border border-slate-100 rounded-xl py-2.5 px-3 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-800">A4</span>
                                <input type="hidden" name="paper_size" value="a4">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-[10px] font-black text-slate-800">Rango de Páginas</label>
                            <select x-model="pageSelection" class="text-[9px] font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full outline-none border-none">
                                <option value="all">Todas ({{ $pdf->pages_count }})</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>
                        <div x-show="pageSelection === 'custom'" x-transition>
                            <input type="text" name="custom_pages" x-model="customPages" placeholder="ej: 1-5, 8" 
                                   class="w-full bg-slate-50 border-none rounded-xl py-2 px-3 text-xs font-bold text-slate-800 focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="orientation = 'portrait'" 
                                class="py-2.5 rounded-xl text-[9px] font-black uppercase transition-all"
                                :class="orientation === 'portrait' ? 'bg-slate-800 text-white shadow-md' : 'bg-slate-50 text-slate-400'">Vertical</button>
                        <button type="button" @click="orientation = 'landscape'" 
                                class="py-2.5 rounded-xl text-[9px] font-black uppercase transition-all"
                                :class="orientation === 'landscape' ? 'bg-slate-800 text-white shadow-md' : 'bg-slate-50 text-slate-400'">Horizontal</button>
                        <input type="hidden" name="orientation" :value="orientation">
                    </div>

                    <button type="submit" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black text-center shadow-lg hover:bg-indigo-700 transition-all active:scale-95 flex items-center justify-center gap-2 text-sm tracking-tight">
                        CONTINUAR AL PAGO
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function configurator() {
            return {
                totalPdfPages: {{ $pdf->pages_count }},
                costBW: {{ $costBW }},
                costColor: {{ $costColor }},
                colorType: 'bw', copies: 1, pageSelection: 'all', customPages: '', orientation: 'portrait',
                get pagesToPrint() { return this.pageSelection === 'all' ? this.totalPdfPages : this.parsePageRange(this.customPages); },
                get costPerPage() { return this.colorType === 'color' ? this.costColor : this.costBW; },
                get total() { return (this.pagesToPrint * this.copies) * this.costPerPage; },
                parsePageRange(str) {
                    if (!str || str.trim() === '') return 0;
                    const ranges = str.split(','), pages = new Set();
                    ranges.forEach(r => {
                        const p = r.trim().split('-');
                        if (p.length === 1) { const n = parseInt(p[0]); if (n >= 1 && n <= this.totalPdfPages) pages.add(n); }
                        else if (p.length === 2) {
                            const s = parseInt(p[0]), e = parseInt(p[1]);
                            for (let i = Math.min(s, e); i <= Math.max(s, e); i++) { if (i >= 1 && i <= this.totalPdfPages) pages.add(i); }
                        }
                    });
                    return pages.size;
                }
            }
        }
    </script>
</body>
</html>
