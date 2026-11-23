<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grant_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique()->index();
            $table->string('applicant_name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('institution_or_org')->nullable();
            $table->string('project_title');
            $table->string('category')->index();
            $table->decimal('funding_requested', 12, 2)->nullable();
            $table->text('summary');
            $table->string('proposal_file_path')->nullable();
            $table->enum('status', ['submitted', 'under_review', 'shortlisted', 'approved', 'rejected'])->default('submitted')->index();
            $table->text('reviewer_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grant_applications');
    }
};
