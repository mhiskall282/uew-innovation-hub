<?php

namespace Tests\Feature;

use App\Models\GrantApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GrantApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_submit_grant_application(): void
    {
        $response = $this->post('/apply', [
            'applicant_name' => 'Kweku Mensah',
            'email' => 'k.mensah@gmail.com',
            'phone' => '+233 24 555 1234',
            'institution_or_org' => 'Department of Chemistry Education, UEW',
            'project_title' => 'Green Chemistry Bio-Pesticide from Local Neem Seed',
            'category' => 'Agriculture',
            'funding_requested' => 14000.00,
            'summary' => 'Formulation of an organic pesticide for smallholder peri-urban farmers in Winneba to reduce synthetic pesticide reliance.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('grant_applications', [
            'applicant_name' => 'Kweku Mensah',
            'project_title' => 'Green Chemistry Bio-Pesticide from Local Neem Seed',
            'status' => 'submitted',
        ]);
    }

    public function test_reviewer_can_update_application_status(): void
    {
        $reviewer = User::create([
            'name' => 'Dean of Science',
            'email' => 'dean@uew.edu.gh',
            'role' => 'reviewer',
            'password' => Hash::make('password'),
        ]);

        $app = GrantApplication::create([
            'applicant_name' => 'Abena Serwaa',
            'email' => 'abena@gmail.com',
            'phone' => '+233 50 111 2222',
            'project_title' => 'Assistive Audio Maths for Blind Primary Students',
            'category' => 'Inclusive Education',
            'funding_requested' => 9500.00,
            'summary' => 'Development of talking arithmetic calculators tailored to the national primary curriculum.',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($reviewer)->post("/admin/grants/{$app->id}/status", [
            'status' => 'under_review',
            'reviewer_notes' => 'Preliminary evaluation indicates exceptional pedagogical value.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('grant_applications', [
            'id' => $app->id,
            'status' => 'under_review',
            'reviewed_by' => $reviewer->id,
        ]);
    }
}
