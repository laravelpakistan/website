<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('github_id')->default('');
            $table->string('github_url')->default('');
            $table->string('email');
            $table->string('name');

            $table->timestamps();
        });
    }
};
