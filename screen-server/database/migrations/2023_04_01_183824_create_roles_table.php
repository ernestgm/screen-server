<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id()->foreign('Users.id');
            $table->text('name');
            $table->text('tag');
            $table->timestamps();
        });
        DB::table('roles')->insert([
            [
                'name' => 'Administrator',
                'tag' => 'admin'
            ],
            [
                'name' => 'Owner',
                'tag' => 'owner',
            ],
            [
                'name' => 'Editor',
                'tag' => 'editor'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
