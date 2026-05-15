@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Kioskos</h1>
            <p class="text-sm text-slate-500">Administra las sedes conectadas al servidor central.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Volver al dashboard</a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[380px_1fr]">
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
            <h2 class="text-lg font-semibold text-slate-900">Nuevo kiosko</h2>
            <form method="POST" action="{{ route('admin.kiosks.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
                    <input type="text" name="nombre" required class="w-full rounded-2xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Kiosko Ibarra Centro">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Ubicación</label>
                    <input type="text" name="ubicacion" class="w-full rounded-2xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Av. Principal y Calle 10">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">API Token</label>
                    <input type="text" name="api_token" class="w-full rounded-2xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Opcional, se genera automáticamente">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">PIN de acceso kiosko <span class="text-slate-400">(solo admin)</span></label>
                    <input type="text" name="access_pin" maxlength="4" pattern="\d{4}" class="w-full rounded-2xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ej: 1234">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Estado de conexión</label>
                    <select name="estado_conexion" class="w-full rounded-2xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="offline">Offline</option>
                        <option value="online">Online</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Crear kiosko</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/60">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">Sedes registradas</h2>
            </div>
            <div class="divide-y divide-slate-200">
                @forelse($kiosks as $kiosk)
                    <div class="p-6">
                        <div class="grid gap-4 lg:grid-cols-[1.2fr_1fr_180px] lg:items-end">
                            <form method="POST" action="{{ route('admin.kiosks.update', $kiosk) }}" class="grid gap-4 lg:grid-cols-[1.2fr_1fr_180px] lg:items-end lg:col-span-3">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
                                    <input type="text" name="nombre" value="{{ $kiosk->nombre }}" class="w-full rounded-2xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Ubicación</label>
                                    <input type="text" name="ubicacion" value="{{ $kiosk->ubicacion }}" class="w-full rounded-2xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Estado</label>
                                    <select name="estado_conexion" class="w-full rounded-2xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="offline" @selected($kiosk->estado_conexion === 'offline')>Offline</option>
                                        <option value="online" @selected($kiosk->estado_conexion === 'online')>Online</option>
                                        <option value="maintenance" @selected($kiosk->estado_conexion === 'maintenance')>Maintenance</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">PIN kiosko <span class="text-slate-400">(solo admin)</span></label>
                                    <input type="text" name="access_pin" value="{{ $kiosk->access_pin }}" maxlength="4" pattern="\d{4}" class="w-full rounded-2xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ej: 1234">
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Guardar</button>
                                </div>
                            </form>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center gap-3 text-xs font-medium text-slate-500">
                            <span>Token: {{ $kiosk->api_token }}</span>
                            <span>Trabajos: {{ $kiosk->print_jobs_count }}</span>
                            <span>Pagos: {{ $kiosk->payments_count }}</span>
                            <span>Última conexión: {{ optional($kiosk->last_seen_at)->format('Y-m-d H:i') ?? 'Nunca' }}</span>
                            <a href="{{ route('admin.kiosks.qr', $kiosk) }}" target="_blank" class="rounded-2xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">QR imprimible</a>
                            <a href="{{ route('admin.kiosks.qr-pdf', $kiosk) }}" class="rounded-2xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Descargar PDF</a>
                            <a href="{{ route('kiosko.whatsapp-qr.kiosk', $kiosk) }}" target="_blank" class="rounded-2xl bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">QR WhatsApp</a>
                            <form method="POST" action="{{ route('admin.kiosks.destroy', $kiosk) }}" onsubmit="return confirm('¿Eliminar este kiosko?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-2xl bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-sm text-slate-500">Todavía no hay kioskos registrados.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection