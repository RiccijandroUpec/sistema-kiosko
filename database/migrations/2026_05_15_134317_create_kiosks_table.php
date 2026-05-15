<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kiosks', function (Blueprint $table) {
            if (!Schema::hasColumn('kiosks', 'nombre')) {
                $table->string('nombre')->nullable()->after('id');
            }

            if (!Schema::hasColumn('kiosks', 'ubicacion')) {
                $table->string('ubicacion')->nullable()->after('nombre');
            }

            if (!Schema::hasColumn('kiosks', 'api_token')) {
                $table->string('api_token', 80)->nullable()->after('ubicacion');
            }

            if (!Schema::hasColumn('kiosks', 'estado_conexion')) {
                $table->enum('estado_conexion', ['offline', 'online', 'maintenance'])
                    ->default('offline')
                    ->after('api_token');
            }

            if (!Schema::hasColumn('kiosks', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('estado_conexion');
            }

            $table->index('estado_conexion');
        });
    }

    public function down(): void
    {
        Schema::table('kiosks', function (Blueprint $table) {
            if (Schema::hasColumn('kiosks', 'estado_conexion')) {
                $table->dropIndex(['estado_conexion']);
            }

            foreach (['last_seen_at', 'estado_conexion', 'api_token', 'ubicacion', 'nombre'] as $column) {
                if (Schema::hasColumn('kiosks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};