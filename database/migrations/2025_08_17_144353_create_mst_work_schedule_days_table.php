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
        Schema::create('mst_work_schedule_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_schedule_id')->constrained('mst_work_schedules', 'id')->onDelete('cascade');
            $table->tinyInteger('day_of_week');
            $table->foreignId('work_time_id')->nullable()->constrained('mst_work_times')->onDelete('set null');
            $table->unique(['work_schedule_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_work_schedule_days');
    }
};
