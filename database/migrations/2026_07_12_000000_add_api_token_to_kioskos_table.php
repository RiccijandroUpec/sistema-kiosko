<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * El UUID del kiosko se usaba como "token" de la API (X-Kiosk-Token), pero ese
     * mismo UUID queda expuesto en el HTML publico de /configurar y en las rutas
     * publicas de poster/QR (/kioskos/{kiosk}/poster). Este campo separa el
     * identificador publico del secreto real que usa el kiosk-agent para autenticarse.
     */
    public function up(): void
    {
        Schema::table('kioskos', function (Blueprint $table) {
            $table->string('api_token', 64)->nullable()->unique()->after('pin');
        });

        foreach (DB::table('kioskos')->select('id')->get() as $kiosko) {
            DB::table('kioskos')
                ->where('id', $kiosko->id)
                ->update(['api_token' => Str::random(48)]);
        }
    }

    public function down(): void
    {
        Schema::table('kioskos', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }
};
