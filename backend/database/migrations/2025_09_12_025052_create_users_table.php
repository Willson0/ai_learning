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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('telegram_id');

            $table->string('fullname');
            $table->string('username')->nullable();
            $table->string("avatar")->nullable();

            $table->boolean("autopayment")->default(true);
            $table->unsignedInteger("bonus")->default(0);
            $table->boolean("spend_bonus")->default(false);

            $table->integer("free_hints")->default(3);
            $table->integer("hints")->default(0);

            $table->integer("tokens")->default(0);

            $table->unsignedBigInteger("from_user_id")->nullable();
            $table->json("card")->nullable();

            $table->json("pinned_achievements")->nullable();

            $table->json("data")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
