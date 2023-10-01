<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('threads', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable();
        });

        Schema::table('replies', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable();
        });
    }
};
