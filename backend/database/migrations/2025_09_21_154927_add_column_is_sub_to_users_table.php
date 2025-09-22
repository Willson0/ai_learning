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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_sub')->default(false)->after("tokens");
            $table->dateTime("sub_date")->nullable()->after("is_sub");
            $table->boolean("used_trial")->default(false)->after("sub_date");
            $table->string("payment_method_id")->nullable()->after("used_trial");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_sub');
            $table->dropColumn('sub_date');
            $table->dropColumn('used_trial');
            $table->dropColumn('payment_method_id');
        });
    }
};
