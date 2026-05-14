@extends('layouts.app')

@section('content')
<div class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <form method="GET" class="flex gap-4">
                <select name="status" class="border border-gray-300 rounded px-3 py-2 text-gray-700">
                    <option value="all">Todos los estados</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                    <option value="printing" {{ request('status') === 'printing' ? 'selected' : '' }}>Imprimiendo</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completado</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                </select>
                
                <select name="paid" class="border border-gray-300 rounded px-3 py-2 text-gray-700">
                    <option value="">Todos los pagos</option>
                    <option value="yes" {{ request('paid') === 'yes' ? 'selected' : '' }}>Pagado</option>
                    <option value="no" {{ request('paid') === 'no' ? 'selected' : '' }}>No Pagado</option>
                </select>
                
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded transition">
                    Filtrar
                </button>
            </form>
        </div>

        <!-- Tabla de Trabajos -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">Todos los Trabajos de Impresión</h3>
            </div>
            
            <table class="w-full text-xs">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Email</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Archivo</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Copias</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Tipo</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Costo</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Pagado</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Estado</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Fecha</th>
                            <th class="px-2 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($printJobs as $job)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-2 py-2 whitespace-nowrap font-semibold text-gray-900">#{{ $job->id }}</td>
                            <td class="px-2 py-2 whitespace-nowrap text-gray-700">{{ $job->email ?? 'N/A' }}</td>
                            <td class="px-2 py-2 text-gray-900">
                                <p class="max-w-xs truncate">{{ $job->pdfFile->original_name ?? 'N/A' }}</p>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-gray-900">{{ $job->copies }}</td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <span class="px-2 py-1 rounded text-xs font-medium {{ $job->color_type === 'color' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $job->color_type === 'color' ? 'Color' : 'B/N' }}
                                </span>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap font-semibold text-gray-900">${{ number_format($job->cost, 2) }}</td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                @if($job->paid)
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Sí</span>
                                @else
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">No</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">
                                @if($job->status === 'pending')
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
                                @elseif($job->status === 'printing')
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">Imprimiendo</span>
                                @elseif($job->status === 'completed')
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Completado</span>
                                @elseif($job->status === 'cancelled')
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">Cancelado</span>
                                @else
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($job->status) }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap text-gray-600">{{ $job->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-2 py-2 whitespace-nowrap font-medium space-x-1 flex">
                                <!-- Ver detalles -->
                                <a href="{{ route('admin.job-details', $job->id) }}" class="inline-flex items-center px-2 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 transition" title="Ver detalles">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>

                                <!-- Descargar PDF -->
                                <a href="{{ route('admin.download-pdf', $job->id) }}" class="inline-flex items-center px-2 py-1 rounded bg-green-50 text-green-700 hover:bg-green-100 transition" title="Descargar PDF">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </a>

                                <!-- Marcar como impreso (solo si no está completado) -->
                                @if($job->status !== 'completed')
                                    <form method="POST" action="{{ route('admin.mark-printed', $job->id) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-2 py-1 rounded bg-purple-50 text-purple-700 hover:bg-purple-100 transition" title="Marcar como impreso">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17m-2 2l4-4m0 0l-4-4m4 4H3"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                <!-- Cancelar (solo si no está cancelado o completado) -->
                                @if($job->status !== 'cancelled' && $job->status !== 'completed')
                                    <form method="POST" action="{{ route('admin.cancel-job', $job->id) }}" style="display: inline;" onsubmit="return confirm('¿Seguro que desea cancelar este trabajo?');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-2 py-1 rounded bg-orange-50 text-orange-700 hover:bg-orange-100 transition" title="Cancelar trabajo">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                <!-- Eliminar -->
                                <form method="POST" action="{{ route('admin.delete-job', $job->id) }}" style="display: inline;" onsubmit="return confirm('¿Seguro que desea eliminar este trabajo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-2 py-1 rounded bg-red-50 text-red-700 hover:bg-red-100 transition" title="Eliminar trabajo">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-2 py-4 text-center text-gray-500">No hay trabajos disponibles</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            <!-- Pagination -->
            @if($printJobs->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $printJobs->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
