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
        Schema::create('trx_raw_attendances', function (Blueprint $table) {
            $table->id();

            // Kunci asing ke tabel users
            $table->foreignUuid('user_id')
                  ->constrained('sys_users', 'id')
                  ->onDelete('cascade');

            // Tanggal presensi (untuk memudahkan pencarian)
            $table->date('date');

            // Waktu scan masuk dan pulang yang definitif
            $table->timestamp('clock_in');
            $table->timestamp('clock_out')->nullable();
            
            $table->timestamps();

            // Memastikan satu user hanya punya satu data mentah per hari
            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_raw_attendances');
    }
};
