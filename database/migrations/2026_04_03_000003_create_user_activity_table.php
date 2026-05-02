<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Watch History
        Schema::create('watch_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('anime_id')->constrained('anime')->cascadeOnDelete();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->integer('progress')->default(0); // seconds watched
            $table->integer('duration')->default(0);  // total duration
            $table->boolean('completed')->default(false);
            $table->timestamp('watched_at')->useCurrent();
            $table->timestamps();
            $table->unique(['user_id', 'episode_id']);
            $table->index(['user_id', 'watched_at']);
            $table->index(['user_id', 'anime_id']);
        });

        // Favorites / Watchlist
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('anime_id')->constrained('anime')->cascadeOnDelete();
            $table->enum('type', ['favorite', 'watching', 'plan_to_watch', 'completed', 'dropped', 'on_hold'])->default('favorite');
            $table->integer('user_score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'anime_id']);
            $table->index(['user_id', 'type']);
        });

        // Comments
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('episode_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('anime_id')->nullable()->constrained('anime')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->integer('likes_count')->default(0);
            $table->integer('dislikes_count')->default(0);
            $table->integer('replies_count')->default(0);
            $table->boolean('is_spoiler')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->enum('status', ['active', 'hidden', 'deleted'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['episode_id', 'created_at']);
            $table->index(['anime_id', 'created_at']);
        });

        // Comment reactions
        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['like', 'dislike']);
            $table->timestamps();
            $table->unique(['user_id', 'comment_id']);
        });

        // Ratings
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('anime_id')->constrained('anime')->cascadeOnDelete();
            $table->tinyInteger('score'); // 1-10
            $table->timestamps();
            $table->unique(['user_id', 'anime_id']);
            $table->index('anime_id');
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // Downloads
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->string('quality');
            $table->bigInteger('file_size')->nullable();
            $table->enum('status', ['pending', 'processing', 'ready', 'failed'])->default('pending');
            $table->string('download_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('comment_reactions');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('watch_history');
    }
};
