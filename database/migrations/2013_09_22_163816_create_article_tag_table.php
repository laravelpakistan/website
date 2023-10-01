<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_tag', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('article_id')->index();
            $table->integer('tag_id')->index();

            $table->timestamps();
        });
    }
};
