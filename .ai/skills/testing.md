# Testing & Quality Assurance Skill

> **Platform**: UEW Innovation Hub Enterprise  
> **Domain**: Automated Testing, Pest / PHPUnit, and Verification

---

## 1. Test Strategy
- **Unit Tests**: Domain services (`ProjectServiceTest`, `ReportExportServiceTest`, `AnalyticsServiceTest`), model relationships, and custom validation rules.
- **Feature Tests**: HTTP endpoints, authentication workflows, authorization policies, file uploads, and Excel export generation.
- **Security Tests**: CSRF enforcement, path traversal attempts on file streaming, role escalation attempts.

---

## 2. Example Feature Test
```php
namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_officer_can_create_project(): void
    {
        $officer = User::factory()->create(['role' => 'officer']);

        $response = $this->actingAs($officer)->post(route('projects.store'), [
            'title' => 'STEM Teacher Robotics Lab',
            'summary' => 'Innovative robotics kits for secondary school STEM teachers.',
            'category' => 'EdTech',
            'start_date' => '2026-01-01',
            'beneficiaries_target' => 500,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['title' => 'STEM Teacher Robotics Lab']);
    }

    public function test_viewer_cannot_delete_project(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $project = Project::factory()->create();

        $response = $this->actingAs($viewer)->delete(route('projects.destroy', $project));
        $response->assertForbidden();
    }
}
```
