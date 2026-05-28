<x-guest-layout>
    <div x-data="{ tab: 'pin' }" class="w-full">
        <!-- Tabs Header -->
        <div class="flex mb-8 bg-slate-100 p-1 rounded-xl">
            <button @click="tab = 'pin'" 
                    :class="tab === 'pin' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500'"
                    class="flex-1 py-2 text-xs font-bold rounded-lg transition-all duration-200 uppercase tracking-wider">
                PIN de Kiosko
            </button>
            <button @click="tab = 'traditional'" 
                    :class="tab === 'traditional' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500'"
                    class="flex-1 py-2 text-xs font-bold rounded-lg transition-all duration-200 uppercase tracking-wider">
                Administrador
            </button>
        </div>

        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- PIN Login Section (Kioskos) -->
        <div x-show="tab === 'pin'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95">
            <div x-data="{ 
                pin: '', 
                addNumber(n) {
                    if (this.pin.length < 4) {
                        this.pin += n;
                        if (this.pin.length === 4) {
                            this.$nextTick(() => { this.$refs.pinForm.submit(); });
                        }
                    }
                },
                deleteNumber() { this.pin = this.pin.slice(0, -1); }
            }" 
            @keydown.window="if(tab === 'pin') { if ($event.key >= 0 && $event.key <= 9) addNumber($event.key); if ($event.key === 'Backspace') deleteNumber(); }"
            class="text-center space-y-6">
                
                <div class="text-center">
                    <h2 class="text-sm font-bold text-slate-700">Ingreso Operador de Sede</h2>
                    <p class="text-[10px] text-slate-400 mt-1">Ingresa el PIN de 4 dígitos asignado a tu sede</p>
                </div>

                <!-- PIN Dots -->
                <div class="flex justify-center gap-4 py-2">
                    <template x-for="i in 4">
                        <div class="w-3.5 h-3.5 rounded-full border-2 border-slate-200 transition-all duration-200"
                             :class="pin.length >= i ? 'bg-indigo-600 border-indigo-600 scale-125' : ''"></div>
                    </template>
                </div>

                <!-- Numpad Grid -->
                <div class="grid grid-cols-3 gap-4 justify-items-center max-w-[240px] mx-auto">
                    <template x-for="n in [1, 2, 3, 4, 5, 6, 7, 8, 9]">
                        <button type="button" @click="addNumber(n)" 
                                class="w-12 h-12 flex items-center justify-center text-lg font-bold rounded-full bg-white shadow-sm border border-slate-100 active:scale-90 active:bg-slate-50 transition-all text-slate-700" 
                                x-text="n"></button>
                    </template>
                    <div class="w-12"></div>
                    <button type="button" @click="addNumber(0)" class="w-12 h-12 flex items-center justify-center text-lg font-bold rounded-full bg-white shadow-sm border border-slate-100 active:scale-90 active:bg-slate-50 transition-all text-slate-700">0</button>
                    <button type="button" @click="deleteNumber()" class="w-12 h-12 flex items-center justify-center rounded-full bg-slate-50 text-slate-400 active:scale-90 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"></path></svg>
                    </button>
                </div>

                <!-- PIN Form (Posts to Kiosk Auth controller) -->
                <form x-ref="pinForm" action="{{ route('kiosk.panel.login.submit') }}" method="POST" class="hidden">
                    @csrf
                    <input type="hidden" name="pin" x-model="pin">
                </form>
            </div>
        </div>

        <!-- Traditional Login Section (Admin) -->
        <div x-show="tab === 'traditional'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95">
            <div class="mb-6 text-center">
                <h2 class="text-sm font-bold text-slate-700">Ingreso Administrador</h2>
                <p class="text-[10px] text-slate-400">Ingresa tus credenciales de acceso total</p>
            </div>
            
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1 w-full text-xs" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="block mt-1 w-full text-xs" type="password" name="password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between mt-4">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                        <span class="ms-2 text-xs text-gray-600">{{ __('Remember me') }}</span>
                    </label>

                    <x-primary-button class="text-xs">
                        {{ __('Log in') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
