<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pastes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('author_id')->nullable();
            $table->integer('parent_id')->nullable();
            $table->text('description')->nullable();
            $table->text('code');
            $table->integer('comment_count');
            $table->integer('child_count');

            $table->timestamps();
        });
    }
};
