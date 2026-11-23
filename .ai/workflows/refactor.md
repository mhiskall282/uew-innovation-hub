# Refactor Workflow

> **Platform**: UEW Innovation Hub Enterprise

1. **Identify the scope**: Determine which files, classes, or modules are affected by the refactor.
2. **Ensure test coverage exists**: Before refactoring, verify that the affected functionality has test coverage. Write tests first if none exist.
3. **Refactor incrementally**: Make small, focused changes that can be individually verified.
4. **Preserve public interfaces**: Do not change method signatures, route paths, or database schema unless explicitly intended as part of the refactoring task.
5. **Run full test suite**: Execute `php artisan test` after every logical refactoring step.
6. **Verify views render**: If Blade views or Alpine.js components were touched, manually verify rendering at key breakpoints.
7. **Update documentation**: Update `.ai/state.md` and `.ai/changelog.md` to reflect the structural improvements.

Never refactor unrelated code while fixing a bug or implementing a feature. Keep changes scoped.
