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
}
