<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->default('Research Paper')->index(); // Research Paper, Policy Brief, Newsletter, Technical Report
            $table->string('authors')->nullable();
            $table->date('publish_date')->nullable()->index();
            $table->string('journal_name')->nullable();
            $table->string('doi_url')->nullable();
            $table->string('pdf_file_path')->nullable();
            $table->unsignedInteger('downloads_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
