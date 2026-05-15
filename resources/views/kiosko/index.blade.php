<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RickTech - Kiosko</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow: hidden; }
        .hero-title { background: linear-gradient(135deg, #1e1b4b 0%, #4f46e5 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        @media (min-width: 1024px) {
            .no-scroll-pc { height: calc(100vh - 60px); overflow: hidden; }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-900 antialiased flex flex-col">

    <nav class="max-w-6xl mx-auto px-6 py-3 w-full flex justify-between items-center">
        <div class="flex items-center gap-2">
            <img src="{{ asset('images/app-icon.png') }}" alt="RickTech" class="w-6 h-6 rounded-lg">
            <span class="text-sm font-black tracking-tight text-slate-800">RickTech</span>
        </div>
        <a href="{{ route('login') }}" class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Admin</a>
    </nav>

    <main class="max-w-6xl mx-auto px-6 flex-1 flex flex-col justify-center no-scroll-pc">
        <!-- Hero Section Compacta -->
        <div class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-black hero-title mb-3 tracking-tight">Impresiones al instante.</h1>
            <p class="text-slate-400 text-sm md:text-base font-medium max-w-xl mx-auto">Sube tu PDF, paga con Deuna! y retira en segundos.</p>
        </div>

        <!-- Acciones Principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto w-full">
            <div class="bg-white rounded-[2rem] p-6 shadow-xl border border-slate-50 flex flex-col text-center">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                </div>
                <h2 class="text-xl font-black text-slate-800 mb-2">Nueva Impresión</h2>
                <p class="text-slate-400 text-[10px] mb-6 font-medium">Selecciona tu PDF para empezar.</p>
                
                <form action="{{ route('kiosko.upload-pdf') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <input type="file" name="pdf" id="pdfInput" class="hidden" accept=".pdf" onchange="document.getElementById('uploadForm').submit()">
                    <button type="button" onclick="document.getElementById('pdfInput').click()" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black text-xs shadow-lg hover:bg-indigo-700 transition-all active:scale-95">
                        SUBIR ARCHIVO
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-[2rem] p-6 shadow-xl border border-slate-50 flex flex-col text-center">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <h2 class="text-xl font-black text-slate-800 mb-2">Mi Pedido</h2>
                <p class="text-slate-400 text-[10px] mb-6 font-medium">Ingresa tu código de referencia.</p>
                
                <form action="{{ route('kiosko.search') }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="text" name="job_reference" placeholder="EJ: TARE-1405" required class="flex-1 bg-slate-50 border-none rounded-xl px-4 py-3 text-xs font-bold text-slate-800 uppercase focus:ring-2 focus:ring-indigo-500">
                    <button type="submit" class="p-3 bg-slate-800 text-white rounded-xl hover:bg-slate-900 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Pasos Mini -->
        <div class="mt-12 flex justify-center gap-8 md:gap-16 opacity-30 grayscale hover:opacity-100 hover:grayscale-0 transition-all duration-700">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black">01</span>
                <span class="text-[9px] font-bold uppercase tracking-widest">Sube</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-black">02</span>
                <span class="text-[9px] font-bold uppercase tracking-widest">Paga</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-black">03</span>
                <span class="text-[9px] font-bold uppercase tracking-widest">Retira</span>
            </div>
        </div>
    </main>

    <footer class="py-6 text-center text-[10px] font-bold text-slate-300 uppercase tracking-widest">
        © {{ date('Y') }} RickTech Impresiones <br>
        <span class="opacity-50 mt-1 block italic text-[9px]">Desarrollado por @riccijandro</span>
    </footer>

</body>
</html>
