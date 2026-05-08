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
        // Crear tabla de pagos
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('print_job_id');
            $table->string('reference_code')->unique(); // Referencia bancaria
            $table->decimal('amount', 8, 2);
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->string('bank_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('print_job_id')->references('id')->on('print_jobs')->onDelete('cascade');
            $table->index('reference_code');
            $table->index('status');
        });

        // Modificar tabla print_jobs
        Schema::table('print_jobs', function (Blueprint $table) {
            // Agregar nuevas columnas
            $table->string('job_reference')->unique()->after('id'); // Código único para impresión
            $table->string('email')->nullable()->after('job_reference');
            
            // Eliminar foreign key de user_id
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            
            // Agregar columna para indicar si está pagado
            $table->boolean('paid')->default(false)->after('status');
            
            // Agregar índices
            $table->index('job_reference');
            $table->index('paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('print_jobs', function (Blueprint $table) {
            $table->dropIndex(['job_reference']);
            $table->dropIndex(['paid']);
            $table->dropColumn('job_reference');
            $table->dropColumn('email');
            $table->dropColumn('paid');
            $table->unsignedBigInteger('user_id')->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::dropIfExists('payments');
    }
};
