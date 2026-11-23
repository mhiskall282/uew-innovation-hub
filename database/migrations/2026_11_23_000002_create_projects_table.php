<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->string('category')->index(); // STEM, Educational Tech, Agriculture, Inclusive Education, Digital Humanities, Community Outreach
            $table->enum('status', ['active', 'completed', 'suspended', 'pending'])->default('active')->index();
            $table->date('start_date')->nullable()->index();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('beneficiaries_target')->default(0);
            $table->unsignedInteger('beneficiaries_reached')->default(0);
            $table->decimal('grant_amount', 12, 2)->nullable();
            $table->string('lead_researcher')->nullable();
            $table->string('department')->nullable();
            $table->string('report_file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
