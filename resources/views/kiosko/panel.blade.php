@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Panel de {{ $kiosk->nombre_comercial }}</h1>
            <p class="text-sm text-slate-500">Gestión local de trabajos para esta sede.</p>
        </div>
        <form method="POST" action="{{ route('kiosk.panel.logout') }}">
            @csrf
            <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Cerrar sesión</button>
        </form>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Total</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60">
            <p class="text-xs font-bold uppercase tracking-widest text-amber-500">Pendientes</p>
            <p class="mt-2 text-2xl font-black text-amber-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60">
            <p class="text-xs font-bold uppercase tracking-widest text-indigo-500">Imprimiendo</p>
            <p class="mt-2 text-2xl font-black text-indigo-600">{{ $stats['printing'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/60">
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-500">Completados</p>
            <p class="mt-2 text-2xl font-black text-emerald-600">{{ $stats['completed'] }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/60">
        <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Trabajos de esta sede</h2>
            <span class="text-xs font-black uppercase tracking-widest text-slate-400">{{ $printJobs->total() }} registros</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Referencia</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Archivo</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Config</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Estado</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Costo</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($printJobs as $printJob)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-4 font-black text-slate-900">{{ strtoupper(substr($printJob->id, 0, 8)) }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <div class="font-semibold text-slate-800">{{ basename($printJob->archivo_url) }}</div>
                                <div class="text-[10px] uppercase tracking-widest text-slate-400">{{ $printJob->paginas }} páginas en total</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $printJob->color ? 'A Color' : 'Blanco y Negro' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest
                                    {{ $printJob->estado === 'completado' ? 'bg-emerald-100 text-emerald-700' : ($printJob->estado === 'imprimiendo' ? 'bg-indigo-100 text-indigo-700' : ($printJob->estado === 'cancelado' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700')) }}">
                                    {{ $printJob->estado }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-black text-slate-900">${{ number_format($printJob->costo_total, 2) }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @if($printJob->estado !== 'completado' && $printJob->estado !== 'cancelado')
                                        <form method="POST" action="{{ route('kiosk.panel.mark-printed', $printJob) }}">
                                            @csrf
                                            <button type="submit" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                                {{ $printJob->estado === 'pendiente' ? 'Cobrar e Imprimir' : 'Completar' }}
                                            </button>
                                        </form>
                                    @endif
                                    @if(!in_array($printJob->estado, ['completado', 'cancelado'], true))
                                        <form method="POST" action="{{ route('kiosk.panel.cancel-job', $printJob) }}" onsubmit="return confirm('¿Cancelar este trabajo?')">
                                            @csrf
                                            <button type="submit" class="rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">Cancelar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-400">No hay trabajos asignados a este kiosko.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-6 py-4">
            {{ $printJobs->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
