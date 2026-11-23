<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('posts', fn (Blueprint $t) => $t->index(['type', 'published_at']));
        Schema::table('grant_applications', fn (Blueprint $t) => $t->index(['status', 'created_at']));
        Schema::table('publications', fn (Blueprint $t) => $t->index(['type', 'publish_date']));
        Schema::table('projects', fn (Blueprint $t) => $t->index(['category', 'status']));
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('posts', fn (Blueprint $t) => $t->dropIndex(['type', 'published_at']));
        Schema::table('grant_applications', fn (Blueprint $t) => $t->dropIndex(['status', 'created_at']));
        Schema::table('publications', fn (Blueprint $t) => $t->dropIndex(['type', 'publish_date']));
        Schema::table('projects', fn (Blueprint $t) => $t->dropIndex(['category', 'status']));
    }
};
