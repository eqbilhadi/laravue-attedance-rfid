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
        Schema::create('sys_rfid_scans', function (Blueprint $table) {
            $table->id();
            $table->string('device_uid', 20);
            $table->foreign('device_uid')
                ->references('device_uid')
                ->on('sys_devices')
                ->cascadeOnDelete();
            $table->string('card_uid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_rfid_scans');
    }
};
