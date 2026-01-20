<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function ($table) {
            $table->dropColumn('trial');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function ($table) {
            $table->boolean('trial')->default(false);
        });
    }
};
