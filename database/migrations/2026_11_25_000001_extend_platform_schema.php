<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive schema changes for the workspace redesign. No columns are dropped
 * and existing rows keep their values (new columns have safe defaults).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('department');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_public')->default(true)->after('report_file_path')->index();
        });

        Schema::table('publications', function (Blueprint $table) {
            $table->text('abstract')->nullable()->after('authors');
        });

        Schema::table('grant_applications', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->nullableMorphs('subject');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', fn (Blueprint $t) => $t->dropMorphs('subject'));
        Schema::table('grant_applications', fn (Blueprint $t) => $t->dropColumn('reviewed_at'));
        Schema::table('publications', fn (Blueprint $t) => $t->dropColumn('abstract'));
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['is_public']);
            $table->dropColumn('is_public');
        });
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['is_active', 'last_login_at', 'last_login_ip']));
    }
};
