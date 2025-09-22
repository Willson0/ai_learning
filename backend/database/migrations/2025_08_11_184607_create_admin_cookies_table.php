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
        Schema::create('admin_cookies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger("user_id");
            $table->index("user_id");
            $table->foreign("user_id")->references("id")->on("admins")->onDelete("cascade");

            $table->string("cookie");

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_cookies');
    }
};
