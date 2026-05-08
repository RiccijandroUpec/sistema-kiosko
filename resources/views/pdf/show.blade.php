@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Header Section -->
            <div class="mb-8 px-4 sm:px-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Configurar Impresión</h1>
                    <p class="text-gray-500 mt-2 text-lg">Personaliza cómo quieres tu documento.</p>
                </div>
                <a href="{{ route('dashboard') }}"
                    class="group inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                    <div
                        class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mr-2 group-hover:bg-indigo-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </div>
                    Volver al Panel
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 px-4 sm:px-0">

                <!-- Left Column: Preview (8 cols) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-50 flex items-center justify-between bg-white">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-red-50 rounded-lg">
                                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900">{{ $pdfFile->original_name }}</h2>
                                    <p class="text-sm text-gray-400">{{ $pdfFile->filename }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    {{ $pdfFile->pages_count }} págs
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4">
                                        </path>
                                    </svg>
                                    {{ number_format($pdfFile->file_size, 2) }} MB
                                </span>
                            </div>
                        </div>

                        <div class="bg-gray-100 p-8 flex justify-center items-center min-h-[600px]">
                            <iframe src="{{ asset('storage/' . $pdfFile->file_path) }}"
                                class="w-full h-[70vh] rounded-xl shadow-lg border border-gray-200 bg-white"></iframe>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Settings (4 cols) -->
                <div class="lg:col-span-4">
                    <div class="bg-white rounded-3xl shadow-xl border border-indigo-50 p-6 lg:p-8 sticky top-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <span class="w-1 h-6 bg-indigo-500 rounded-full mr-3"></span>
                            Opciones de Impresión
                        </h3>

                        <form method="POST" action="{{ route('pdf.print', $pdfFile->id) }}" class="space-y-6">
                            @csrf

                            <!-- Copias -->
                            <div>
                                <label for="copies" class="block text-sm font-medium text-gray-700 mb-2">Número de
                                    Copias</label>
                                <div class="relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <input type="number" name="copies" id="copies" value="1" min="1"
                                        class="block w-full pl-10 pr-3 py-3 border-gray-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm text-gray-900"
                                        placeholder="1">
                                </div>
                            </div>

                            <!-- Color -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Color</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="color_type" value="bw" class="peer sr-only">
                                        <div
                                            class="p-3 text-center rounded-xl border-2 border-gray-200 bg-white hover:border-gray-300 peer-checked:border-gray-900 peer-checked:bg-gray-900 peer-checked:text-white transition-all">
                                            <span class="block font-medium">B/N</span>
                                            <span class="text-xs opacity-70">Económico</span>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="color_type" value="color" class="peer sr-only" checked>
                                        <div
                                            class="p-3 text-center rounded-xl border-2 border-gray-200 bg-white hover:border-gray-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white transition-all">
                                            <span class="block font-medium">Color</span>
                                            <span class="text-xs opacity-70">Alta Calidad</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Tamaño -->
                            <div>
                                <label for="paper_size" class="block text-sm font-medium text-gray-700 mb-2">Tamaño de
                                    Papel</label>
                                <select id="paper_size" name="paper_size"
                                    class="block w-full py-3 pl-3 pr-10 text-base border-gray-200 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-xl">
                                    <option value="a4">A4 (Estándar)</option>
                                    <option value="letter">Carta / Letter</option>
                                    <option value="legal">Oficio / Legal</option>
                                </select>
                            </div>

                            <!-- Orientación -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Orientación</label>
                                <div class="flex gap-4">
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="orientation" value="portrait" class="peer sr-only"
                                            checked>
                                        <div
                                            class="flex flex-col items-center p-3 rounded-xl border-2 border-gray-200 hover:border-gray-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all">
                                            <svg class="w-6 h-8 border-2 border-current rounded-sm mb-2 text-gray-400 peer-checked:text-indigo-600"
                                                viewBox="0 0 24 32"></svg>
                                            <span
                                                class="text-sm font-medium text-gray-900 peer-checked:text-indigo-700">Vertical</span>
                                        </div>
                                    </label>
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="orientation" value="landscape" class="peer sr-only">
                                        <div
                                            class="flex flex-col items-center p-3 rounded-xl border-2 border-gray-200 hover:border-gray-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition-all">
                                            <svg class="w-8 h-6 border-2 border-current rounded-sm mb-2 text-gray-400 peer-checked:text-indigo-600"
                                                viewBox="0 0 32 24"></svg>
                                            <span
                                                class="text-sm font-medium text-gray-900 peer-checked:text-indigo-700">Horizontal</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-100 mt-6">
                                <button type="submit"
                                    class="w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-lg text-base font-bold text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 transform hover:scale-[1.02] transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Confirmar e Imprimir
                                </button>
                                <p class="text-center text-xs text-gray-400 mt-3 flex items-center justify-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                        </path>
                                    </svg>
                                    Pago seguro al confirmar
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection