@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900">Estadísticas</h1>
            <p class="text-sm text-slate-500">Resumen general del sistema de impresión.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Dashboard</a>
            <a href="{{ route('admin.kiosks.index') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Kioskos</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Trabajos Totales</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ $stats['total_jobs'] ?? 0 }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
            <p class="text-[10px] font-black uppercase tracking-widest text-emerald-500">Completados</p>
            <p class="mt-2 text-3xl font-black text-emerald-600">{{ $stats['completed_jobs'] ?? 0 }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
            <p class="text-[10px] font-black uppercase tracking-widest text-amber-500">Pendientes</p>
            <p class="mt-2 text-3xl font-black text-amber-600">{{ $stats['pending_jobs'] ?? 0 }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
            <p class="text-[10px] font-black uppercase tracking-widest text-rose-500">Cancelados</p>
            <p class="mt-2 text-3xl font-black text-rose-600">{{ $stats['cancelled_jobs'] ?? 0 }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200/60">
            <p class="text-[10px] font-black uppercase tracking-widest text-indigo-500">Ingresos</p>
            <p class="mt-2 text-3xl font-black text-indigo-600">${{ number_format($stats['total_revenue'] ?? 0, 2) }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
            <h2 class="text-lg font-black text-slate-900">Vista rápida</h2>
            <div class="mt-6 space-y-4">
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                    <span class="text-sm font-semibold text-slate-600">Total kioskos</span>
                    <span class="text-sm font-black text-slate-900">{{ $stats['total_kiosks'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                    <span class="text-sm font-semibold text-slate-600">Kioskos online</span>
                    <span class="text-sm font-black text-emerald-600">{{ $stats['online_kiosks'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                    <span class="text-sm font-semibold text-slate-600">En mantenimiento</span>
                    <span class="text-sm font-black text-amber-600">{{ $stats['maintenance_kiosks'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                    <span class="text-sm font-semibold text-slate-600">Kioskos offline</span>
                    <span class="text-sm font-black text-rose-600">{{ $stats['offline_kiosks'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
            <h2 class="text-lg font-black text-slate-900">Acciones rápidas</h2>
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <a href="{{ route('admin.print-jobs') }}" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800 text-center">Ver trabajos</a>
                <a href="{{ route('admin.transactions') }}" class="rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700 text-center">Ver transacciones</a>
                <a href="{{ route('admin.kiosks.index') }}" class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-700 text-center">Administrar kioskos</a>
                <a href="{{ route('central.preview') }}" class="rounded-2xl bg-amber-500 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-600 text-center">Ver torre</a>
            </div>
        </div>
    </div>
</div>
@endsection
