<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir PDF - Kiosko de Impresiones</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-50 to-indigo-50 dark:from-slate-900 dark:to-indigo-950">
    <nav class="bg-white dark:bg-slate-800 shadow-md p-4">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="{{ route('kiosko.index') }}" class="flex items-center gap-2 text-gray-900 dark:text-white hover:text-indigo-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver
            </a>
        </div>
    </nav>

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-4xl w-full">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-8">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Subir PDF</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-8">Elige cómo deseas enviar tu archivo</p>
                
                <!-- Tabs para cambiar entre WhatsApp y USB -->
                <div class="flex gap-4 mb-8 border-b border-gray-200 dark:border-gray-700">
                    <button onclick="switchTab('whatsapp')" class="tab-btn active px-6 py-3 font-semibold text-indigo-600 border-b-2 border-indigo-600" data-tab="whatsapp">
                        📱 WhatsApp (Recomendado)
                    </button>
                    <button onclick="switchTab('usb')" class="tab-btn px-6 py-3 font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white" data-tab="usb">
                        💿 USB / Computadora
                    </button>
                </div>

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg">
                        <h3 class="font-semibold text-red-900 dark:text-red-100 mb-2">Errores encontrados:</h3>
                        <ul class="text-red-800 dark:text-red-200 space-y-1 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- TAB: WhatsApp -->
                <div id="whatsapp" class="tab-content active space-y-6">
                    <div class="text-center">
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl p-8 border border-green-200 dark:border-green-800">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Envía tu PDF por WhatsApp</h3>
                            
                            <!-- QR Code -->
                            <div class="flex justify-center mb-6">
                                <img id="whatsapp-qr" src="{{ route('kiosko.whatsapp-qr') }}" alt="WhatsApp QR Code" class="w-64 h-64 border-4 border-white dark:border-slate-700 rounded-lg shadow-lg">
                            </div>
                            
                            <div class="space-y-4">
                                <p class="text-gray-700 dark:text-gray-300 text-lg">
                                    <strong>Opción 1:</strong> Escanea el código QR con tu celular
                                </p>
                                <p class="text-gray-700 dark:text-gray-300 text-lg">
                                    <strong>Opción 2:</strong> O abre WhatsApp y envía tu PDF a:
                                </p>
                                <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', config('evolution.whatsapp_number')) }}" target="_blank" class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg transition">
                                    {{ config('evolution.whatsapp_number') }}
                                </a>
                            </div>
                            
                            <div class="mt-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg">
                                <p class="text-sm text-green-800 dark:text-green-200">
                                    💡 El bot recibirá tu PDF, lo procesará automáticamente y te enviará un enlace para continuar
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: USB / Computadora -->
                <div id="usb" class="tab-content hidden space-y-6">
                <form action="{{ route('kiosko.upload-pdf') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <!-- Zona de carga -->
                    <div>
                        <label for="pdf" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 uppercase">
                            Archivo PDF
                        </label>
                        <div class="relative border border-gray-300 dark:border-gray-600 border-dashed rounded-lg p-12 text-center cursor-pointer hover:border-indigo-400 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors" 
                             id="dropZone">
                            <input type="file" name="pdf" id="pdf" accept=".pdf" required 
                                   class="hidden" onchange="updateFileName(this)">
                            
                            <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>

                            <p class="text-gray-700 dark:text-gray-300 mb-2 font-semibold text-base">
                                <span class="text-indigo-600 dark:text-indigo-400">Haz clic para seleccionar</span> o arrastra un PDF
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Máximo {{ config('printing.max_file_size_mb') }} MB
                            </p>
                            <p class="text-sm text-indigo-600 dark:text-indigo-400 mt-3 font-medium" id="fileName"></p>
                        </div>
                    </div>

                    <!-- Email (opcional) -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Email (opcional)
                        </label>
                        <input type="email" name="email" id="email" 
                               placeholder="tu@email.com"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Para recibir notificaciones sobre tu trabajo
                        </p>
                    </div>

                    <!-- Botón -->
                    <button type="submit" class="w-full px-6 py-3 text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg hover:shadow-lg transition-all">
                        Continuar
                    </button>
                </form>

                <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
                    Solo se aceptan archivos PDF (máximo {{ config('printing.max_file_size_mb') }} MB)
                </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality - MUST BE GLOBAL
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.remove('hidden');
            
            // Update button states
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-b-2', 'border-indigo-600', 'text-indigo-600');
                btn.classList.add('text-gray-600', 'dark:text-gray-400');
            });
            
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('border-b-2', 'border-indigo-600', 'text-indigo-600');
            document.querySelector(`[data-tab="${tabName}"]`).classList.remove('text-gray-600', 'dark:text-gray-400');
        }

        // Drag and drop functionality for USB tab
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('pdf');

        if (dropZone) {
            dropZone.addEventListener('click', () => fileInput.click());

            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-indigo-400', 'bg-gray-100', 'dark:bg-slate-700/50');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('border-indigo-400', 'bg-gray-100', 'dark:bg-slate-700/50');
            });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-indigo-700', 'bg-indigo-50', 'dark:bg-indigo-900/30');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileName({ files: files });
            }
        });

        function updateFileName(input) {
            const fileName = document.getElementById('fileName');
            if (input.files && input.files[0]) {
                fileName.textContent = '✓ ' + input.files[0].name;
            }
        }
    </script>
</body>
</html>
