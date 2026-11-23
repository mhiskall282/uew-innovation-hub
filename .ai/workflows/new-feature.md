# New Feature Workflow

> **Platform**: UEW Innovation Hub Enterprise

When implementing a new feature:

## 1. Discover
Inspect the repository. Find:
- existing related functionality (legacy PHP scripts or new Laravel files)
- models, migrations, and relationships
- controllers, routes, and middleware
- views, components, and Alpine.js behaviors
- tests and factories

## 2. Define
Identify:
- user story and acceptance criteria
- database changes (new tables, columns, indexes)
- authorization requirements (which roles can access)
- UI states (loading, empty, error, success)
- edge cases (invalid dates, duplicate partners, oversized files)

## 3. Design
Determine the smallest coherent architecture. Follow `.ai/architecture.md`.

## 4. Implement
Build in this order:
1. Migration (if schema changes are needed)
2. Eloquent Model (with relationships, casts, scopes)
3. FormRequest validation
4. Policy / Gate authorization
5. Service class (for complex business logic)
6. Controller actions
7. Named routes
8. Blade views / Npontu components
9. Alpine.js interactivity (if needed)
10. Feature and unit tests

## 5. Verify
Run:
- `php artisan migrate` (confirm migration applies cleanly)
- `php artisan test` (confirm all tests pass)
- `npm run build` (confirm assets compile)

## 6. Review
Check:
- security (CSRF, authorization, input validation, file upload safety)
- accessibility (contrast, labels, focus rings, responsive layout)
- performance (N+1 query prevention, eager loading)
- maintainability (reusable components, clean code)

## 7. Record
Update:
- `.ai/state.md` with current system status
- `.ai/changelog.md` with the new feature entry
- `.ai/decisions.md` if an architectural decision was made
