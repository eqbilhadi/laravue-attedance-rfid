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
        Schema::create('trx_attendances', function (Blueprint $table) {
            $table->id();

            // Kunci asing ke tabel users
            $table->foreignUuid('user_id')
                  ->constrained('sys_users', 'id')
                  ->onDelete('cascade');

            // Menyimpan jadwal apa yang berlaku pada hari itu
            $table->foreignId('work_schedule_id')
                  ->constrained('mst_work_schedules')
                  ->onDelete('cascade');
            
            $table->date('date');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->integer('late_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);

            // Status akhir kehadiran pada hari itu
            $table->enum('status', [
                'Present', 
                'Late', 
                'Absent', 
                'Sick', 
                'Permit', 
                'Leave', 
                'Holiday'
            ]);

            $table->text('notes')->nullable(); // Untuk catatan dari admin saat koreksi
            $table->timestamps();

            // Memastikan satu user hanya punya satu rekap per hari
            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_attendances');
    }
};
