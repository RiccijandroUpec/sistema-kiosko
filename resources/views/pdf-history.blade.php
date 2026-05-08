<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mis Archivos PDF') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900">Archivos Subidos</h3>
                    <a href="{{ route('pdf.upload') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">+ Subir nuevo</a>
                </div>
                
                @if($pdfFiles->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Páginas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Tamaño</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Trabajos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($pdfFiles as $pdf)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 bg-red-100 rounded flex items-center justify-center">
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <a href="{{ route('pdf.show', $pdf->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            {{ Str::limit($pdf->original_name, 40) }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ $pdf->pages_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ number_format($pdf->file_size, 2) }} MB</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ $pdf->print_jobs_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $pdf->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2 flex">
                                    <a href="{{ route('pdf.show', $pdf->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Ver</a>
                                    <form action="{{ route('pdf-history.destroy', $pdf->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 font-medium" onclick="return confirm('¿Estás seguro?')">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $pdfFiles->links() }}
                </div>
                @else
                <div class="px-6 py-12 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-gray-500 text-lg">No hay archivos aún</p>
                    <a href="{{ route('pdf.upload') }}" class="text-indigo-600 hover:text-indigo-900 font-medium mt-2 inline-block">
                        Sube tu primer archivo
                    </a>
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
