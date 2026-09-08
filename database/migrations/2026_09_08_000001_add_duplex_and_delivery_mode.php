<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kioskos', function (Blueprint $table) {
            $table->boolean('admite_duplex')->default(false)->after('nombre_cups');
        });

        Schema::table('ordenes_impresion', function (Blueprint $table) {
            $table->boolean('duplex')->default(false)->after('color');
            $table->string('modo_entrega', 20)->default('inmediato')->after('duplex'); // 'inmediato' o 'pin_retiro'
            $table->string('pin_retiro', 10)->nullable()->after('modo_entrega');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_impresion', function (Blueprint $table) {
            $table->dropColumn(['duplex', 'modo_entrega', 'pin_retiro']);
        });

        Schema::table('kioskos', function (Blueprint $table) {
            $table->dropColumn(['admite_duplex']);
        });
    }
};
