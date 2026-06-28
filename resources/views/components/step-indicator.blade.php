@php
    $labels = ['Subir', 'Configurar', 'Pagar', 'Listo'];
@endphp
<div class="bg-white/80 backdrop-blur-md border-b border-slate-100">
    <div class="max-w-6xl mx-auto px-6 py-3">
        <div class="flex items-center justify-center gap-3 sm:gap-6">
            @foreach ($labels as $index => $label)
                @php
                    $step = $index + 1;
                    $done = $step < $current;
                    $active = $step === $current;
                @endphp
                <div class="flex items-center gap-2 {{ !$done && !$active ? 'opacity-40' : '' }}">
                    <div class="w-5 h-5 rounded-full flex items-center justify-center text-[8px] font-bold transition-all
                        {{ $done ? 'bg-emerald-500 text-white' : ($active ? 'bg-indigo-600 text-white ring-2 ring-indigo-100 scale-110' : 'border border-slate-200 text-slate-400') }}">
                        @if ($done)
                            ✓
                        @else
                            {{ $step }}
                        @endif
                    </div>
                    <span class="text-[9px] font-bold uppercase tracking-widest {{ $active ? 'text-indigo-600' : ($done ? 'text-slate-500' : 'text-slate-300') }}">
                        {{ $label }}
                    </span>
                </div>
                @if (!$loop->last)
                    <div class="w-6 sm:w-8 h-[1px] {{ $done ? 'bg-emerald-200' : 'bg-slate-100' }}"></div>
                @endif
            @endforeach
        </div>
    </div>
</div>
