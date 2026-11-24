<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Member engagement: in-app notifications, achievements and XP, application
 * conversations between applicants and staff, and incubation progress and
 * budget reports. Additive only; no existing data is changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('xp')->default(0)->after('marketing_opt_in');
            $table->boolean('public_profile')->default(false)->after('xp');
            $table->index(['role', 'public_profile', 'xp']);
        });

        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->string('title', 150);
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('xp')->default(0);
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('awarded_at');
            $table->timestamps();
            $table->unique(['user_id', 'program_id', 'type']);
            $table->index(['user_id', 'awarded_at']);
        });

        Schema::create('application_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('from_staff')->default(false);
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['program_application_id', 'id']);
        });

        Schema::create('progress_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('program_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('period', 60);
            $table->date('period_end');
            $table->text('summary');
            $table->text('milestones');
            $table->text('challenges')->nullable();
            $table->text('next_steps')->nullable();
            $table->unsignedInteger('beneficiaries')->nullable();
            $table->decimal('budget_received', 14, 2)->default(0);
            $table->decimal('budget_spent', 14, 2)->default(0);
            $table->json('line_items')->nullable();
            $table->string('status', 20)->default('submitted')->index();
            $table->text('feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['program_application_id', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_reports');
        Schema::dropIfExists('application_messages');
        Schema::dropIfExists('achievements');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'public_profile', 'xp']);
            $table->dropColumn(['xp', 'public_profile']);
        });
    }
};
