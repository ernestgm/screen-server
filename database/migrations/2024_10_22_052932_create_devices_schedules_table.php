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
        Schema::create('device_schedules', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('device_id')->nullable();
            $table->foreign('device_id')->references('id')->on('devices');

            $table->unsignedBigInteger('marquee_id')->nullable();
            $table->foreign('marquee_id')->references('id')->on('marquees');

            $table->unsignedBigInteger('screen_id')->nullable();
            $table->foreign('screen_id')->references('id')->on('screens');

            $table->time('start_time');
            $table->time('end_time');
            $table->enum('schedule_type', ['screen', 'marquee', 'all']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices_schedules');
    }
};
