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
        Schema::create('trx_leave_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('user_id')
                  ->constrained('sys_users', 'id')
                  ->onDelete('cascade');

            $table->foreignId('leave_type_id')
                  ->constrained('mst_leave_types')
                  ->onDelete('cascade');

            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');

            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignUuid('approved_by')->nullable()->constrained('sys_users', 'id')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trx_leave_requests');
    }
};
