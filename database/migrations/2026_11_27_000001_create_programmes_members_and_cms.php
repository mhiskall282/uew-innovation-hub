<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Member accounts, programmes with custom application forms, applications,
 * one-time verification codes, editable email templates, broadcasts and CMS
 * pages. Additive: no existing column is dropped or changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('department');
            $table->string('affiliation', 30)->nullable()->after('phone');
            $table->string('institution')->nullable()->after('affiliation');
            $table->text('bio')->nullable()->after('institution');
            $table->json('interests')->nullable()->after('bio');
            $table->boolean('marketing_opt_in')->default(false)->after('interests');
            $table->timestamp('onboarded_at')->nullable()->after('marketing_opt_in');
        });

        // Staff accounts created before e-mail verification existed are trusted.
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);

        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 30); // verify_email, reset_password
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'purpose', 'consumed_at']);
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type', 30)->index();
            $table->string('focus_area')->nullable()->index();
            $table->string('status', 20)->default('draft')->index();
            $table->string('summary', 500)->nullable();
            $table->longText('description')->nullable();
            $table->text('eligibility')->nullable();
            $table->decimal('funding_amount', 12, 2)->nullable();
            $table->string('location')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable()->index();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->json('form_schema')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'closes_at']);
        });

        Schema::create('program_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->json('answers')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('decision_message')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'user_id']);
            $table->index(['program_id', 'status']);
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('subject');
            $table->text('body');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('body');
            $table->json('audience');
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('summary', 300)->nullable();
            $table->longText('body');
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_footer')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
        Schema::dropIfExists('broadcasts');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('program_applications');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('verification_codes');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn([
            'phone', 'affiliation', 'institution', 'bio', 'interests', 'marketing_opt_in', 'onboarded_at',
        ]));
    }
};
