@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Trabajos de Impresión</h1>
            <p class="text-sm text-slate-500">Cola e historial de trabajos enviados por los kioskos.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Volver al dashboard</a>
            <a href="{{ route('admin.kiosks.index') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Ver kioskos</a>
        </div>
    </div>

    <div class="rounded-3xl bg-white shadow-xl shadow-slate-200/40 border border-slate-50 overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-black text-slate-800">Listado Central</h2>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Historial y trabajos activos</p>
            </div>
            <span class="text-xs font-black uppercase tracking-widest text-slate-400">{{ $printJobs->total() }} trabajos</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Referencia</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Kiosko</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Archivo</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Configuración</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Estado</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Costo</th>
                        <th class="px-6 py-3 text-left text-[10px] font-black uppercase tracking-widest text-slate-400">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($printJobs as $printJob)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-4 font-black text-slate-900">{{ $printJob->job_reference }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $printJob->kiosk->nombre ?? 'Sin kiosko' }}
                                <div class="text-[10px] uppercase tracking-widest text-slate-400">{{ $printJob->kiosk->estado_conexion ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <div class="font-semibold text-slate-800">{{ $printJob->pdfFile->original_name ?? 'PDF no disponible' }}</div>
                                <div class="text-[10px] uppercase tracking-widest text-slate-400">{{ $printJob->pdfFile->pages_count ?? 0 }} páginas</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $printJob->copies }}x {{ $printJob->color_type === 'color' ? 'Color' : 'B/N' }}
                                <div class="text-[10px] uppercase tracking-widest text-slate-400">{{ strtoupper($printJob->paper_size) }} · {{ strtoupper($printJob->orientation) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest
                                    {{ $printJob->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($printJob->status === 'printing' ? 'bg-indigo-100 text-indigo-700' : ($printJob->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700')) }}">
                                    {{ $printJob->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-black text-slate-900">${{ number_format($printJob->cost, 2) }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.job-details', $printJob) }}" class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">Ver</a>
                                    <a href="{{ route('admin.download-pdf', $printJob) }}" class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">PDF</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">Todavía no hay trabajos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection