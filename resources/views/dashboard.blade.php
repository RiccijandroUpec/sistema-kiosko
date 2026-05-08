<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Kiosko de Impresión') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Saludo -->
            <div class="mb-8 px-4 sm:px-0 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Hola, {{ Auth::user()->name }} 👋</h1>
                    <p class="text-gray-600 mt-2">¿Qué deseas hacer hoy?</p>
                </div>
                @if(Auth::user()->role === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    Panel Admin
                </a>
                @endif
            </div>

            <!-- Grid de Tarjetas de Acción -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 px-4 sm:px-0">
                
                <!-- Tarjeta: Nueva Impresión -->
                <a href="{{ route('pdf.upload') }}" class="group relative overflow-hidden bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 hover:-translate-y-1">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-gradient-to-br from-blue-400 to-indigo-600 rounded-full opacity-20 blur-xl group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="p-8">
                        <div class="w-14 h-14 bg-blue-50 rounded-xl flex items-center justify-center mb-6 group-hover:bg-blue-100 transition-colors">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Nueva Impresión</h3>
                        <p class="text-gray-500 text-sm">Sube tu archivo PDF y configura tus opciones de impresión en segundos.</p>
                        <div class="mt-6 flex items-center text-blue-600 font-medium group-hover:translate-x-1 transition-transform">
                            <span>Comenzar</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </div>
                </a>

                <!-- Tarjeta: Mis Impresiones -->
                <a href="{{ route('print-history') }}" class="group relative overflow-hidden bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 hover:-translate-y-1">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-gradient-to-br from-emerald-400 to-green-600 rounded-full opacity-20 blur-xl group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="p-8">
                        <div class="w-14 h-14 bg-emerald-50 rounded-xl flex items-center justify-center mb-6 group-hover:bg-emerald-100 transition-colors">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Mis Impresiones</h3>
                        <p class="text-gray-500 text-sm">Revisa el estado de tus documentos y tu historial de pagos.</p>
                        <div class="mt-6 flex items-center text-emerald-600 font-medium group-hover:translate-x-1 transition-transform">
                            <span>Ver historial</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </div>
                </a>

                <!-- Tarjeta: Mis Archivos -->
                <a href="{{ route('pdf-history') }}" class="group relative overflow-hidden bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 hover:-translate-y-1">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-gradient-to-br from-orange-400 to-red-600 rounded-full opacity-20 blur-xl group-hover:scale-150 transition-transform duration-500"></div>
                    <div class="p-8">
                        <div class="w-14 h-14 bg-orange-50 rounded-xl flex items-center justify-center mb-6 group-hover:bg-orange-100 transition-colors">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Mis Archivos</h3>
                        <p class="text-gray-500 text-sm">Accede a todos tus archivos PDF subidos al sistema.</p>
                        <div class="mt-6 flex items-center text-orange-600 font-medium group-hover:translate-x-1 transition-transform">
                            <span>Ver archivos</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </div>
                </a>
                
            </div>

            <!-- Sección Informativa / Placeholder -->
            <div class="mt-12 px-4 sm:px-0">
                <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-2xl shadow-lg p-8 text-white relative overflow-hidden">
                    <div class="absolute right-0 top-0 h-full w-1/2 bg-white opacity-5 transform skew-x-12 -mr-12"></div>
                    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">¿Necesitas ayuda?</h3>
                            <p class="text-gray-300 max-w-lg">Si tienes problemas con tu archivo o el pago, acércate al mostrador y un operador te ayudará.</p>
                        </div>
                        <div class="mt-6 md:mt-0">
                            <span class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-gray-900 bg-white hover:bg-gray-100 transition-colors cursor-default">
                                Contactar soporte
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
