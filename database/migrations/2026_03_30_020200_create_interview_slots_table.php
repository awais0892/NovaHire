<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('slot_date');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('timezone', 64)->default('Europe/London');
            $table->unsignedSmallInteger('duration_minutes')->default(45);
            $table->unsignedTinyInteger('buffer_minutes')->default(10);
            $table->enum('mode', ['video', 'phone', 'onsite'])->default('video');
            $table->boolean('is_available')->default(true);
            $table->boolean('is_bank_holiday')->default(false);
            $table->string('holiday_name')->nullable();
            $table->foreignId('interviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('booked_application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'starts_at'], 'interview_slots_company_start_unique');
            $table->index(['company_id', 'slot_date', 'is_available']);
            $table->index(['company_id', 'is_bank_holiday', 'slot_date']);
            $table->index(['booked_application_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_slots');
    }
};

