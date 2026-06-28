<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir PDF - Kiosko de Impresiones</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .gradient-bg { background: radial-gradient(circle at top right, #e0e7ff 0%, #f8fafc 50%); }
        @keyframes fade-in-up { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in-up { animation: fade-in-up 0.5s ease-out; }
    </style>
</head>
<body class="gradient-bg min-h-screen flex flex-col antialiased text-slate-900">

    <div class="sticky top-0 z-50">
        <x-step-indicator :current="1" />
    </div>

    <div class="flex-1 flex items-center justify-center px-4 py-10">
        <div class="max-w-md w-full fade-in-up">
            <div class="bg-white rounded-[2.5rem] p-8 shadow-2xl shadow-indigo-100 border border-slate-50 relative overflow-hidden">
                <!-- Decorativo -->
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-indigo-50 rounded-full blur-3xl opacity-60"></div>

                @if ($activeKiosk ?? null)
                    <div class="mb-6 flex items-center justify-center gap-2 bg-emerald-50 border border-emerald-200 rounded-2xl px-4 py-2 relative z-10">
                        <span class="text-base">📍</span>
                        <p class="text-emerald-900 text-[11px] font-bold">Vas a imprimir en: {{ $activeKiosk->nombre_comercial }}</p>
                    </div>
                @endif

                <div class="text-center relative z-10">
                    <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    </div>
                    <h2 class="text-2xl font-black text-slate-800 mb-2 tracking-tight">Sube tu PDF</h2>
                    <p class="text-slate-400 text-sm mb-8 font-medium">Selecciona tu archivo para empezar.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-6 p-3 bg-red-50 text-red-500 rounded-xl text-[10px] font-bold uppercase tracking-widest text-center relative z-10">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form id="uploadForm" action="{{ route('kiosko.upload-pdf') }}" method="POST" enctype="multipart/form-data" class="relative z-10">
                    @csrf

                    <div class="relative border-2 border-dashed border-slate-200 rounded-[1.75rem] p-10 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/30 transition-all duration-300"
                         id="dropZone">
                        <input type="file" name="pdf" id="pdf" accept=".pdf" required
                               class="hidden" onchange="updateFileName(this)">

                        <div id="uploadIcon" class="w-12 h-12 mx-auto mb-4 text-slate-300 transition-all duration-300">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </div>

                        <p class="text-slate-700 mb-1 font-bold text-sm">
                            <span class="text-indigo-600">Haz clic para seleccionar</span> o arrastra un PDF
                        </p>
                        <p class="text-xs text-slate-400 font-medium">
                            Máximo {{ config('printing.max_file_size_mb') }} MB
                        </p>
                        <p class="text-sm text-emerald-600 mt-3 font-bold" id="fileName"></p>
                    </div>
                </form>

                <p class="text-center text-[10px] text-slate-300 font-bold uppercase tracking-widest mt-6 relative z-10">
                    Solo archivos PDF (máximo {{ config('printing.max_file_size_mb') }} MB)
                </p>
            </div>

            <div class="text-center mt-6">
                <a href="{{ route('kiosko.search-form') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-indigo-600 font-bold text-[10px] uppercase tracking-widest transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    ¿Ya subiste un archivo? Busca tu pedido
                </a>
            </div>
        </div>
    </div>

    <footer class="py-6 text-center text-[10px] font-bold text-slate-300 uppercase tracking-widest">
        © {{ date('Y') }} RickTech Impresiones
    </footer>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('pdf');

        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-indigo-400', 'bg-indigo-50/30', 'scale-[1.02]');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('border-indigo-400', 'bg-indigo-50/30', 'scale-[1.02]');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-indigo-400', 'bg-indigo-50/30', 'scale-[1.02]');
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
                document.getElementById('dropZone').classList.add('border-emerald-300', 'bg-emerald-50/40');
                // Continuar automáticamente a configurar la impresión, sin esperar a un botón.
                document.getElementById('uploadForm').submit();
            }
        }
    </script>
</body>
</html>
