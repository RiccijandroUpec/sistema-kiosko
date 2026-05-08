<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Trabajos') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow mb-6 p-4">
                <form method="GET" class="flex gap-4">
                    <select name="status" class="border border-gray-300 rounded px-3 py-2">
                        <option value="">Todos los estados</option>
                        <option value="pending">Pendiente</option>
                        <option value="printing">Imprimiendo</option>
                        <option value="completed">Completado</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded">
                        Filtrar
                    </button>
                </form>
            </div>

            <!-- Tabla de Trabajos -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Todos los Trabajos</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Archivo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Copias</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Color</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Costo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($jobs as $job)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">#{{ $job->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="font-semibold text-gray-900">{{ $job->user->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $job->user->email }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900">{{ Str::limit($job->pdfFile->original_name ?? 'N/A', 30) }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ $job->copies }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded text-sm font-medium {{ $job->color_type === 'color' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($job->color_type === 'color' ? 'Color' : 'B/N') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">${{ number_format($job->cost, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($job->status === 'pending')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
                                    @elseif($job->status === 'printing')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">Imprimiendo</span>
                                    @elseif($job->status === 'completed')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Completado</span>
                                    @elseif($job->status === 'cancelled')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">Cancelado</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $job->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                    @if($job->status === 'pending')
                                        <form action="{{ route('admin.job-approve', $job->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-900 font-medium">Aprobar</button>
                                        </form>
                                        <form action="{{ route('admin.job-cancel', $job->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="text-red-600 hover:text-red-900 font-medium" onclick="return confirm('¿Cancelar trabajo?')">Cancelar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">No hay trabajos disponibles</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $jobs->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
