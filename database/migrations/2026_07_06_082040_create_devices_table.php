<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_number')->comment('Номер устройства');
            $table->tinyInteger('status')->default(1)->comment('1-Принято, 2-Диагностика, 3-Ремонт, 4-Списано, 5-Отремонтировано');
            $table->date('received_date')->comment('Дата приема');
            $table->date('repair_date')->nullable()->comment('Дата ремонта');
            $table->text('fault_description')->comment('Описание неисправности');
            $table->text('replaced_parts')->nullable()->comment('Замененные запчасти');
            $table->date('warehouse_date')->nullable()->comment('Дата передачи на склад');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};