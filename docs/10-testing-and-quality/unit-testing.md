# Unit Testing

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `d1296b1`
> **Related:** [testing-strategy](testing-strategy.md), [integration-testing](integration-testing.md), [testing guide (commands)](../05-developer-guide/testing-guide.md), [authorization architecture](../04-architecture/authorization-architecture.md)

The project has one unit test file, `../../tests/Unit/DomainTest.php`, with 5 tests. It does not boot Laravel or touch a database. Other small, pure checks sit inside feature test files. This page lists both, shows how to write a new unit test, and names the pure code that has no unit test.

## Run

```powershell
php artisan test --testsuite=Unit
php vendor/bin/phpunit tests/Unit --testdox
```

```bash
php artisan test --testsuite=Unit
php vendor/bin/phpunit tests/Unit --testdox
```

The suite is defined in `../../phpunit.xml` (`Unit` runs `tests/Unit`). It takes well under a second.

## Existing tests

`DomainTest` extends `PHPUnit\Framework\TestCase` directly, not `Tests\TestCase`. That is why it needs no application or database.

| Test | What it asserts | Code under test |
| --- | --- | --- |
| `test_hub_director_holds_every_permission` | `Role::SuperAdmin->allows()` is true for every case of `Permission` | `app/Enums/Role.php`, `app/Enums/Permission.php` |
| `test_least_privilege_for_restricted_roles` | Viewer cannot `ProjectsManage`; Intern cannot `ContentPublish`; Reviewer cannot `ProjectsManage`; Officer cannot `UsersManage`, `AuditView` or `ProjectsDelete` | `Role` matrix |
| `test_every_staff_role_can_enter_the_workspace_and_members_cannot` | Every role in `Role::staff()` has `WorkspaceAccess`; `Role::Member->permissions()` is empty | `Role` |
| `test_form_schema_normalises_keys_and_drops_invalid_fields` | Duplicate labels get keys `team_name`, `team_name_2`; select options are split by line, de-duplicated and blank lines dropped; a radio field with no options, an empty label and an unknown type (`script`) are removed | `app/Support/FormSchema.php` |
| `test_compact_number_formatting` | `950` stays `950`; `1250` becomes `1.3K`; `100000` becomes `100K`; `2400000` becomes `2.4M`; `1000000000` becomes `1B` | `app/Support/Format.php` |

The first three tests guard the role matrix in `Role`. `AccessControlTest` and `PrivilegesTest` check the same matrix through HTTP.

> **Note:** `test_least_privilege_for_restricted_roles` lists only six denied pairs. A change to another role/permission pair is caught by the HTTP tests, not here.

## Unit-style checks inside feature tests

These tests need the framework (for config, hashing or mail), so they live under `tests/Feature`. They assert pure behaviour.

| Test | Pure behaviour checked |
| --- | --- |
| `SecureDemoAccountsTest::test_strength_rules` | `SecureDemoAccounts::isStrong()` rejects `password`, an all-lowercase string and `null`, and accepts a long mixed-case string with digits |
| `EmailLinksTest::test_masked_email_hides_the_local_part` | The masked e-mail helper hides the local part |
| `GalleryTest::test_exif_orientation_is_read_without_the_exif_extension` | EXIF orientation is read without the PHP `exif` extension |
| `AdministrationTest::test_cms_page_markdown_cannot_inject_html_or_scripts` | Markdown rendering strips HTML and script |

## How to write a unit test

Use `PHPUnit\Framework\TestCase` when the class under test needs no container, database or config. Use `Tests\TestCase` (a feature test) when it does.

```php
<?php

namespace Tests\Unit;

use App\Support\Format;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
{
    #[DataProvider('compactCases')]
    public function test_compact_number_formatting(int $input, string $expected): void
    {
        $this->assertSame($expected, Format::compact($input));
    }

    /** @return array<string, array{0: int, 1: string}> */
    public static function compactCases(): array
    {
        return ['thousand' => [1250, '1.3K'], 'million' => [2_400_000, '2.4M']];
    }
}
```

Rules used in this repository:

- File goes in `tests/Unit`, class name ends in `Test`, methods are `test_<behaviour_in_snake_case>`.
- Data providers use the `#[DataProvider]` attribute and are `public static` (see `RouteSmokeTest` and `AccessControlTest`).
- No `RefreshDatabase` in unit tests. If you need the database, write a feature test.

## Pure code without a unit test

Found by comparing `app/Enums`, `app/Support` and `app/Models` with the test files. Each can be tested without HTTP.

| Code | Why it deserves a unit test |
| --- | --- |
| `app/Support/DateRange.php` | Report periods (presets and custom). Today it is only exercised by `ReportExportTest::test_date_range_presets_and_custom_ranges` |
| `app/Support/Demographics.php` | Feeds reports. No direct assertion |
| `app/Support/EmailTemplates.php` | Template keys and placeholders. Only reached through mail tests |
| `app/Support/Photos.php` | Photo helper. No test names it. Check how it is used before writing tests |
| Status enums (`ProjectStatus`, `ProgramStatus`, `ApplicationStatus`, `GrantStatus`, `BroadcastStatus`, `ReportStatus`) | Labels, colours and allowed values. `BroadcastStatus::Failed` was added in commit `d1296b1` and is only tested through `OperationalFixesTest` |
| Model scopes and helpers such as `Program::isFull()`, `Program::scopeWithActiveApplicationsCount()`, `User` scope `onLeaderboard()` | Tested only inside HTTP flows |

## Recommended, not implemented

- A `Role` and `Permission` snapshot test: assert the full matrix (every role, every permission) in one table, so a matrix change fails one obvious test.
- Unit tests for `DateRange` (presets, custom range, inverted dates).
- Unit tests for each enum (`label()`, `values()`), kept short.
- Mutation testing (for example Infection) on `app/Enums` and `app/Support`. Not installed.
