# Pull Request Template

## Description
[Provide a clear summary of the changes made, why they were made, and how they relate to the UEW Innovation Hub enterprise architecture.]

---

## Type of Change
- [ ] `feat`: New feature or capability
- [ ] `fix`: Bug fix
- [ ] `refactor`: Code refactoring without behavior change
- [ ] `migration`: Database schema change
- [ ] `ui`: Npontu styling or Blade UI adjustment
- [ ] `security`: Hardening or vulnerability patch
- [ ] `test`: New or updated tests
- [ ] `docs`: Documentation update

---

## Architectural Checklist
- [ ] Adheres to `.ai/ai.md` and `.ai/architecture.md`
- [ ] FormRequests used for all input validation (no inline controller validation)
- [ ] Model relationships, casts, and scopes properly configured
- [ ] N+1 queries prevented with eager loading (`with(...)`)
- [ ] Authorization checks verified (`Policy` / `Gate`)
- [ ] Private file streaming protected; no direct PDF URLs
- [ ] Npontu UI design tokens and UEW brand colors maintained
- [ ] Tests written and passing: `php artisan test`
- [ ] `.ai/state.md` and `.ai/changelog.md` updated
