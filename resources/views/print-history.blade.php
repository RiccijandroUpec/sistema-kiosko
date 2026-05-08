<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Historial de Impresiones') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Mis Trabajos de Impresión</h3>
                </div>
                
                @if($printJobs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Archivo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Copias</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Color</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Tamaño</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Orientación</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Costo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($printJobs as $job)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <a href="{{ route('pdf.show', $job->pdfFile->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                        {{ Str::limit($job->pdfFile->original_name, 35) }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ $job->copies }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded text-sm font-medium {{ $job->color_type === 'color' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($job->color_type === 'color' ? 'Color' : 'B/N') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ ucfirst($job->paper_size) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 capitalize">{{ $job->orientation }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-semibold">${{ number_format($job->cost, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($job->status === 'pending')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
                                    @elseif($job->status === 'printing')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">Imprimiendo</span>
                                    @elseif($job->status === 'completed')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Completado</span>
                                    @elseif($job->status === 'cancelled')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">Cancelado</span>
                                    @else
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">{{ ucfirst($job->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $job->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="{{ route('print-history.show', $job->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Ver detalles</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $printJobs->links() }}
                </div>
                @else
                <div class="px-6 py-12 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-gray-500 text-lg">No hay trabajos de impresión aún</p>
                    <a href="{{ route('pdf.upload') }}" class="text-indigo-600 hover:text-indigo-900 font-medium mt-2 inline-block">
                        Crea tu primer trabajo
                    </a>
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
