@extends('layouts.app')

@section('content')
<div class="py-10 bg-gray-50 min-h-screen">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Detalle del Trabajo #{{ $printJob->id }}</h1>
                <p class="text-sm text-gray-600 mt-1">Referencia: <span class="font-semibold">{{ $printJob->job_reference }}</span></p>
            </div>
            <a href="{{ route('admin.print-jobs') }}" class="inline-flex items-center px-4 py-2 rounded bg-gray-200 text-gray-800 hover:bg-gray-300 transition">
                Volver
            </a>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Trabajo de impresión</h2>

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Estado</p>
                        <p class="font-semibold text-gray-900">{{ ucfirst($printJob->status) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Pagado</p>
                        <p class="font-semibold {{ $printJob->paid ? 'text-green-700' : 'text-red-700' }}">{{ $printJob->paid ? 'Sí' : 'No' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Copias</p>
                        <p class="font-semibold text-gray-900">{{ $printJob->copies }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Costo</p>
                        <p class="font-semibold text-gray-900">${{ number_format((float) $printJob->cost, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Color</p>
                        <p class="font-semibold text-gray-900">{{ $printJob->color_type === 'color' ? 'Color' : 'B/N' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Tamaño</p>
                        <p class="font-semibold text-gray-900">{{ strtoupper($printJob->paper_size) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Orientación</p>
                        <p class="font-semibold text-gray-900">{{ ucfirst($printJob->orientation) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Fecha</p>
                        <p class="font-semibold text-gray-900">{{ $printJob->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Archivo PDF</h2>

                <div class="text-sm space-y-3">
                    <div>
                        <p class="text-gray-500">Nombre</p>
                        <p class="font-semibold text-gray-900 break-all">{{ optional($printJob->pdfFile)->original_name ?? 'N/A' }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-500">Páginas</p>
                            <p class="font-semibold text-gray-900">{{ optional($printJob->pdfFile)->pages_count ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Tamaño</p>
                            <p class="font-semibold text-gray-900">{{ optional($printJob->pdfFile)->file_size ? number_format((float) $printJob->pdfFile->file_size, 2) . ' KB' : 'N/A' }}</p>
                        </div>
                    </div>
                    @if($printJob->pdfFile)
                        <a href="{{ route('admin.download-pdf', $printJob->id) }}" class="inline-flex items-center px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700 transition">
                            Descargar PDF
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Pago</h2>

            @if($printJob->payment)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Referencia</p>
                        <p class="font-semibold text-gray-900">{{ $printJob->payment->reference_code }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Monto</p>
                        <p class="font-semibold text-gray-900">${{ number_format((float) $printJob->payment->amount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Estado</p>
                        <p class="font-semibold text-gray-900">{{ ucfirst($printJob->payment->status) }}</p>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-600">No hay registro de pago para este trabajo.</p>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Acciones</h2>
            <div class="flex flex-wrap gap-3">
                @if($printJob->status !== 'completed')
                    <form method="POST" action="{{ route('admin.mark-printed', $printJob->id) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition">Marcar como impreso</button>
                    </form>
                @endif

                @if($printJob->status !== 'cancelled' && $printJob->status !== 'completed')
                    <form method="POST" action="{{ route('admin.cancel-job', $printJob->id) }}" onsubmit="return confirm('¿Cancelar este trabajo?');">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded bg-orange-600 text-white hover:bg-orange-700 transition">Cancelar trabajo</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.delete-job', $printJob->id) }}" onsubmit="return confirm('¿Eliminar definitivamente este trabajo?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 transition">Eliminar trabajo</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection