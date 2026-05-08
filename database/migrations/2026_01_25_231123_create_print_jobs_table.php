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
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pdf_file_id');
            $table->integer('copies')->default(1);
            $table->enum('color_type', ['bw', 'color'])->default('bw');
            $table->enum('paper_size', ['a4', 'letter', 'legal'])->default('a4');
            $table->enum('orientation', ['portrait', 'landscape'])->default('portrait');
            $table->decimal('cost', 8, 2);
            $table->enum('status', ['pending', 'printing', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('pdf_file_id')->references('id')->on('pdf_files')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
