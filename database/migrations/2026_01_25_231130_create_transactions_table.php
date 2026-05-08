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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('print_job_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 8, 2);
            $table->enum('type', ['print_job', 'refund', 'credit', 'other'])->default('print_job');
            $table->string('description')->nullable();
            $table->enum('payment_method', ['cash', 'card', 'digital_wallet', 'other'])->default('cash');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('print_job_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
