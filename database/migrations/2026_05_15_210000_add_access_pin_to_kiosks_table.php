<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kiosks', function (Blueprint $table) {
            if (!Schema::hasColumn('kiosks', 'access_pin')) {
                $table->string('access_pin', 4)->nullable()->after('api_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kiosks', function (Blueprint $table) {
            if (Schema::hasColumn('kiosks', 'access_pin')) {
                $table->dropColumn('access_pin');
            }
        });
    }
};
