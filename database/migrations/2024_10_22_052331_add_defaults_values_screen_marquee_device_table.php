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
        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedBigInteger('default_screen_id')->nullable();
            $table->foreign('default_screen_id')->references('id')->on('screens');

            $table->unsignedBigInteger('default_marquee_id')->nullable();
            $table->foreign('default_marquee_id')->references('id')->on('marquees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
