<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Impresible - {{ $kiosk->nombre }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .page { box-shadow: none !important; border: none !important; }
            .print-page { break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <div class="no-print sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-slate-200">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.35em] text-slate-400">QR imprimible por sede</p>
                <h1 class="text-lg font-black text-slate-900">{{ $kiosk->nombre }}</h1>
            </div>
            <div class="flex gap-2">
                <button onclick="window.print()" class="rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Imprimir</button>
                <a href="{{ route('admin.kiosks.index') }}" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Volver</a>
            </div>
        </div>
    </div>

    <main class="max-w-5xl mx-auto px-4 py-8">
        <section class="page print-page mx-auto max-w-3xl overflow-hidden rounded-[2.5rem] bg-white shadow-2xl shadow-slate-300/40 border border-slate-200">
            <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-900 px-8 py-10 text-white relative overflow-hidden">
                <div class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.35),_transparent_30%),radial-gradient(circle_at_bottom_left,_rgba(99,102,241,0.5),_transparent_32%)]"></div>
                <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.45em] text-indigo-200">RickTech Kiosko</p>
                        <h2 class="mt-2 text-3xl md:text-4xl font-black tracking-tight">Escanea, envía y recibe impresión</h2>
                        <p class="mt-3 max-w-xl text-sm text-slate-300">Este QR abre WhatsApp con la sede predefinida. El sistema reconocerá automáticamente este kiosko cuando el cliente envíe el mensaje.</p>
                    </div>
                    <div class="rounded-3xl bg-white/10 px-5 py-4 backdrop-blur border border-white/10 min-w-[220px]">
                        <p class="text-[10px] font-black uppercase tracking-[0.35em] text-indigo-200">Sede</p>
                        <p class="mt-1 text-xl font-black">{{ $kiosk->nombre }}</p>
                        <p class="text-sm text-slate-300">{{ $kiosk->ubicacion ?? 'Sin ubicación' }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-0 md:grid-cols-[1.08fr_0.92fr]">
                <div class="flex items-center justify-center bg-slate-50 p-8 md:p-12">
                    <div class="rounded-[2rem] bg-white p-5 md:p-6 shadow-xl shadow-slate-200 border border-slate-100">
                        <div class="rounded-[1.5rem] bg-slate-50 p-4 md:p-5">
                            <img src="{{ route('kiosko.whatsapp-qr.kiosk', $kiosk) }}" alt="QR WhatsApp {{ $kiosk->nombre }}" class="w-72 h-72 md:w-80 md:h-80 object-contain">
                        </div>
                    </div>
                </div>
                <div class="flex flex-col justify-center gap-5 p-8 md:p-10 bg-white">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.35em] text-slate-400">Instrucciones</p>
                        <ol class="mt-3 space-y-3 text-sm text-slate-600">
                            <li class="flex gap-3"><span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-black text-white">1</span> Escanea el QR con WhatsApp.</li>
                            <li class="flex gap-3"><span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-black text-white">2</span> Envía el mensaje y luego sube tu PDF.</li>
                            <li class="flex gap-3"><span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-black text-white">3</span> El sistema lo enviará a esta sede automáticamente.</li>
                        </ol>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <div class="grid gap-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold text-slate-500">WhatsApp</span>
                                <span class="font-black text-slate-900">{{ config('evolution.whatsapp_number') }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold text-slate-500">Estado</span>
                                <span class="font-black text-emerald-600">{{ ucfirst($kiosk->estado_conexion) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold text-slate-500">Aviso</span>
                                <span class="font-black text-slate-900">PIN no impreso</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs font-medium text-slate-400">Sugerencia: imprime esta tarjeta en tamaño carta o media carta y colócala junto al kiosko. El PIN de acceso se mantiene solo en el panel administrativo.</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>