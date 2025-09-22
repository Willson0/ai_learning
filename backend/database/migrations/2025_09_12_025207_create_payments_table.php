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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->string("payment_id");
            $table->boolean("is_autopayment")->default(false);
            $table->boolean("is_bought")->default(false);

//            $table->unsignedBigInteger("good_id");
//            $table->index("good_id");
//            $table->foreign("good_id")->references("id")->on("goods")->onDelete('cascade');

            $table->integer("amount");
            $table->unsignedInteger("summa");

            $table->unsignedBigInteger("user_id");
            $table->index("user_id");
            $table->foreign("user_id")->references("id")->on("users")->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
