<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Fine-grained abilities. Each case is registered as a Gate in
 * AppServiceProvider, so routes use `can:projects.manage` and views use
 * `@can('projects.manage')`. Roles map to permissions in Role::permissions().
 */
enum Permission: string
{
    case WorkspaceAccess = 'workspace.access';
    case ProjectsView = 'projects.view';
    case ProjectsManage = 'projects.manage';
    case ProjectsDelete = 'projects.delete';
    case ReportsExport = 'reports.export';
    case ReportsView = 'reports.view';
    case GrantsReview = 'grants.review';
    case ProgramsManage = 'programs.manage';
    case ApplicationsReview = 'applications.review';
    case MembersManage = 'members.manage';
    case BroadcastsSend = 'broadcasts.send';
    case ContentDraft = 'content.draft';
    case ContentPublish = 'content.publish';
    case PagesManage = 'pages.manage';
    case PublicationsManage = 'publications.manage';
    case PartnersManage = 'partners.manage';
    case MessagesManage = 'messages.manage';
    case UsersManage = 'users.manage';
    case SettingsManage = 'settings.manage';
    case AuditView = 'audit.view';

    /** Plain-language description, shown on the Roles & permissions page. */
    public function label(): string
    {
        return match ($this) {
            self::WorkspaceAccess => 'Sign in to the staff workspace',
            self::ProjectsView => 'View the project portfolio',
            self::ProjectsManage => 'Create and edit projects',
            self::ProjectsDelete => 'Archive or delete projects',
            self::ReportsExport => 'Export portfolio data (CSV)',
            self::ReportsView => 'View reports and export PDF reports',
            self::GrantsReview => 'Review legacy grant applications',
            self::ProgramsManage => 'Create programmes and application forms',
            self::ApplicationsReview => 'Review applications, message applicants, review progress reports',
            self::MembersManage => 'Manage members, award badges, export member data',
            self::BroadcastsSend => 'Send announcement e-mails to members',
            self::ContentDraft => 'Draft news posts and gallery albums',
            self::ContentPublish => 'Publish news and gallery albums',
            self::PagesManage => 'Edit website pages (About, FAQ, Privacy...)',
            self::PublicationsManage => 'Manage publications',
            self::PartnersManage => 'Manage partner organisations',
            self::MessagesManage => 'Read and answer contact enquiries',
            self::UsersManage => 'Manage staff accounts and roles',
            self::SettingsManage => 'Change site settings, e-mail (SMTP) and templates',
            self::AuditView => 'View the audit log',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::WorkspaceAccess => 'Workspace',
            self::ProjectsView, self::ProjectsManage, self::ProjectsDelete, self::ReportsExport, self::ReportsView, self::PublicationsManage, self::PartnersManage => 'Portfolio & reports',
            self::GrantsReview, self::ProgramsManage, self::ApplicationsReview => 'Programmes',
            self::MembersManage, self::BroadcastsSend, self::MessagesManage => 'People',
            self::ContentDraft, self::ContentPublish, self::PagesManage => 'Website',
            self::UsersManage, self::SettingsManage, self::AuditView => 'Administration',
        };
    }
}
