<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clientes
        Schema::create('clientes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('telefono', 20)->unique();
            $table->string('cedula', 20)->unique()->nullable();
            $table->string('nombre', 100)->nullable();
            $table->timestamps();
        });

        // 2. Kioskos
        Schema::create('kioskos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre_comercial', 150);
            $table->string('estado', 20)->default('inactivo');
            $table->decimal('precio_blanco_negro', 10, 2);
            $table->decimal('precio_color', 10, 2);
            $table->string('nombre_cups', 100);
            $table->string('color_tema', 7)->default('#000000');
            $table->string('logo_url', 255)->nullable();
            $table->timestamp('ultima_conexion')->nullable();
            $table->timestamps();
        });

        // 3. Ordenes de Impresion
        Schema::create('ordenes_impresion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kiosko_id')->constrained('kioskos')->cascadeOnDelete();
            $table->foreignUuid('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('archivo_url', 255);
            $table->integer('paginas');
            $table->boolean('color')->default(false);
            $table->decimal('costo_total', 10, 2);
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();
        });

        // Habilitar Supabase Realtime (Comando SQL nativo de Postgres)
        // Nota: Esto fallará en MySQL, por eso el .env DEBE ser Postgres
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER PUBLICATION supabase_realtime ADD TABLE ordenes_impresion;');
        }

        // 4. Transacciones Pago
        Schema::create('transacciones_pago', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('orden_id')->constrained('ordenes_impresion')->cascadeOnDelete();
            $table->decimal('monto', 10, 2);
            $table->string('metodo', 50)->default('Deuna');
            $table->string('referencia_externa', 100)->nullable();
            $table->string('estado', 20)->default('completado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER PUBLICATION supabase_realtime DROP TABLE ordenes_impresion;');
        }
        
        Schema::dropIfExists('transacciones_pago');
        Schema::dropIfExists('ordenes_impresion');
        Schema::dropIfExists('kioskos');
        Schema::dropIfExists('clientes');
    }
};
