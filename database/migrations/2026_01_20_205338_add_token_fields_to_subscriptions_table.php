<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function ($table) {
            $table->integer('plan_tokens')->after('telegram_id')->nullable()->default(0)->unsigned();
            $table->integer('used_plan_tokens')->after('plan_tokens')->nullable()->default(0)->unsigned();
            $table->integer('trial_tokens')->after('used_plan_tokens')->nullable()->default(0)->unsigned();
            $table->integer('used_trial_tokens')->after('trial_tokens')->nullable()->default(0)->unsigned();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function ($table) {
            $table->dropColumn('plan_tokens');
            $table->dropColumn('used_plan_tokens');
            $table->dropColumn('trial_tokens');
            $table->dropColumn('used_trial_tokens');
        });
    }
};
