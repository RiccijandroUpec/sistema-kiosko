@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-indigo-50 py-6 px-4" x-data="adminPanel()" x-cloak @load="init()">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Panel Administrativo</h1>
                <p class="text-gray-600 mt-1">Gestión de trabajos y precios</p>
            </div>
            <button @click="openModal('settings')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                ⚙️ Configuración
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                <p class="text-gray-600 text-sm font-medium">Pagos Pendientes</p>
                <p class="text-3xl font-bold text-gray-900 mt-2" x-text="stats.pending || 0"></p>
                <button @click="openModal('pending')" class="text-red-600 text-sm mt-3 font-medium">Ver →</button>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                <p class="text-gray-600 text-sm font-medium">Confirmados</p>
                <p class="text-3xl font-bold text-gray-900 mt-2" x-text="stats.confirmed || 0"></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <p class="text-gray-600 text-sm font-medium">Listos</p>
                <p class="text-3xl font-bold text-gray-900 mt-2" x-text="stats.ready || 0"></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                <p class="text-gray-600 text-sm font-medium">Ingresos</p>
                <p class="text-3xl font-bold text-gray-900 mt-2" x-text="'$' + stats.revenue.toFixed(2)"></p>
            </div>
        </div>

        <!-- Jobs Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Trabajos</h2>
                <select @change="loadJobs($event.target.value)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Todos</option>
                    <option value="pending">Pendiente</option>
                    <option value="printing">Listo</option>
                    <option value="completed">Completado</option>
                </select>
            </div>
            <div id="jobsList" class="divide-y divide-gray-200">
                <div class="p-4 text-center text-gray-500">Cargando...</div>
            </div>
        </div>
    </div>

    <!-- MODAL: Settings -->
    <div x-show="modal === 'settings'" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="modal = null">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Configuración de Precios</h3>
            <form @submit.prevent="updatePrices()">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio B/N por página ($)</label>
                    <input type="number" step="0.01" min="0" x-model.number="prices.bw" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio Color por página ($)</label>
                    <input type="number" step="0.01" min="0" x-model.number="prices.color" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded p-3 text-sm text-blue-700 mb-4">
                    <p><strong>Ejemplo:</strong> 2 pág B/N × 1 copia = $<span x-text="(prices.bw * 2).toFixed(2)"></span></p>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium" x-text="saving ? 'Guardando...' : 'Guardar'"></button>
                    <button type="button" @click="modal = null" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Pending Payments -->
    <div x-show="modal === 'pending'" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" @click.self="modal = null">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-96 overflow-y-auto">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Pagos Pendientes</h3>
            <div id="pendingPayments" class="space-y-2">
                <p class="text-gray-500 text-center py-4">Cargando...</p>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adminPanel', () => ({
            modal: null,
            stats: { pending: 0, confirmed: 0, ready: 0, revenue: 0 },
            prices: { bw: 0.05, color: 0.20 },
            saving: false,

            init() {
                this.loadStats();
                this.loadJobs();
                setInterval(() => this.loadStats(), 30000);
            },

            openModal(name) {
                this.modal = name;
                if (name === 'pending') {
                    this.loadPendingPayments();
                }
            },

            async loadStats() {
                try {
                    const res = await fetch('{{ route("admin.api.stats") }}');
                    this.stats = await res.json();
                } catch (e) {
                    console.error('Error:', e);
                }
            },

            async loadJobs(status = '') {
                try {
                    const url = new URL('{{ route("admin.api.jobs") }}', location.origin);
                    if (status) url.searchParams.set('status', status);
                    const res = await fetch(url);
                    const { jobs } = await res.json();
                    this.renderJobs(jobs);
                } catch (e) {
                    console.error('Error:', e);
                }
            },

            renderJobs(jobs) {
                const html = jobs.length ? jobs.map(job => `
                    <div class="p-4 flex justify-between items-center hover:bg-gray-50">
                        <div>
                            <p class="font-medium text-gray-900">${job.job_reference}</p>
                            <p class="text-sm text-gray-600">${job.pdf_file.original_name}</p>
                            <p class="text-xs text-gray-500 mt-1">${job.copies} copias • ${job.color_type} • $${job.cost}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-2 py-1 text-xs font-bold rounded ${this.statusColor(job.status)}">${this.statusLabel(job.status)}</span>
                            ${job.status === 'pending' && !job.paid ? `<button onclick="window.adminData.confirmPayment(${job.id})" class="block mt-2 text-blue-600 text-sm font-medium">Confirmar</button>` : ''}
                        </div>
                    </div>
                `).join('') : '<div class="p-4 text-center text-gray-500">No hay trabajos</div>';
                document.getElementById('jobsList').innerHTML = html;
            },

            statusColor(status) {
                const colors = {
                    pending: 'bg-yellow-100 text-yellow-800',
                    printing: 'bg-blue-100 text-blue-800',
                    completed: 'bg-green-100 text-green-800',
                    cancelled: 'bg-red-100 text-red-800'
                };
                return colors[status] || 'bg-gray-100 text-gray-800';
            },

            statusLabel(status) {
                const labels = { pending: 'Pendiente', printing: 'Listo', completed: 'Completado', cancelled: 'Cancelado' };
                return labels[status] || status;
            },

            async loadPendingPayments() {
                try {
                    const res = await fetch('{{ route("admin.api.pending-payments") }}');
                    const { payments } = await res.json();
                    const html = payments.length ? payments.map(p => `
                        <div class="p-3 bg-gray-50 rounded flex justify-between items-center">
                            <div>
                                <p class="font-medium">${p.reference_code}</p>
                                <p class="text-sm text-gray-600">$${p.amount}</p>
                            </div>
                            <button onclick="window.adminData.confirmPayment(${p.print_job_id})" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                Confirmar
                            </button>
                        </div>
                    `).join('') : '<p class="text-center text-gray-500 py-4">Sin pagos pendientes</p>';
                    document.getElementById('pendingPayments').innerHTML = html;
                } catch (e) {
                    console.error('Error:', e);
                }
            },

            async updatePrices() {
                this.saving = true;
                try {
                    const res = await fetch('{{ route("admin.update-prices") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('[name="csrf-token"]').content
                        },
                        body: JSON.stringify(this.prices)
                    });
                    const { success } = await res.json();
                    if (success) {
                        alert('Precios actualizados');
                        this.modal = null;
                    }
                } catch (e) {
                    console.error('Error:', e);
                }
                this.saving = false;
            },

            confirmPayment(jobId) {
                if (confirm('¿Confirmar pago?')) {
                    fetch(`/admin/trabajos/${jobId}/confirmar-pago`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('[name="csrf-token"]').content }
                    }).then(() => location.reload());
                }
            }
        }))
    });

    // Global reference for inline onclick
    window.adminData = Alpine.store('adminPanel') || window;
    document.addEventListener('alpine:init', () => {
        const admin = document.querySelector('[x-data*="adminPanel"]')?.__x?.getUnobservedData?.() || {};
        window.adminData = admin;
        window.adminData.confirmPayment = admin.confirmPayment?.bind(admin);
    });
    </script>

    <style>
    [x-cloak] { display: none !important; }
    </style>
@endsection
