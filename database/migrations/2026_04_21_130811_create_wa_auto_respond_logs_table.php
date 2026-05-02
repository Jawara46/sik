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
        Schema::create('wa_auto_respond_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('sender_number');
            $table->string('sender_name')->nullable();
            $table->string('nisn_queried')->nullable();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->text('request_message');
            $table->text('response_message')->nullable();
            $table->enum('status', ['replied', 'not_found', 'error'])->default('replied');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_auto_respond_logs');
    }
};
