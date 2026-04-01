<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_slot_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('timezone', 64)->default('Europe/London');
            $table->unsignedSmallInteger('slot_duration_minutes')->default(45);
            $table->unsignedTinyInteger('buffer_minutes')->default(10);
            $table->boolean('weekend_enabled')->default(false);
            $table->unsignedTinyInteger('auto_generate_days')->default(28);
            $table->enum('default_mode', ['video', 'phone', 'onsite'])->default('video');
            $table->string('default_location')->nullable();
            $table->string('default_meeting_link')->nullable();
            $table->json('weekdays')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_slot_settings');
    }
};

