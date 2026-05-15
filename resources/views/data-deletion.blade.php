@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Eliminación de Datos de Usuario</h1>
        <p class="text-gray-600 mb-8">Si deseas que eliminemos tus datos de nuestro sistema, sigue las instrucciones a continuación.</p>
        
        <div class="space-y-6 text-gray-700">
            <section>
                <h2 class="text-xl font-semibold text-gray-900">¿Qué datos eliminamos?</h2>
                <p>Eliminaremos tu número de teléfono, historial de mensajes con el bot y cualquier archivo PDF que hayas subido y que no haya sido impreso aún.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900">Pasos para solicitar la eliminación</h2>
                <ol class="list-decimal ml-6 space-y-2">
                    <li>Envía un mensaje por WhatsApp al bot con la palabra <strong>"ELIMINAR"</strong>.</li>
                    <li>O envía un correo a <strong>carchicinema0012@gmail.com</strong> solicitando la baja de tu número.</li>
                </ol>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900">Tiempo de respuesta</h2>
                <p>Una vez recibida la solicitud, tus datos serán eliminados en un plazo máximo de 24 horas.</p>
            </section>
        </div>
    </div>
</div>
@endsection
