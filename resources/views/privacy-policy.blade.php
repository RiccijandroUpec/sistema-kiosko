@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Política de Privacidad - RickTech</h1>
        <p class="text-gray-600 mb-4">Última actualización: {{ date('d/m/Y') }}</p>
        
        <div class="prose prose-indigo max-w-none text-gray-700 space-y-4">
            <p>En RickTech, nos tomamos muy en serio la privacidad de tus datos. Esta política describe cómo manejamos la información cuando utilizas nuestro servicio de kiosko de impresiones a través de WhatsApp.</p>
            
            <h2 class="text-xl font-semibold text-gray-900 mt-6">1. Información que recolectamos</h2>
            <p>Recolectamos los archivos PDF que envías para ser impresos y tu número de teléfono para enviarte el código de referencia y el costo del servicio.</p>
            
            <h2 class="text-xl font-semibold text-gray-900 mt-6">2. Uso de la información</h2>
            <p>Tus archivos se utilizan únicamente para el proceso de impresión. Los archivos se eliminan automáticamente del sistema periódicamente una vez finalizado el trabajo.</p>
            
            <h2 class="text-xl font-semibold text-gray-900 mt-6">3. Seguridad</h2>
            <p>Implementamos medidas de seguridad para proteger tu información contra acceso no autorizado.</p>
            
            <h2 class="text-xl font-semibold text-gray-900 mt-6">4. Contacto</h2>
            <p>Si tienes dudas sobre esta política, puedes contactarnos a través de nuestra plataforma.</p>
        </div>
    </div>
</div>
@endsection
