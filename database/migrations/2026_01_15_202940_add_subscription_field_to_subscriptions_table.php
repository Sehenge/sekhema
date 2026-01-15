<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function ($table) {
            $table->string('subscription')->after('telegram_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function ($table) {
            $table->dropColumn('subscription');
        });
    }
};
