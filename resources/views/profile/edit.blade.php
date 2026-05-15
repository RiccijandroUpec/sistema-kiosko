<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            
            <!-- Header with Back Button -->
            <div class="mb-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Mi Perfil</h1>
                    <p class="mt-2 text-slate-500">Gestiona tu información personal y configuraciones de seguridad.</p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 text-slate-600 rounded-xl font-semibold hover:bg-slate-50 hover:text-indigo-600 transition-all shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Volver al Panel
                </a>
            </div>

            <div class="space-y-8">
                <!-- Section: Information & PIN -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <h2 class="text-xl font-bold text-slate-800">Información General</h2>
                        </div>
                        
                        <div class="max-w-xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>
                </div>

                <!-- Section: Security / Password -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                            <h2 class="text-xl font-bold text-slate-800">Seguridad</h2>
                        </div>

                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                </div>

                <!-- Section: Advanced (Danger Zone) -->
                <div class="bg-red-50 rounded-3xl border border-red-100 overflow-hidden">
                    <div class="p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-red-100 text-red-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </div>
                            <h2 class="text-xl font-bold text-red-800">Zona Peligrosa</h2>
                        </div>

                        <div class="max-w-xl text-red-600">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-12 text-center text-slate-400 text-sm">
                &copy; {{ date('Y') }} RickTech - Sistema de Kiosko
            </p>
        </div>
    </div>
</x-app-layout>
