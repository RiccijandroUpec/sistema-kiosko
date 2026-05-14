@extends('layouts.app')

@section('content')
<div class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <p class="text-sm text-gray-600">Total trabajos cargados: {{ $jobs->count() }}</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total de Usuarios -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Usuarios Totales</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">{{ \App\Models\User::count() }}</p>
                        </div>
                        <div class="bg-indigo-100 p-3 rounded-lg">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM16 11h4"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total de PDFs -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Archivos PDF</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">{{ \App\Models\PdfFile::count() }}</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-lg">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total de Trabajos -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Trabajos Totales</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">{{ \App\Models\PrintJob::count() }}</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Ingresos Totales -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Ingresos Totales</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">${{ number_format(\App\Models\PrintJob::where('status', 'completed')->sum('cost'), 2) }}</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trabajos Recientes -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Trabajos Recientes</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Usuario</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Archivo</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Copias</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Costo</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Estado</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Fecha</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($jobs as $job)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-2 py-2 whitespace-nowrap text-xs font-semibold text-gray-900">#{{ $job->id }}</td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">{{ $job->email }}</td>
                                <td class="px-2 py-2 text-xs text-gray-900 max-w-xs truncate">{{ $job->pdfFile->original_name ?? 'N/A' }}</td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">{{ $job->copies }}</td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs font-semibold text-gray-900">${{ number_format($job->cost, 2) }}</td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs">
                                    @if($job->status === 'pending')
                                        <span class="px-1.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
                                    @elseif($job->status === 'printing')
                                        <span class="px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Imprimiendo</span>
                                    @elseif($job->status === 'completed')
                                        <span class="px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Completado</span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ ucfirst($job->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-600">{{ $job->created_at->format('d/m H:i') }}</td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs font-medium space-x-1 flex items-center">
                                    <!-- Ver detalles -->
                                    <a href="{{ route('admin.job-details', $job->id) }}" class="inline-flex items-center p-1 rounded bg-blue-50 text-blue-600 hover:bg-blue-100 transition" title="Ver detalles">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>

                                    <!-- Descargar PDF -->
                                    <a href="{{ route('admin.download-pdf', $job->id) }}" class="inline-flex items-center p-1 rounded bg-green-50 text-green-600 hover:bg-green-100 transition" title="Descargar">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                    </a>

                                    <!-- Marcar como impreso (solo si no está completado) -->
                                    @if($job->status !== 'completed')
                                        <form method="POST" action="{{ route('admin.mark-printed', $job->id) }}" style="display: inline;" title="Marcar como impreso">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center p-1 rounded bg-purple-50 text-purple-600 hover:bg-purple-100 transition">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Cancelar (solo si no está cancelado o completado) -->
                                    @if($job->status !== 'cancelled' && $job->status !== 'completed')
                                        <form method="POST" action="{{ route('admin.cancel-job', $job->id) }}" style="display: inline;" onsubmit="return confirm('¿Cancelar?');" title="Cancelar">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center p-1 rounded bg-orange-50 text-orange-600 hover:bg-orange-100 transition">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Eliminar -->
                                    <form method="POST" action="{{ route('admin.delete-job', $job->id) }}" style="display: inline;" onsubmit="return confirm('¿Eliminar?');" title="Eliminar">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center p-1 rounded bg-red-50 text-red-600 hover:bg-red-100 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-2 py-4 text-center text-gray-500">No hay trabajos aún</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection