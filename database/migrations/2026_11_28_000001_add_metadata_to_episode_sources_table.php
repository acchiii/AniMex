<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episode_sources', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('headers');
            $table->string('provider')->nullable()->after('metadata');
            $table->timestamp('expires_at')->nullable()->after('provider');
            $table->unsignedInteger('failure_count')->default(0)->after('expires_at');
            $table->timestamp('last_failed_at')->nullable()->after('failure_count');
        });
    }

    public function down(): void
    {
        Schema::table('episode_sources', function (Blueprint $table) {
            $table->dropColumn([
                'metadata',
                'provider',
                'expires_at',
                'failure_count',
                'last_failed_at',
            ]);
        });
    }
};
