<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poster Kiosko - {{ $kiosk->nombre_comercial }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: white; }
        
        /* Estilos específicos para impresión */
        @media print {
            .no-print { display: none !important; }
            .print-page {
                height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
                box-sizing: border-box;
                padding: 1cm;
                border: none !important;
                box-shadow: none !important;
            }
            .qr-image {
                width: 14cm;
                height: 14cm;
                margin: 2cm 0;
            }
            /* Ocultamos el diseño superior colorido en impresión para ahorrar tinta y quedar más limpio */
            .print-decor { display: none; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">

    <!-- Encabezado de controles (oculto en impresión) -->
    <div class="fixed top-4 right-4 no-print flex gap-2 z-50">
        <a href="/admin/kioskos" class="px-4 py-2 bg-white text-slate-700 rounded-full shadow font-semibold hover:bg-slate-50">
            Volver a kioskos
        </a>
        <button onclick="window.print()" class="px-6 py-2 bg-indigo-600 text-white rounded-full shadow-lg font-bold hover:bg-indigo-700 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Imprimir A4
        </button>
    </div>

    <div class="print-page bg-white p-12 rounded-[3rem] shadow-2xl border border-slate-100 flex flex-col items-center relative overflow-hidden max-w-4xl w-full">
        
        <!-- Diseño gráfico superior -->
        <div class="print-decor absolute top-0 left-0 w-full h-4 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

        <div class="mb-4 text-indigo-600 print-decor mt-4">
            <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        </div>
        
        <h2 class="text-6xl md:text-[5rem] leading-none font-black text-slate-900 tracking-tight uppercase mb-4 text-center">IMPRIME AQUÍ</h2>
        <p class="text-2xl text-slate-500 font-medium mb-2">SEDE: <span class="font-bold text-indigo-600">{{ $kiosk->nombre_comercial }}</span></p>

        <!-- El QR Code -->
        <div class="p-6 bg-white rounded-3xl border-[8px] border-indigo-50 shadow-2xl mt-12 mb-12">
            <img src="{{ route('kiosko.whatsapp-qr.kiosk', $kiosk->id) }}" alt="QR de {{ $kiosk->nombre_comercial }}" class="qr-image w-72 h-72 md:w-[28rem] md:h-[28rem] object-contain">
        </div>

        <div class="bg-slate-900 text-white w-full rounded-3xl p-10 mt-auto shadow-xl text-center">
            <p class="text-3xl md:text-4xl font-black mb-4">1. ESCANEA CON TU CÁMARA</p>
            <p class="text-xl md:text-2xl text-slate-300">2. Envía el mensaje automático a nuestro WhatsApp<br>3. Sube tu PDF y paga desde tu teléfono.</p>
        </div>
        
        <p class="mt-8 text-xl font-bold text-slate-400">WhatsApp: {{ config('evolution.whatsapp_number', env('EVOLUTION_WHATSAPP_NUMBER')) }}</p>

    </div>

</body>
</html>
