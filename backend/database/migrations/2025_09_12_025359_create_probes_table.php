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
        Schema::create('probes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger("subject_id");
            $table->index("subject_id");
            $table->foreign("subject_id")->references("id")->on("subjects")->onDelete('cascade');

            $table->string("type")->default("ege");

            $table->string("title");

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('probes');
    }
};
