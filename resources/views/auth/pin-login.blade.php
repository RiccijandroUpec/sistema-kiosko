<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso por PIN - RickTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .pin-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        .pin-dot.filled {
            background-color: #4f46e5;
            border-color: #4f46e5;
            transform: scale(1.2);
        }
        .numpad-btn {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
            border-radius: 50%;
            background: white;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            transition: all 0.1s ease;
            user-select: none;
        }
        .numpad-btn:active {
            transform: scale(0.9);
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div x-data="{ 
        pin: '', 
        error: '{{ $errors->first('pin') }}',
        addNumber(n) {
            if (this.pin.length < 4) {
                this.pin += n;
                if (this.pin.length === 4) {
                    this.$nextTick(() => {
                        this.$refs.pinForm.submit();
                    });
                }
            }
        },
        deleteNumber() {
            this.pin = this.pin.slice(0, -1);
        }
    }" 
    @keydown.window="
        if ($event.key >= 0 && $event.key <= 9) addNumber($event.key);
        if ($event.key === 'Backspace') deleteNumber();
    "
    class="w-full max-w-md">
        
        <div class="bg-white rounded-3xl shadow-2xl p-8 text-center border border-slate-100">
            <div class="mb-8">
                <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-indigo-200">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-800">Panel Admin</h1>
                <p class="text-slate-500">Ingresa tu código de acceso</p>
            </div>

            <!-- PIN Display -->
            <div class="flex justify-center gap-6 mb-10">
                <template x-for="i in 4">
                    <div class="pin-dot" :class="pin.length >= i ? 'filled' : ''"></div>
                </template>
            </div>

            <p x-show="error" x-text="error" class="text-red-500 text-sm mb-4 font-medium animate-bounce"></p>

            <!-- Numpad -->
            <div class="grid grid-cols-3 gap-y-6 justify-items-center">
                <template x-for="n in [1, 2, 3, 4, 5, 6, 7, 8, 9]">
                    <button type="button" @click="addNumber(n)" class="numpad-btn text-slate-700" x-text="n"></button>
                </template>
                
                <div class="w-16"></div>
                <button type="button" @click="addNumber(0)" class="numpad-btn text-slate-700">0</button>
                <button type="button" @click="deleteNumber()" class="numpad-btn bg-slate-100 shadow-none text-slate-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"></path>
                    </svg>
                </button>
            </div>

            <form x-ref="pinForm" action="{{ route('login.pin') }}" method="POST" class="hidden">
                @csrf
                <input type="hidden" name="pin" x-model="pin">
            </form>

            <div class="mt-10 border-t pt-6">
                <a href="{{ route('login') }}" class="text-indigo-600 font-semibold hover:text-indigo-700 transition">
                    ← Volver al login tradicional
                </a>
            </div>
        </div>

        <p class="text-center text-slate-400 text-sm mt-8">
            &copy; {{ date('Y') }} RickTech Kiosko
        </p>
    </div>
</body>
</html>
