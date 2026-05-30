<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QRs de Kioskos - Impresión</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Estilos específicos para impresión */
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .print-page {
                page-break-after: always;
                height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
                box-sizing: border-box;
                padding: 2cm;
            }
            .print-page:last-child {
                page-break-after: auto;
            }
            .qr-image {
                width: 12cm;
                height: 12cm;
                margin: 2cm 0;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">

    <!-- Encabezado web (se oculta al imprimir) -->
    <div class="no-print max-w-5xl mx-auto pt-10 px-6 pb-6 text-center">
        <h1 class="text-4xl font-black text-slate-900 mb-3 tracking-tight">Posters de Kioskos listos para imprimir</h1>
        <p class="text-lg text-slate-500 mb-8 max-w-2xl mx-auto">Imprime directamente desde esta página. Cada código QR se generará automáticamente en una hoja A4 completa, con un diseño profesional listo para ser pegado en tu sede.</p>
        
        <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-10 rounded-full shadow-lg shadow-indigo-600/30 transition-all transform hover:scale-105 flex items-center justify-center gap-3 mx-auto text-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Imprimir Todos los Posters en A4
        </button>
    </div>

    <!-- Contenedor principal de Posters -->
    <div class="max-w-4xl mx-auto px-6 pb-20 no-print flex flex-col gap-12 mt-10">
        @foreach(\App\Models\Kiosko::all() as $kiosk)
            <!-- Cada página de impresión (también se ve como tarjeta en la web) -->
            <div class="print-page bg-white p-12 rounded-[3rem] shadow-xl border border-slate-100 flex flex-col items-center relative overflow-hidden">
                
                <!-- Diseño gráfico superior -->
                <div class="absolute top-0 left-0 w-full h-4 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                <div class="mb-4 text-indigo-600">
                    <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                </div>
                
                <h2 class="text-6xl font-black text-slate-900 tracking-tight uppercase mb-2">IMPRIME AQUÍ</h2>
                <p class="text-2xl text-slate-500 font-medium mb-2">SEDE: <span class="font-bold text-indigo-600">{{ $kiosk->nombre_comercial }}</span></p>

                <!-- El QR Code -->
                <div class="p-4 bg-white rounded-3xl border-[6px] border-indigo-50 shadow-2xl mt-10 mb-10">
                    <img src="{{ route('kiosko.whatsapp-qr.kiosk', $kiosk->id) }}" alt="QR de {{ $kiosk->nombre_comercial }}" class="qr-image w-64 h-64 md:w-96 md:h-96 object-contain">
                </div>

                <div class="bg-slate-900 text-white w-full rounded-3xl p-8 mt-auto shadow-xl">
                    <p class="text-3xl font-black mb-3">1. ESCANEA CON TU CÁMARA</p>
                    <p class="text-xl text-slate-300">2. Envía el mensaje preescrito a nuestro bot de WhatsApp<br>3. Sube tu PDF y paga desde tu teléfono.</p>
                </div>
                
                <p class="mt-8 text-lg font-bold text-slate-400">Nuestro WhatsApp: {{ config('evolution.whatsapp_number', env('EVOLUTION_WHATSAPP_NUMBER')) }}</p>

            </div>
        @endforeach

        @if(\App\Models\Kiosko::count() === 0)
            <div class="bg-white p-12 rounded-3xl text-center shadow-sm">
                <p class="text-xl text-slate-500">Todavía no tienes sedes registradas. Crea una primero para generar su QR.</p>
            </div>
        @endif
    </div>

</body>
</html>
