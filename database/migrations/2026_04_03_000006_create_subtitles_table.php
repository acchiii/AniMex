<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subtitles')) {
            Schema::create('subtitles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
                $table->string('language', 10);
                $table->string('label');
                $table->string('file_path');
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subtitles');
    }
};
