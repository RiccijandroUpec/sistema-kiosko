<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin RickTech - Centro de Control</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; overflow: hidden; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); }
        .stat-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-900 antialiased" x-data="adminPanel()" x-cloak @load="init()">

    <!-- Barra de Navegación Premium -->
    <nav class="bg-white/80 backdrop-blur-md border-b border-slate-100 sticky top-0 z-40">
        <div class="max-w-screen-2xl mx-auto px-6 py-3 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-100">
                    <img src="{{ asset('images/app-icon.png') }}" alt="Logo" class="w-6 h-6 invert">
                </div>
                <div>
                    <h1 class="text-sm font-black text-slate-800 leading-none">RICKTECH</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Admin Dashboard</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden md:flex bg-slate-100 p-1 rounded-xl gap-1">
                    <button @click="openModal('whatsapp')" class="px-3 py-1.5 text-[10px] font-black uppercase tracking-tight text-slate-500 hover:text-emerald-600 transition-colors">WhatsApp</button>
                    <button @click="openModal('settings')" class="px-3 py-1.5 text-[10px] font-black uppercase tracking-tight text-slate-500 hover:text-indigo-600 transition-colors">Precios</button>
                </div>
                <div class="h-8 w-px bg-slate-100 mx-2"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-screen-2xl mx-auto px-6 py-6 h-[calc(100vh-65px)] flex flex-col gap-6">
        
        <!-- Métrica de Resumen -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 shrink-0">
            <div class="stat-card bg-white rounded-3xl p-5 border border-slate-50 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pendientes</p>
                    <p class="text-2xl font-black text-slate-800" x-text="stats.pending || 0">0</p>
                </div>
            </div>
            <div class="stat-card bg-white rounded-3xl p-5 border border-slate-50 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Listos</p>
                    <p class="text-2xl font-black text-slate-800" x-text="stats.ready || 0">0</p>
                </div>
            </div>
            <div class="stat-card bg-indigo-600 rounded-3xl p-5 shadow-xl shadow-indigo-100 flex items-center gap-4 text-white">
                <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest">Hoy (Confirmado)</p>
                    <p class="text-2xl font-black" x-text="'$' + (stats.revenue || 0).toFixed(2)">$0.00</p>
                </div>
            </div>
            <div class="stat-card bg-white rounded-3xl p-5 border border-slate-50 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Completados</p>
                    <p class="text-2xl font-black text-slate-800" x-text="stats.confirmed || 0">0</p>
                </div>
            </div>
        </div>

        <!-- Cuerpo Principal -->
        <div class="flex-1 flex flex-col min-h-0 bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50 overflow-hidden">
            <div class="px-8 py-5 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h2 class="text-lg font-black text-slate-800 tracking-tight">Registro de Trabajos</h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Monitoreo en tiempo real</p>
                </div>
                
                <div class="flex bg-slate-50 p-1 rounded-2xl gap-1">
                    <button @click="loadJobs('')" class="px-4 py-1.5 text-[10px] font-black uppercase rounded-xl transition-all" :class="!currentFilter ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-400'">Todos</button>
                    <button @click="loadJobs('pending')" class="px-4 py-1.5 text-[10px] font-black uppercase rounded-xl transition-all" :class="currentFilter === 'pending' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-400'">Pendientes</button>
                    <button @click="loadJobs('printing')" class="px-4 py-1.5 text-[10px] font-black uppercase rounded-xl transition-all" :class="currentFilter === 'printing' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400'">Listos</button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scroll px-4">
                <div id="jobsList" class="divide-y divide-slate-50">
                    <!-- Contenido dinámico -->
                    <div class="p-20 text-center text-slate-300 font-bold">Cargando central de datos...</div>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL: Cobro Inteligente -->
    <div x-show="modal === 'payment'" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-transition x-cloak @click.self="modal = null">
        <div class="bg-white rounded-[2.5rem] shadow-2xl max-w-md w-full overflow-hidden border border-slate-100">
            <div class="bg-indigo-600 p-6 text-white text-center">
                <h3 class="text-xl font-black tracking-tight">Registrar Cobro</h3>
                <p class="text-indigo-200 text-xs font-bold font-mono" x-text="selectedJob?.job_reference"></p>
            </div>
            <div class="p-8" x-data="{ cashReceived: 0 }">
                <div class="bg-slate-50 rounded-2xl p-5 mb-6 border-2 border-dashed border-slate-200 text-center">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total a pagar</span>
                    <p class="text-4xl font-black text-indigo-600" x-text="'$' + (selectedJob?.cost || 0)"></p>
                </div>
                <div class="space-y-4">
                    <input type="number" step="0.01" x-model.number="cashReceived" 
                           class="w-full py-4 bg-slate-100 border-none rounded-2xl text-2xl font-black text-slate-800 text-center focus:ring-2 focus:ring-indigo-500"
                           placeholder="Ingresar efectivo" autofocus>
                    
                    <div x-show="cashReceived > (selectedJob?.cost || 0)" class="bg-emerald-50 rounded-2xl p-4 text-center">
                        <p class="text-emerald-600 text-[10px] font-black uppercase">Cambio</p>
                        <p class="text-2xl font-black text-emerald-700" x-text="'$' + (cashReceived - (selectedJob?.cost || 0)).toFixed(2)"></p>
                    </div>

                    <button @click="performAction('confirmar-pago', selectedJob.id, true)" 
                            class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black text-center shadow-xl shadow-indigo-100 active:scale-95 transition-all">
                        CONFIRMAR PAGO
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Precios -->
    <div x-show="modal === 'settings'" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-transition x-cloak @click.self="modal = null">
        <div class="bg-white rounded-[2.5rem] shadow-2xl max-w-sm w-full p-8">
            <h3 class="text-xl font-black text-slate-800 mb-6 tracking-tight">Ajustes de Precios</h3>
            <form @submit.prevent="updatePrices()" class="space-y-4">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">B/N por página</label>
                    <input type="number" step="0.01" x-model.number="prices.bw" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-black text-slate-800">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Color por página</label>
                    <input type="number" step="0.01" x-model.number="prices.color" class="w-full bg-slate-50 border-none rounded-2xl p-4 font-black text-slate-800">
                </div>
                <button type="submit" class="w-full py-4 bg-slate-800 text-white rounded-2xl font-black shadow-lg" x-text="saving ? 'Guardando...' : 'GUARDAR CAMBIOS'"></button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adminPanel', () => ({
            modal: null, stats: { pending: 0, confirmed: 0, ready: 0, revenue: 0 },
            prices: { bw: {{ config('printing.cost_bw') }}, color: {{ config('printing.cost_color') }} },
            saving: false, selectedJob: null, jobs: [], currentFilter: '',

            init() {
                this.loadStats(); this.loadJobs();
                setInterval(() => { this.loadStats(); this.loadJobs(this.currentFilter); }, 15000);
            },

            openModal(name) { this.modal = name; },

            async loadStats() {
                const res = await fetch('{{ route("admin.api.stats") }}');
                this.stats = await res.json();
            },

            async loadJobs(status = '') {
                this.currentFilter = status;
                const url = new URL('{{ route("admin.api.jobs") }}', location.origin);
                if (status) url.searchParams.set('status', status);
                const res = await fetch(url);
                const data = await res.json();
                this.jobs = data.jobs;
                this.renderJobs();
            },

            renderJobs() {
                const html = this.jobs.length ? this.jobs.map(job => `
                    <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50/50 transition-colors group">
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-10 h-10 ${this.statusIconBg(job.status)} rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 ${this.statusIconColor(job.status)}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-black text-slate-800 text-sm tracking-tight">${job.job_reference}</p>
                                    <span class="px-2 py-0.5 text-[8px] font-black uppercase rounded-full ${this.statusBadge(job.status)}">${this.statusLabel(job.status)}</span>
                                </div>
                                <p class="text-[10px] font-bold text-slate-400 truncate max-w-[300px]">${job.pdf_file.original_name}</p>
                                <p class="text-[9px] font-black text-indigo-600 mt-0.5">${job.copies}x ${job.color_type === 'color' ? 'COLOR' : 'B/N'} • ${job.pdf_file.pages_count} PÁG • $${job.cost}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="/admin/trabajos/${job.id}/descargar" target="_blank" class="p-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            </a>
                            ${job.status === 'pending' && !job.paid ? `
                                <button onclick="window.adminData.prepareCheckout(${job.id})" class="px-4 py-2 bg-emerald-500 text-white text-[9px] font-black uppercase rounded-lg shadow-lg shadow-emerald-100 active:scale-95">COBRAR</button>
                            ` : ''}
                            ${job.status === 'printing' ? `
                                <button onclick="window.adminData.performAction('impreso', ${job.id}, true)" class="px-4 py-2 bg-indigo-600 text-white text-[9px] font-black uppercase rounded-lg shadow-lg shadow-indigo-100 active:scale-95">LISTO</button>
                            ` : ''}
                            <button onclick="window.adminData.performAction('delete', ${job.id})" class="p-2 text-slate-300 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                `).join('') : '<div class="p-20 text-center text-slate-300 font-bold">No hay trabajos activos</div>';
                document.getElementById('jobsList').innerHTML = html;
            },

            statusBadge(s) { 
                return { pending: 'bg-amber-100 text-amber-600', printing: 'bg-indigo-100 text-indigo-600', completed: 'bg-emerald-100 text-emerald-600', cancelled: 'bg-red-100 text-red-600' }[s]; 
            },
            statusIconBg(s) { 
                return { pending: 'bg-amber-50', printing: 'bg-indigo-50', completed: 'bg-emerald-50', cancelled: 'bg-red-50' }[s]; 
            },
            statusIconColor(s) { 
                return { pending: 'text-amber-500', printing: 'text-indigo-500', completed: 'text-emerald-500', cancelled: 'text-red-500' }[s]; 
            },
            statusLabel(s) { return { pending: 'PAGO PENDIENTE', printing: 'POR IMPRIMIR', completed: 'COMPLETADO', cancelled: 'CANCELADO' }[s]; },

            prepareCheckout(jobId) { this.selectedJob = this.jobs.find(j => j.id === jobId); this.modal = 'payment'; },

            async performAction(action, jobId, skipConfirm = false) {
                let url = action === 'delete' ? `/admin/trabajos/${jobId}` : (action === 'impreso' || action === 'confirmar-pago' ? `/admin/trabajos/${jobId}/impreso` : `/admin/trabajos/${jobId}/cancelar`);
                if (!skipConfirm && !confirm('¿Ejecutar esta acción?')) return;
                const res = await fetch(url, { method: action === 'delete' ? 'DELETE' : 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
                if (res.ok) { this.loadStats(); this.loadJobs(this.currentFilter); this.modal = null; }
            },

            async updatePrices() {
                this.saving = true;
                const res = await fetch('{{ route("admin.update-prices") }}', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ cost_bw: this.prices.bw, cost_color: this.prices.color })
                });
                if (res.ok) { alert('Precios actualizados'); this.modal = null; }
                this.saving = false;
            }
        }))
    });
    window.adminData = {};
    document.addEventListener('alpine:initialized', () => { window.adminData = Alpine.$data(document.querySelector('[x-data]')); });
    </script>
</body>
</html>
