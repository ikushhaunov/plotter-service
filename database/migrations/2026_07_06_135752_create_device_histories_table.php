<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->tinyInteger('status')->comment('Статус после изменения');
            $table->string('status_name')->comment('Название статуса');
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('comment')->nullable()->comment('Комментарий к изменению');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_histories');
    }
};