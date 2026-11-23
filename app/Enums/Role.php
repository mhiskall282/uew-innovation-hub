<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Account roles. Staff roles use the workspace; `member` is the public
 * account people create to apply for programmes. The role -> permission
 * matrix lives here and nowhere else.
 */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Officer = 'officer';
    case Reviewer = 'reviewer';
    case Intern = 'intern';
    case Viewer = 'viewer';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Hub Director',
            self::Officer => 'Programme Officer',
            self::Reviewer => 'Reviewer',
            self::Intern => 'Communications Intern',
            self::Viewer => 'Read-only Staff',
            self::Member => 'Member',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Full access, including staff accounts, site settings, e-mail and the audit log.',
            self::Officer => 'Runs programmes and applications, members, broadcasts, projects, content and enquiries.',
            self::Reviewer => 'Reviews programme and grant applications and sees reports.',
            self::Intern => 'Drafts news and blog posts for an officer to publish.',
            self::Viewer => 'Read-only access to the project portfolio.',
            self::Member => 'Public account used to apply for programmes.',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::SuperAdmin => 'gold',
            self::Officer => 'blue',
            self::Reviewer => 'purple',
            self::Intern => 'green',
            self::Viewer, self::Member => 'slate',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Member;
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => Permission::cases(),
            self::Officer => [
                Permission::WorkspaceAccess,
                Permission::ProjectsView,
                Permission::ProjectsManage,
                Permission::ReportsExport,
                Permission::ReportsView,
                Permission::GrantsReview,
                Permission::ProgramsManage,
                Permission::ApplicationsReview,
                Permission::MembersManage,
                Permission::BroadcastsSend,
                Permission::ContentDraft,
                Permission::ContentPublish,
                Permission::PagesManage,
                Permission::PublicationsManage,
                Permission::PartnersManage,
                Permission::MessagesManage,
            ],
            self::Reviewer => [
                Permission::WorkspaceAccess,
                Permission::ProjectsView,
                Permission::ReportsExport,
                Permission::ReportsView,
                Permission::GrantsReview,
                Permission::ApplicationsReview,
            ],
            self::Intern => [
                Permission::WorkspaceAccess,
                Permission::ProjectsView,
                Permission::ContentDraft,
            ],
            self::Viewer => [
                Permission::WorkspaceAccess,
                Permission::ProjectsView,
            ],
            self::Member => [],
        };
    }

    public function allows(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /** @return list<self> */
    public static function staff(): array
    {
        return array_values(array_filter(self::cases(), fn (self $r) => $r->isStaff()));
    }

    /**
     * @return array<string, string> value => label, for select inputs
     */
    public static function options(): array
    {
        return collect(self::staff())->mapWithKeys(fn (self $r) => [$r->value => $r->label()])->all();
    }
}
