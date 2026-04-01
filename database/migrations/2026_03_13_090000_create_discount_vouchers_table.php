<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('type', 20)->default('percent'); // percent|fixed
            $table->decimal('value', 10, 2);
            $table->string('currency', 3)->default('usd');
            $table->integer('min_subtotal')->default(0); // cents
            $table->integer('max_discount')->nullable(); // cents
            $table->json('applies_to_plans')->nullable();
            $table->json('billing_cycles')->nullable(); // monthly|annual
            $table->boolean('first_purchase_only')->default(false);
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('per_company_limit')->nullable();
            $table->unsignedInteger('redeemed_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_vouchers');
    }
};

