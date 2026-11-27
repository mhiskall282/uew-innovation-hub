<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Event photo gallery: albums (one per event) and their photos. Additive only. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title', 160);
            $table->string('summary', 300)->nullable();
            $table->text('description')->nullable();
            $table->date('event_date')->nullable()->index();
            $table->string('location', 160)->nullable();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('cover_photo_id')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_published', 'event_date']);
        });

        Schema::create('album_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 30);
            $table->string('path_large');
            $table->string('path_thumb');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('size_bytes')->default(0);
            $table->string('caption', 300)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['album_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('album_photos');
        Schema::dropIfExists('albums');
    }
};
