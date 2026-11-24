<?php

namespace Tests\Unit;

use App\Enums\Permission;
use App\Enums\Role;
use App\Support\Format;
use App\Support\FormSchema;
use PHPUnit\Framework\TestCase;

class DomainTest extends TestCase
{
    public function test_hub_director_holds_every_permission(): void
    {
        foreach (Permission::cases() as $permission) {
            $this->assertTrue(Role::SuperAdmin->allows($permission), $permission->value);
        }
    }

    public function test_least_privilege_for_restricted_roles(): void
    {
        $this->assertFalse(Role::Viewer->allows(Permission::ProjectsManage));
        $this->assertFalse(Role::Intern->allows(Permission::ContentPublish));
        $this->assertFalse(Role::Reviewer->allows(Permission::ProjectsManage));
        $this->assertFalse(Role::Officer->allows(Permission::UsersManage));
        $this->assertFalse(Role::Officer->allows(Permission::AuditView));
        $this->assertFalse(Role::Officer->allows(Permission::ProjectsDelete));
    }

    public function test_every_staff_role_can_enter_the_workspace_and_members_cannot(): void
    {
        foreach (Role::staff() as $role) {
            $this->assertTrue($role->allows(Permission::WorkspaceAccess), $role->value);
        }

        $this->assertSame([], Role::Member->permissions());
    }

    public function test_form_schema_normalises_keys_and_drops_invalid_fields(): void
    {
        $fields = FormSchema::normalize([
            ['label' => 'Team name', 'type' => 'short_text'],
            ['label' => 'Team name', 'type' => 'short_text'],
            ['label' => 'Pick one', 'type' => 'select', 'options' => "A\nB\n\nA"],
            ['label' => 'No choices', 'type' => 'radio', 'options' => ''],
            ['label' => '', 'type' => 'short_text'],
            ['label' => 'Bad type', 'type' => 'script'],
        ]);

        $this->assertSame(['team_name', 'team_name_2', 'pick_one'], array_column($fields, 'key'));
        $this->assertSame(['A', 'B'], $fields[2]['options']);
    }

    public function test_compact_number_formatting(): void
    {
        $this->assertSame('950', Format::compact(950));
        $this->assertSame('1.3K', Format::compact(1250));
        $this->assertSame('100K', Format::compact(100000));
        $this->assertSame('2.4M', Format::compact(2_400_000));
        $this->assertSame('1B', Format::compact(1_000_000_000));
    }
}
