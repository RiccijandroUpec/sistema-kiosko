<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Impresión - RickTech</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .step-line { position: relative; }
        .step-line::after {
            content: ''; position: absolute; left: 1.25rem; top: 2.5rem; bottom: 0;
            width: 2px; background: #e2e8f0; z-index: 0;
        }
        .step-line:last-child::after { display: none; }
        .pulse-indigo { animation: pulse-indigo 2s infinite; }
        @keyframes pulse-indigo {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(79, 70, 229, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased pb-10">

    @php
        $status = $printJob->status;
        $paymentStatus = $printJob->payment->status;
        
        $isReceived = true;
        $isPaid = $paymentStatus === 'confirmed' || $printJob->paid;
        $isPrinting = $status === 'printing' && $isPaid;
        $isCompleted = $status === 'completed';
        $isCancelled = $status === 'cancelled';
    @endphp

    <!-- Header Compacto -->
    <nav class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-100">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('kiosko.index') }}" class="flex items-center gap-2 text-slate-500 hover:text-indigo-600 font-bold text-sm transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Inicio
            </a>
            <div class="px-4 py-1 bg-slate-100 rounded-full text-[10px] font-black text-slate-500 uppercase tracking-widest">
                Ref: {{ $printJob->job_reference }}
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-6 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
            
            <!-- COLUMNA IZQUIERDA: Linea de Tiempo -->
            <div class="bg-white rounded-[2.5rem] p-8 shadow-xl shadow-slate-200/50 border border-slate-100">
                <h3 class="text-xl font-black text-slate-800 mb-8">Seguimiento en Vivo</h3>
                
                <div class="space-y-0">
                    <!-- Paso 1: Recibido -->
                    <div class="step-line pb-10 flex gap-6 items-start">
                        <div class="relative z-10 w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $isReceived ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-100' : 'bg-slate-100 text-slate-400' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 leading-tight">Pedido Recibido</h4>
                            <p class="text-xs text-slate-500 mt-1">Hemos recibido tu archivo correctamente.</p>
                        </div>
                    </div>

                    <!-- Paso 2: Pago -->
                    <div class="step-line pb-10 flex gap-6 items-start">
                        <div class="relative z-10 w-10 h-10 rounded-full flex items-center justify-center shrink-0 
                            {{ $isPaid ? 'bg-emerald-500 text-white' : 'bg-indigo-600 text-white pulse-indigo shadow-lg shadow-indigo-100' }}">
                            @if($isPaid)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            @endif
                        </div>
                        <div>
                            <h4 class="font-bold {{ $isPaid ? 'text-slate-800' : 'text-indigo-600' }} leading-tight">Validación de Pago</h4>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ $isPaid ? 'Pago confirmado con éxito.' : 'Esperando que el administrador confirme el pago.' }}
                            </p>
                            @if(!$isPaid && !$isCancelled)
                                <a href="{{ route('kiosko.payment', $printJob->id) }}" class="inline-block mt-3 px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-100 transition-all">Ver Datos de Pago</a>
                            @endif
                        </div>
                    </div>

                    <!-- Paso 3: Impresión -->
                    <div class="step-line pb-10 flex gap-6 items-start">
                        <div class="relative z-10 w-10 h-10 rounded-full flex items-center justify-center shrink-0 
                            {{ $isCompleted ? 'bg-emerald-500 text-white' : ($isPrinting ? 'bg-indigo-600 text-white pulse-indigo shadow-lg shadow-indigo-100' : 'bg-slate-100 text-slate-400') }}">
                            @if($isCompleted)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            @endif
                        </div>
                        <div>
                            <h4 class="font-bold {{ $isPrinting ? 'text-indigo-600' : 'text-slate-800' }} leading-tight">Impresión en Cola</h4>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ $isCompleted ? 'El documento ha sido impreso.' : ($isPrinting ? 'Tu documento se está imprimiendo ahora mismo.' : 'Tu turno llegará pronto.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Paso 4: ¡Listo! -->
                    <div class="step-line flex gap-6 items-start">
                        <div class="relative z-10 w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $isCompleted ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200 scale-125' : 'bg-slate-100 text-slate-400' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-bold {{ $isCompleted ? 'text-emerald-600' : 'text-slate-800' }} leading-tight">¡Listo para Retirar!</h4>
                            <p class="text-xs text-slate-500 mt-1">Ya puedes pasar por tu pedido.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: Detalles y Acción -->
            <div class="space-y-6">
                <!-- Info Card -->
                <div class="bg-white rounded-[2.5rem] p-8 shadow-xl shadow-slate-200/50 border border-slate-100">
                    <h3 class="text-lg font-black text-slate-800 mb-6">Detalles del Trabajo</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-3 border-b border-slate-50">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Archivo</span>
                            <span class="text-sm font-bold text-slate-800 truncate max-w-[150px]">{{ $printJob->pdfFile->original_name }}</span>
                        </div>
                        <div class="flex justify-between items-center py-3 border-b border-slate-50">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Configuración</span>
                            <span class="text-sm font-bold text-slate-800">{{ $printJob->copies }}x {{ $printJob->color_type === 'color' ? 'Color' : 'B/N' }} ({{ strtoupper($printJob->paper_size) }})</span>
                        </div>
                        <div class="flex justify-between items-center py-3 border-b border-slate-50">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Pagado</span>
                            <span class="text-lg font-black text-indigo-600">${{ number_format($printJob->cost, 2) }}</span>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col gap-3">
                        <button onclick="location.reload()" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black text-center shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all active:scale-95">
                            ACTUALIZAR AHORA
                        </button>
                        <a href="{{ route('kiosko.index') }}" class="w-full py-3 bg-slate-100 text-slate-500 rounded-xl font-bold text-xs text-center hover:bg-slate-200 transition-all">
                            CREAR NUEVO PEDIDO
                        </a>
                    </div>
                </div>

                <!-- Alert Box -->
                @if($isCompleted)
                    <div class="bg-emerald-500 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-emerald-100 text-center">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <h4 class="text-xl font-black mb-2">¡Todo listo!</h4>
                        <p class="text-emerald-50 font-medium text-sm leading-relaxed">Puedes pasar a retirar tu impresión. Muestra tu código de referencia al administrador.</p>
                    </div>
                @elseif($isCancelled)
                    <div class="bg-red-500 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-red-100 text-center">
                        <h4 class="text-xl font-black mb-2">Pedido Cancelado</h4>
                        <p class="text-red-50 font-medium text-sm leading-relaxed">Este trabajo ha sido cancelado. Por favor, contacta al administrador.</p>
                    </div>
                @else
                    <div class="bg-white rounded-[2.5rem] p-8 border-2 border-dashed border-slate-200 text-center">
                        <p class="text-slate-400 text-sm font-medium">La página se actualizará automáticamente cada 30 segundos.</p>
                    </div>
                @endif
            </div>
        </div>
    </main>

    @if(!$isCompleted && !$isCancelled)
    <script>
        setTimeout(() => location.reload(), 30000);
    </script>
    @endif
</body>
</html>
