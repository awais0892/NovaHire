<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_slot_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('exception_date');
            $table->enum('exception_type', ['blackout', 'holiday_override'])->default('blackout');
            $table->boolean('is_available')->default(false);
            $table->time('starts_at_time')->nullable();
            $table->time('ends_at_time')->nullable();
            $table->string('reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(
                ['company_id', 'exception_date', 'exception_type'],
                'interview_slot_exceptions_company_date_type_unique'
            );
            $table->index(['company_id', 'exception_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_slot_exceptions');
    }
};

