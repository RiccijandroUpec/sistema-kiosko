<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de Administración') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Tarjetas de Estadísticas -->
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
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Archivo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Copias</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Costo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse(\App\Models\PrintJob::latest()->take(15)->get() as $job)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">#{{ $job->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="font-semibold text-gray-900">{{ $job->user->name ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-500">{{ $job->user->email ?? 'N/A' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900">{{ Str::limit($job->pdfFile->original_name ?? 'N/A', 30) }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900">{{ $job->copies }}</td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">${{ number_format($job->cost, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($job->status === 'pending')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
                                    @elseif($job->status === 'printing')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">Imprimiendo</span>
                                    @elseif($job->status === 'completed')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Completado</span>
                                    @else
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">{{ ucfirst($job->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $job->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">No hay trabajos aún</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>