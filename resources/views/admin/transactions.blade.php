<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transacciones') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Resumen de Transacciones -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                    <p class="text-gray-500 text-sm font-medium">Total de Ingresos</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">${{ number_format(\App\Models\Transaction::where('status', 'completed')->sum('amount'), 2) }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                    <p class="text-gray-500 text-sm font-medium">Transacciones Completadas</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ \App\Models\Transaction::where('status', 'completed')->count() }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                    <p class="text-gray-500 text-sm font-medium">Reembolsos</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">${{ number_format(\App\Models\Transaction::where('type', 'refund')->sum('amount'), 2) }}</p>
                </div>
            </div>

            <!-- Tabla de Transacciones -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Todas las Transacciones</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Trabajo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Monto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Método</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($transactions as $transaction)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">#{{ $transaction->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="font-semibold text-gray-900">{{ $transaction->user->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $transaction->user->email }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    @if($transaction->printJob)
                                        <p class="text-sm text-gray-900">{{ Str::limit($transaction->printJob->pdfFile->original_name ?? 'N/A', 30) }}</p>
                                    @else
                                        <p class="text-sm text-gray-500">-</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded text-sm font-medium {{ $transaction->type === 'print_job' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">${{ number_format($transaction->amount, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-900 capitalize">{{ $transaction->payment_method }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($transaction->status === 'completed')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Completado</span>
                                    @elseif($transaction->status === 'pending')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
                                    @elseif($transaction->status === 'failed')
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">Falló</span>
                                    @else
                                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">{{ ucfirst($transaction->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">No hay transacciones disponibles</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $transactions->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
