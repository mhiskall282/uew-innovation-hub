# Core Engineering Rules

> **Platform**: UEW Innovation Hub Enterprise  

1. **Inspect before modifying**: Always read existing models, controllers, and views before proposing changes.
2. **Never invent architecture**: Follow established patterns in `.ai/architecture.md` and standard Laravel 12 conventions.
3. **Preserve existing functionality & data**: All legacy project records, partner organization relationships, beneficiaries, and PDF documents must be protected against data loss.
4. **Prefer Laravel-native idioms**: Use Eloquent relationships, FormRequests, Policies, Blade components, and the Storage facade rather than custom reinvented wheels.
5. **Zero unnecessary dependencies**: Keep dependencies lean. Avoid heavy external packages when native framework capabilities suffice.
6. **Never expose secrets**: Environment variables (`.env`) must never be hardcoded in code or committed to git.
7. **Never trust client-side validation**: All data must be validated server-side using dedicated `FormRequest` classes.
8. **Authorization happens on the server**: Admin actions and sensitive records must be authorized via Policies or Gates; hiding UI elements is never security.
9. **Every database change requires a migration**: Never modify schemas directly in a database GUI without an accompanying reversible migration.
10. **Avoid N+1 queries**: Always eager-load relationships (`with(['organizations', 'creator'])`) when displaying lists or tables.
11. **Responsive, accessible interfaces**: Ensure UI works across all viewports (320px to 1920px) and satisfies WCAG AA contrast standards.
12. **Handle all UI states**: Every view or component must gracefully render Loading, Empty, Error, and Success states.
13. **Write tests for business logic**: Cover critical flows (project registration, partner sync, file streaming, export generation) with automated feature tests.
14. **Truth in reporting**: Never claim verification or test execution that did not actually take place.
15. **Maintain project memory**: Update `.ai/state.md` and `.ai/changelog.md` after meaningful changes.
16. **Keep changes tightly scoped**: Do not refactor unrelated subsystems when solving a localized task.
17. **Favor maintainability over cleverness**: Write clean, explicit, self-documenting code.
18. **Protect destructive operations**: Deletions and status changes must require confirmation prompts and be executed atomically.
19. **Honor UEW identity and Npontu styling**: Strictly adhere to the designated color tokens (`#102a72`, `#3157d5`, `#6d28d9`, `#f59e0b`) and Npontu cockpit design specifications.
20. **Security & Data Integrity are non-negotiable**: Defensive engineering is mandatory across all layers.
