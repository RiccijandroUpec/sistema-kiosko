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
        Schema::table('ordenes_impresion', function (Blueprint $table) {
            $table->integer('copias')->default(1)->after('paginas');
            $table->string('rango_paginas', 50)->nullable()->after('copias');
            $table->string('papel', 20)->default('a4')->after('rango_paginas');
            $table->string('orientacion', 20)->default('portrait')->after('papel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_impresion', function (Blueprint $table) {
            $table->dropColumn(['copias', 'rango_paginas', 'papel', 'orientacion']);
        });
    }
};
