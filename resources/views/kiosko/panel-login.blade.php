<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso Panel Kiosko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold text-slate-900">Panel de Kiosko</h1>
            <p class="text-sm text-slate-500">Selecciona tu sede e ingresa el PIN</p>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('kiosk.panel.login.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Kiosko</label>
                <select name="kiosk_id" required class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Seleccionar kiosko</option>
                    @foreach($kiosks as $kiosk)
                        <option value="{{ $kiosk->id }}" @selected(old('kiosk_id') == $kiosk->id)>
                            {{ $kiosk->nombre }}{{ $kiosk->ubicacion ? ' - ' . $kiosk->ubicacion : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">PIN (4 dígitos)</label>
                <input
                    type="password"
                    name="pin"
                    maxlength="4"
                    inputmode="numeric"
                    pattern="[0-9]{4}"
                    required
                    class="w-full rounded-xl border-slate-200 text-center tracking-[0.4em] text-lg font-semibold focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="••••"
                >
            </div>

            <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                Entrar al panel
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('kiosko.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-800">Volver al inicio</a>
        </div>
    </div>
</body>
</html>
