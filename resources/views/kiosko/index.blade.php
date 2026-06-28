<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RickTech - Kioskos de Impresión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-900 antialiased flex flex-col">

    <nav class="max-w-6xl mx-auto px-6 py-4 w-full flex justify-between items-center">
        <div class="flex items-center gap-2">
            <img src="{{ asset('images/app-icon.png') }}" alt="RickTech" class="w-6 h-6 rounded-lg">
            <span class="text-sm font-black tracking-tight text-slate-800">RickTech</span>
        </div>
        <a href="{{ route('login') }}" class="text-[9px] font-bold text-slate-400 uppercase tracking-widest hover:text-indigo-600">Admin</a>
    </nav>

    <!-- PARA DUEÑOS DE NEGOCIO -->
    <section class="flex-1 flex items-center bg-indigo-600 py-16">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest mb-2">¿Tienes un local?</p>
            <h1 class="text-3xl md:text-4xl font-black text-white mb-4">Convierte tu impresora en un kiosko RickTech</h1>
            <p class="text-indigo-100 text-sm max-w-2xl mx-auto mb-8 leading-relaxed">
                Tú pones la impresora y el espacio; nosotros nos encargamos del resto: el bot de WhatsApp que recibe los archivos,
                la verificación automática de pagos y el panel para que veas todo en tiempo real. No necesitas estar pendiente
                del local para que tu kiosko siga generando ingresos.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-2xl mx-auto mb-10 text-left">
                <div class="bg-white/10 rounded-xl p-4">
                    <p class="text-white text-xs font-bold mb-1">✓ Bot de WhatsApp incluido</p>
                    <p class="text-indigo-100 text-[10px]">Recibe pedidos y cobra por ti.</p>
                </div>
                <div class="bg-white/10 rounded-xl p-4">
                    <p class="text-white text-xs font-bold mb-1">✓ Panel de administración</p>
                    <p class="text-indigo-100 text-[10px]">Ve órdenes, pagos y estadísticas.</p>
                </div>
                <div class="bg-white/10 rounded-xl p-4">
                    <p class="text-white text-xs font-bold mb-1">✓ Tu propio enlace</p>
                    <p class="text-indigo-100 text-[10px]">Cartel con QR listo para imprimir.</p>
                </div>
            </div>

            <a href="https://wa.me/{{ ltrim(env('ADMIN_PHONE'), '+') }}?text=Hola%2C%20quiero%20informaci%C3%B3n%20para%20tener%20un%20kiosko%20RickTech"
               target="_blank"
               class="inline-block bg-white text-indigo-600 font-black text-xs uppercase tracking-widest px-8 py-4 rounded-2xl shadow-xl hover:bg-indigo-50 transition-all active:scale-95">
                Quiero mi kiosko →
            </a>
        </div>
    </section>

    <footer class="py-6 text-center text-[10px] font-bold text-slate-300 uppercase tracking-widest">
        © {{ date('Y') }} RickTech Impresiones <br>
        <span class="opacity-50 mt-1 block italic text-[9px]">Desarrollado por @riccijandro</span>
    </footer>

</body>
</html>
