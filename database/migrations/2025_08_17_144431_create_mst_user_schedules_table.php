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
        Schema::create('mst_user_schedules', function (Blueprint $table) {
            $table->id();

            // Kunci asing ke tabel users
            $table->foreignUuid('user_id')
                ->constrained('sys_users', 'id')
                ->onDelete('cascade');

            // Kunci asing ke tabel work_schedules
            $table->foreignId('work_schedule_id')
                ->constrained('mst_work_schedules')
                ->onDelete('cascade');

            $table->date('start_date');

            // end_date dibuat nullable sesuai permintaan
            $table->date('end_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_user_schedules');
    }
};
