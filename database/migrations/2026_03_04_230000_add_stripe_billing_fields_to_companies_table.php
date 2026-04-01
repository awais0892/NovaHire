<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')->nullable()->after('status')->index();
            }
            if (!Schema::hasColumn('companies', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id')->index();
            }
            if (!Schema::hasColumn('companies', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable()->after('stripe_subscription_id');
            }
            if (!Schema::hasColumn('companies', 'billing_status')) {
                $table->string('billing_status')->nullable()->after('stripe_price_id');
            }
            if (!Schema::hasColumn('companies', 'billing_period_ends_at')) {
                $table->timestamp('billing_period_ends_at')->nullable()->after('billing_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'billing_period_ends_at')) {
                $table->dropColumn('billing_period_ends_at');
            }
            if (Schema::hasColumn('companies', 'billing_status')) {
                $table->dropColumn('billing_status');
            }
            if (Schema::hasColumn('companies', 'stripe_price_id')) {
                $table->dropColumn('stripe_price_id');
            }
            if (Schema::hasColumn('companies', 'stripe_subscription_id')) {
                $table->dropColumn('stripe_subscription_id');
            }
            if (Schema::hasColumn('companies', 'stripe_customer_id')) {
                $table->dropColumn('stripe_customer_id');
            }
        });
    }
};
