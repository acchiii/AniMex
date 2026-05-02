<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Studios
        Schema::create('studios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->year('founded_year')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Genres
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#6366F1'); // hex color
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tags
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Anime
        Schema::create('anime', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_english')->nullable();
            $table->string('title_japanese')->nullable();
            $table->string('slug')->unique();
            $table->text('synopsis')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('trailer_url')->nullable();
            $table->enum('type', ['TV', 'Movie', 'OVA', 'ONA', 'Special', 'Music'])->default('TV');
            $table->enum('status', ['ongoing', 'completed', 'upcoming', 'hiatus'])->default('upcoming');
            $table->enum('season', ['Winter', 'Spring', 'Summer', 'Fall'])->nullable();
            $table->year('year')->nullable();
            $table->date('aired_from')->nullable();
            $table->date('aired_to')->nullable();
            $table->integer('episodes_count')->nullable();
            $table->integer('episode_duration')->nullable(); // minutes
            $table->enum('rating', ['G', 'PG', 'PG-13', 'R', 'R+', 'Rx'])->default('PG-13');
            $table->string('source')->nullable(); // Manga, Light Novel, etc.
            $table->foreignId('studio_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('score', 4, 2)->default(0);
            $table->integer('score_count')->default(0);
            $table->integer('popularity')->default(0);
            $table->integer('rank')->nullable();
            $table->integer('favorites_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->integer('mal_id')->nullable();
            $table->integer('anilist_id')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_subbed')->default(true);
            $table->boolean('is_dubbed')->default(false);
            $table->boolean('is_premium_only')->default(false);
            $table->json('meta')->nullable(); // SEO and extra data
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'type']);
            $table->index(['is_featured', 'is_trending']);
            $table->index('score');
            $table->index('year');
        });

        // Anime-Genre pivot
        Schema::create('anime_genre', function (Blueprint $table) {
            $table->foreignId('anime_id')->constrained('anime')->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->primary(['anime_id', 'genre_id']);
        });

        // Anime-Tag pivot
        Schema::create('anime_tag', function (Blueprint $table) {
            $table->foreignId('anime_id')->constrained('anime')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['anime_id', 'tag_id']);
        });

        // Video servers
        Schema::create('video_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->enum('type', ['embed', 'hls', 'mp4', 'dash']);
            $table->text('base_url')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        // Episodes
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anime_id')->constrained('anime')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->integer('number');
            $table->decimal('number_decimal', 5, 1)->nullable(); // for 12.5 episodes
            $table->text('synopsis')->nullable();
            $table->string('thumbnail')->nullable();
            $table->integer('duration')->nullable(); // seconds
            $table->date('aired_at')->nullable();
            $table->boolean('is_filler')->default(false);
            $table->boolean('is_recap')->default(false);
            $table->boolean('is_subbed')->default(true);
            $table->boolean('is_dubbed')->default(false);
            $table->boolean('is_premium_only')->default(false);
            $table->integer('intro_start')->nullable(); // seconds
            $table->integer('intro_end')->nullable();
            $table->integer('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['anime_id', 'number']);
            $table->index('aired_at');
        });

        // Episode video sources
        Schema::create('episode_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_server_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->default('default'); // e.g., "Server 1"
            $table->enum('quality', ['360p', '480p', '720p', '1080p', '4K'])->default('720p');
            $table->text('url');
            $table->enum('type', ['hls', 'mp4', 'embed', 'dash'])->default('hls');
            $table->string('language', 10)->default('sub'); // sub, dub
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Subtitles
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

    public function down(): void
    {
        Schema::dropIfExists('subtitles');
        Schema::dropIfExists('episode_sources');
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('video_servers');
        Schema::dropIfExists('anime_tag');
        Schema::dropIfExists('anime_genre');
        Schema::dropIfExists('anime');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('genres');
        Schema::dropIfExists('studios');
    }
};