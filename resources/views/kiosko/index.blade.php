<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosko de Impresiones</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        .gradient-text {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.75rem 0;
        }
        .step-number {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 2rem;
            width: 2rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.875rem;
            flex-shrink: 0;
            color: white;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gradient-to-br from-slate-50 to-indigo-50 dark:from-slate-900 dark:to-indigo-950">
    <nav class="bg-white dark:bg-slate-800 shadow-md p-3 md:p-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">Kiosko de Impresiones</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('kiosko.search-form') }}" class="text-sm px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm font-medium">Buscar trabajo</a>
                <a href="{{ route('login') }}" class="text-sm px-3 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-800 transition-colors shadow-sm font-medium">Administración</a>
            </div>
        </div>
    </nav>

    <div class="min-h-[calc(100vh-72px)] px-4 py-4 md:py-6">
        <div class="max-w-7xl mx-auto animate-fade-in">
            <div class="text-center mb-4 md:mb-6">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-2 md:mb-3">
                    <span class="gradient-text">Impresos al Instante</span>
                </h2>
                <p class="text-gray-600 dark:text-gray-400 text-base md:text-lg">
                    Sube tu PDF, configura la impresión y realiza el pago.
                </p>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-[0.9fr_1.1fr] gap-4 md:gap-6">
                <!-- Columna izquierda: Pasos del proceso -->
                <div class="xl:col-span-1">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-4 md:p-5 h-full">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">Proceso</h3>
                            <span class="text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">4 pasos</span>
                        </div>

                        <div class="grid grid-cols-2 xl:grid-cols-1 gap-3">
                            <div class="step-item rounded-xl bg-slate-50 dark:bg-slate-700/40 px-3 py-3">
                                <div class="step-number bg-gradient-to-br from-indigo-500 to-indigo-600 shadow-md">1</div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm">Sube tu PDF</p>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs">Selecciona el archivo</p>
                                </div>
                            </div>

                            <div class="step-item rounded-xl bg-slate-50 dark:bg-slate-700/40 px-3 py-3">
                                <div class="step-number bg-gradient-to-br from-purple-500 to-purple-600 shadow-md">2</div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm">Configura</p>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs">Copias, color, tamaño</p>
                                </div>
                            </div>

                            <div class="step-item rounded-xl bg-slate-50 dark:bg-slate-700/40 px-3 py-3">
                                <div class="step-number bg-gradient-to-br from-blue-500 to-blue-600 shadow-md">3</div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm">Paga</p>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs">Transfiere al banco</p>
                                </div>
                            </div>

                            <div class="step-item rounded-xl bg-slate-50 dark:bg-slate-700/40 px-3 py-3">
                                <div class="step-number bg-gradient-to-br from-green-500 to-green-600 shadow-md">4</div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white text-sm">¡Listo!</p>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs">Recoge tu impresión</p>
                                </div>
                            </div>
                        </div>

                        <!-- Información de contacto -->
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-700">
                            <p class="text-xs text-gray-600 dark:text-gray-400 text-center">
                                ¿Necesitas ayuda? Contacta al administrador
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: Formulario de subida -->
                <div class="xl:col-span-1">
                    @php
                        $whatsAppQuickMessage = config('evolution.whatsapp_message', 'Hola, quiero imprimir un PDF');
                    @endphp
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-4 md:p-5 h-full flex flex-col">
                        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mb-1">Sube tu PDF</h2>
                        <p class="text-gray-600 dark:text-gray-400 mb-4 text-sm md:text-base">En 4 pasos tendrás tu impresión lista</p>

                        <!-- Tabs para cambiar entre WhatsApp y USB -->
                        <!-- TABS REMOVED - Only showing upload form directly -->

                        @if ($errors->any())
                            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg">
                                <h3 class="font-semibold text-red-900 dark:text-red-100 mb-1 text-sm">Errores encontrados:</h3>
                                <ul class="text-red-800 dark:text-red-200 space-y-1 text-sm">
                                    @foreach ($errors->all() as $error)
                                        <li>• {{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- TAB: WhatsApp - HIDDEN -->
                        <div id="whatsapp" class="tab-content hidden space-y-4 flex-1">
                            <div class="text-center space-y-3">
                                <!-- Botón directo principal -->
                                <div class="bg-gradient-to-br from-green-400 to-emerald-500 dark:from-green-600 dark:to-emerald-600 rounded-2xl p-4 md:p-6 shadow-lg">
                                    <p class="text-white text-xs md:text-sm mb-2 font-semibold">Forma más rápida:</p>
                                    <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', config('evolution.whatsapp_number')) }}?text={{ rawurlencode($whatsAppQuickMessage) }}" target="_blank" class="inline-flex items-center justify-center gap-2 w-full bg-white hover:bg-gray-100 text-green-600 font-bold py-3 px-6 rounded-xl transition-all shadow-md hover:shadow-lg">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.272-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004a9.87 9.87 0 00-5.031 1.378c-3.55 2.357-5.869 6.175-5.869 10.033 0 3.859 2.319 7.676 5.869 10.033 3.55 2.357 8.555 2.357 12.105 0 3.55-2.357 5.869-6.174 5.869-10.033 0-3.859-2.319-7.676-5.869-10.033a9.87 9.87 0 00-5.07-1.378h-.004zm0-2.367c5.432 0 10.534 1.649 14.71 4.7 4.176 3.051 6.862 7.169 6.862 11.535 0 4.365-2.686 8.484-6.862 11.535-4.176 3.051-9.278 4.7-14.71 4.7-5.433 0-10.534-1.649-14.71-4.7C1.266 20.429-1.42 16.31-1.42 11.945c0-4.366 2.686-8.484 6.862-11.535C4.618 1.649 9.72 0 15.051 0z"/>
                                        </svg>
                                        Abrir WhatsApp Ahora
                                    </a>
                                    <p class="text-white text-xs mt-2">Se abrirá con: <span class="font-semibold">{{ $whatsAppQuickMessage }}</span></p>
                                </div>

                                <!-- QR como alternativa -->
                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl p-4 md:p-5 border border-green-200 dark:border-green-800">
                                    <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white mb-3">O escanea el código QR</h3>

                                    <!-- QR Code -->
                                    <div class="flex justify-center mb-3">
                                        <img id="whatsapp-qr" src="{{ route('kiosko.whatsapp-qr') }}" alt="WhatsApp QR Code" class="w-32 h-32 md:w-36 md:h-36 border-4 border-white dark:border-slate-700 rounded-lg shadow-lg">
                                    </div>

                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        Si prefieres, escanea con la cámara de tu celular
                                    </p>
                                </div>

                                <div class="mt-2 p-3 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg">
                                    <p class="text-xs md:text-sm text-green-800 dark:text-green-200">
                                        💡 El bot recibirá tu PDF, lo procesará automáticamente y te enviará un enlace para continuar.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: USB / Computadora -->
                        <div id="usb" class="tab-content active space-y-4 flex-1">
                            <form action="{{ route('kiosko.upload-pdf') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                                @csrf

                                <!-- Zona de carga -->
                                <div>
                                    <label for="pdf" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 uppercase">
                                        Archivo PDF
                                    </label>
                                    <div class="relative border border-gray-300 dark:border-gray-600 border-dashed rounded-lg p-8 text-center cursor-pointer hover:border-indigo-400 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors" 
                                         id="dropZone">
                                        <input type="file" name="pdf" id="pdf" accept=".pdf" required 
                                               class="hidden" onchange="updateFileName(this)">

                                        <svg class="w-10 h-10 mx-auto text-gray-400 dark:text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>

                                        <p class="text-gray-700 dark:text-gray-300 mb-1 font-semibold text-sm md:text-base">
                                            <span class="text-indigo-600 dark:text-indigo-400">Haz clic para seleccionar</span> o arrastra un PDF
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Máximo {{ config('printing.max_file_size_mb') }} MB
                                        </p>
                                        <p class="text-sm text-indigo-600 dark:text-indigo-400 mt-2 font-medium" id="fileName"></p>
                                    </div>
                                </div>

                                <!-- Email (opcional) -->
                                <div>
                                    <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        Email (opcional)
                                    </label>
                                    <input type="email" name="email" id="email" 
                                           placeholder="tu@email.com"
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm md:text-base">
                                    <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Para recibir notificaciones sobre tu trabajo
                                    </p>
                                </div>

                                <!-- Botón -->
                                <button type="submit" class="w-full px-6 py-3 text-base md:text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg hover:shadow-lg transition-all">
                                    Continuar
                                </button>
                            </form>

                            <p class="text-center text-xs md:text-sm text-gray-500 dark:text-gray-400">
                                Solo se aceptan archivos PDF (máximo {{ config('printing.max_file_size_mb') }} MB)
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
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
        }

        function updateFileName(input) {
            const fileName = document.getElementById('fileName');
            if (input.files && input.files[0]) {
                fileName.textContent = '✓ ' + input.files[0].name;
            }
        }
    </script>

    <!-- Footer -->
    <footer class="text-center py-6 text-gray-600 dark:text-gray-400 text-sm">
        <p>&copy; {{ date('Y') }} Kiosko de Impresiones. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
