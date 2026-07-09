<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('plotter_model_id')->nullable()->after('device_number')->constrained('plotter_models')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['plotter_model_id']);
            $table->dropColumn('plotter_model_id');
        });
    }
};