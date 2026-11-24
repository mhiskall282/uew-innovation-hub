<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Member demographics captured at sign-up and onboarding, plus a snapshot of
 * the applicant's profile frozen on each submitted application. Additive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 20)->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('region', 40)->nullable()->after('date_of_birth');
            $table->string('education_level', 30)->nullable()->after('institution');
            $table->string('disability', 20)->nullable()->after('education_level');
        });

        Schema::table('program_applications', function (Blueprint $table) {
            $table->json('applicant_snapshot')->nullable()->after('answers');
        });
    }

    public function down(): void
    {
        Schema::table('program_applications', fn (Blueprint $t) => $t->dropColumn('applicant_snapshot'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['gender', 'date_of_birth', 'region', 'education_level', 'disability']));
    }
};
