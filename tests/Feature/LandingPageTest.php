<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_successfully(): void
    {
        Project::create([
            'title' => 'STEM Innovation Test',
            'summary' => 'Test project summary for landing page',
            'category' => 'STEM',
            'status' => 'active',
            'beneficiaries_target' => 1000,
            'beneficiaries_reached' => 500,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('UEW INNOVATION HUB');
        $response->assertSee('STEM Innovation Test');
    }

    public function test_dashboard_cockpit_renders_successfully(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Innovation Telemetry', false);
        $response->assertSee('Total Initiatives', false);
    }
}
