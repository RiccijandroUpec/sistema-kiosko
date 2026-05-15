<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago - RickTech</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow-x: hidden; }
        [x-cloak] { display: none !important; }
        @media (min-width: 768px) {
            .no-scroll-pc { height: calc(100vh - 60px); overflow: hidden; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased" x-data="paymentPIN()">
    
    <!-- Header Ultra-Compacto -->
    <div class="bg-white border-b border-slate-100 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-6 py-2">
            <div class="flex items-center justify-center gap-6">
                <div class="flex items-center gap-2 opacity-50">
                    <div class="w-5 h-5 rounded-full flex items-center justify-center text-[8px] font-bold bg-emerald-500 text-white">✓</div>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Archivo</span>
                </div>
                <div class="w-8 h-[1px] bg-slate-200"></div>
                <div class="flex items-center gap-2">
                    <div class="w-5 h-5 rounded-full flex items-center justify-center text-[8px] font-bold bg-indigo-600 text-white ring-2 ring-indigo-100">3</div>
                    <span class="text-[9px] font-bold text-indigo-600 uppercase tracking-widest">Pago</span>
                </div>
                <div class="w-8 h-[1px] bg-slate-100"></div>
                <div class="flex items-center gap-2 text-slate-300">
                    <div class="w-5 h-5 rounded-full flex items-center justify-center text-[8px] font-bold border border-slate-200">4</div>
                    <span class="text-[9px] font-bold uppercase tracking-widest">Listo</span>
                </div>
            </div>
        </div>
    </div>

    <main class="max-w-6xl mx-auto px-4 py-4 md:py-6 no-scroll-pc flex items-center">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
            
            <!-- COLUMNA IZQUIERDA -->
            <div class="space-y-4">
                <div class="bg-indigo-600 rounded-[2rem] p-6 text-white shadow-xl relative overflow-hidden">
                    <p class="text-indigo-100 text-xs font-semibold mb-1 opacity-80">Total a pagar</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-5xl font-black tracking-tight">${{ number_format($payment->amount, 2) }}</span>
                        <span class="text-indigo-200 font-bold uppercase text-[10px] tracking-widest">USD</span>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-white/20 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-white/60 text-[9px] uppercase font-bold tracking-wider">Referencia</p>
                            <p class="font-mono font-bold text-sm">{{ $printJob->job_reference }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-white/60 text-[9px] uppercase font-bold tracking-wider">Configuración</p>
                            <p class="font-bold text-sm">{{ $printJob->copies }}x {{ $printJob->color_type === 'color' ? 'Color' : 'B/N' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-4 border border-slate-100 shadow-sm flex items-center gap-4">
                    <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="text-xs">
                        <p class="font-bold text-slate-800">Archivo: {{ Str::limit($printJob->pdfFile->original_name, 30) }}</p>
                        <p class="text-slate-500">{{ $printJob->pdfFile->pages_count }} páginas • {{ strtoupper($printJob->paper_size) }}</p>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA -->
            <div class="bg-white rounded-[2rem] p-5 shadow-lg border border-slate-100 flex flex-col justify-between">
                <div>
                    <h3 class="text-base font-black text-slate-800 mb-4 flex items-center gap-2">
                        <div class="w-8 h-8 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </div>
                        Pagar con Deuna! / Pichincha
                    </h3>

                    <div class="grid grid-cols-2 gap-4 items-center mb-4">
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 text-center">
                            <img src="{{ asset('images/qrpichincha.png') }}" alt="QR" class="w-44 h-44 mx-auto rounded-lg mb-2 shadow-sm">
                            <p class="text-[10px] font-bold text-slate-800 uppercase tracking-tighter">Richard Stalyn Rodriguez</p>
                        </div>
                        <div class="space-y-3">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 relative group">
                                <p class="text-[8px] font-bold text-slate-400 uppercase">Nº de Cuenta</p>
                                <p class="font-mono font-bold text-slate-800 text-xs">2210344445</p>
                                <button onclick="copyToClipboard('2210344445')" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 bg-white text-indigo-600 rounded-lg shadow-sm hover:scale-110 transition-all">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </button>
                            </div>
                            <div class="p-3 bg-amber-50 rounded-xl border border-dashed border-amber-200 relative">
                                <p class="text-[8px] font-bold text-amber-600 uppercase">Concepto</p>
                                <p class="font-mono font-black text-amber-800 text-sm tracking-widest">{{ $payment->reference_code }}</p>
                                <button onclick="copyToClipboard('{{ $payment->reference_code }}')" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 bg-white text-amber-600 rounded-lg shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción Mini -->
                <div class="space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <a href="https://link.deuna.app/open" class="py-3 bg-[#FFD100] text-black rounded-xl font-black text-[10px] text-center shadow-sm flex items-center justify-center gap-1">
                            PAGAR CON DEUNA!
                        </a>
                        <a href="{{ route('kiosko.status', $printJob->job_reference) }}" class="py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] text-center shadow-sm">
                            ESTADO EN VIVO
                        </a>
                    </div>
                    <div class="flex gap-2">
                        <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', config('evolution.whatsapp_number')) }}?text={{ urlencode('Hola! Ya realicé el pago por Deuna. Mi referencia es: ' . $payment->reference_code . ' por un monto de $' . number_format($payment->amount, 2)) }}" 
                           class="flex-[3] py-2.5 bg-emerald-500 text-white rounded-xl font-bold text-[10px] text-center flex items-center justify-center gap-1 shadow-md">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.414 0 .018 5.393 0 12.03c0 2.122.554 4.197 1.61 6.006L0 24l6.135-1.61a11.83 11.83 0 005.912 1.579h.005c6.637 0 12.032-5.392 12.034-12.029a11.78 11.78 0 00-3.486-8.484z"/></svg>
                            ENVIAR COMPROBANTE
                        </a>
                        <button @click="openModal = true" class="flex-1 py-2 bg-slate-50 text-slate-400 rounded-xl font-bold text-[8px] uppercase border border-slate-100">
                            🔓 PIN
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- PIN Modal -->
    <div x-show="openModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[100] flex items-center justify-center p-4" x-transition x-cloak>
        <div class="bg-white w-full max-w-sm rounded-[2rem] p-6 shadow-2xl" @click.away="openModal = false">
            <h3 class="text-lg font-black text-slate-800 text-center mb-6">PIN Administrador</h3>
            <div class="flex justify-center gap-3 mb-8">
                <template x-for="i in 4">
                    <div class="w-10 h-12 rounded-xl border-2 flex items-center justify-center text-xl font-black transition-all"
                         :class="pin.length >= i ? 'border-indigo-600 bg-indigo-50 text-indigo-600' : 'border-slate-100 bg-slate-50'">
                        <span x-text="pin.length >= i ? '●' : ''"></span>
                    </div>
                </template>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <template x-for="n in [1,2,3,4,5,6,7,8,9,0]">
                    <button @click="addNumber(n)" class="h-12 rounded-xl bg-slate-50 text-lg font-bold text-slate-700 active:bg-indigo-600 active:text-white shadow-sm" x-text="n"></button>
                </template>
                <button @click="pin = ''" class="h-12 rounded-xl bg-red-50 text-red-500 font-bold active:bg-red-500">C</button>
                <button @click="deleteNumber()" class="h-12 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"></path></svg>
                </button>
            </div>
            <p x-show="error" class="text-center text-red-500 font-bold mt-4 text-xs" x-text="error"></p>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 left-1/2 -translate-x-1/2 bg-slate-800 text-white px-4 py-2 rounded-xl text-[10px] font-bold shadow-xl z-[110] animate-bounce';
                toast.innerText = '✓ Copiado';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 1500);
            });
        }

        function paymentPIN() {
            return {
                openModal: false, pin: '', error: '',
                init() {
                    window.addEventListener('keydown', (e) => {
                        if (!this.openModal) return;
                        if (e.key >= '0' && e.key <= '9') {
                            this.addNumber(e.key);
                        } else if (e.key === 'Backspace') {
                            this.deleteNumber();
                        } else if (e.key === 'Escape') {
                            this.openModal = false;
                            this.pin = '';
                        }
                    });
                },
                async addNumber(n) {
                    if (this.pin.length < 4) {
                        this.pin += n;
                        if (this.pin.length === 4) await this.verifyPIN();
                    }
                },
                deleteNumber() { this.pin = this.pin.slice(0, -1); this.error = ''; },
                async verifyPIN() {
                    try {
                        const response = await fetch('{{ route("kiosko.api.release-with-pin", $printJob->id) }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ pin: this.pin })
                        });
                        const data = await response.json();
                        if (data.success) window.location.href = '{{ route("kiosko.status", $printJob->job_reference) }}';
                        else { this.error = 'PIN Incorrecto'; this.pin = ''; setTimeout(() => this.error = '', 2000); }
                    } catch (e) { this.error = 'Error'; this.pin = ''; }
                }
            }
        }
    </script>
</body>
</html>
