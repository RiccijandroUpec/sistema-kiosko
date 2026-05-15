<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar - RickTech</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow: hidden; }
        .gradient-bg { background: radial-gradient(circle at top right, #e0e7ff 0%, #f8fafc 50%); }
    </style>
</head>
<body class="gradient-bg min-h-screen flex flex-col antialiased">
    
    <nav class="max-w-6xl mx-auto px-6 py-4 w-full flex justify-between items-center">
        <a href="{{ route('kiosko.index') }}" class="flex items-center gap-2 text-slate-400 hover:text-indigo-600 font-bold text-[10px] uppercase tracking-widest transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver
        </a>
        <div class="flex items-center gap-2">
            <img src="{{ asset('images/app-icon.png') }}" alt="Logo" class="w-5 h-5 rounded-md">
            <span class="text-xs font-black text-slate-800 tracking-tight">RickTech</span>
        </div>
    </nav>

    <div class="flex-1 flex items-center justify-center p-6">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-[2.5rem] p-8 shadow-2xl shadow-indigo-100 border border-slate-50 text-center relative overflow-hidden">
                <!-- Decorative element -->
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-indigo-50 rounded-full blur-3xl opacity-60"></div>
                
                <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <h2 class="text-2xl font-black text-slate-800 mb-2 tracking-tight">Rastrear Pedido</h2>
                <p class="text-slate-400 text-sm mb-8 font-medium">Ingresa el código de tu impresión</p>

                @if ($errors->any())
                    <div class="mb-6 p-3 bg-red-50 text-red-500 rounded-xl text-[10px] font-bold uppercase tracking-widest">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('kiosko.search') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="relative">
                        <input type="text" name="job_reference" id="job_reference" required 
                            placeholder="EJ: TARE-1405"
                            class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl text-slate-900 dark:text-white font-black text-xl focus:ring-2 focus:ring-indigo-500 transition-all text-center uppercase tracking-widest placeholder:text-slate-200 placeholder:font-bold">
                    </div>

                    <button type="submit" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all active:scale-95">
                        BUSCAR TRABAJO
                    </button>
                </form>

                <div class="mt-8 pt-8 border-t border-slate-50">
                    <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">
                        ¿Olvidaste tu código?
                    </p>
                    <p class="text-[10px] text-slate-400 mt-1 font-medium italic">Pregunta al administrador por tu referencia.</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-6 text-center text-[10px] font-bold text-slate-300 uppercase tracking-widest">
        © {{ date('Y') }} RickTech Impresiones <br>
        <span class="opacity-50 mt-1 block italic text-[9px]">Desarrollado por @riccijandro</span>
    </footer>
</body>
</html>
