<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_path_traversal_is_blocked(): void
    {
        $response = $this->get('/files/reports/..%2F..%2F.env');
        $response->assertStatus(400); // Invalid filename regex rejection
    }

    public function test_invalid_file_category_is_rejected(): void
    {
        $response = $this->get('/files/invalid_category/test.pdf');
        $response->assertStatus(404);
    }

    public function test_non_existent_file_returns_404(): void
    {
        $response = $this->get('/files/reports/non_existent_file_12345.pdf');
        $response->assertStatus(404);
    }

    public function test_existing_private_file_is_streamed_with_nosniff_header(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('reports/sample_report.pdf', '%PDF-1.4 sample content');

        $response = $this->get('/files/reports/sample_report.pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
