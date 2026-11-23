# Feature Specification Template

## Feature: [Feature Name]

### 1. User Story
As a [Role: Super Admin | Project Officer | Academic Reviewer | Public Viewer],  
I want to [action / goal],  
So that [business value / institutional outcome].

---

### 2. Acceptance Criteria
- [ ] Criterion 1 (e.g., Given authenticated as Project Officer, when submitting form, then project is saved with status 'draft')
- [ ] Criterion 2 (e.g., File upload validates PDF format and size <= 15MB)
- [ ] Criterion 3 (e.g., Audit log event is dispatched)

---

### 3. Architecture & Data Flow
- **Route**: `METHOD /path` (Route Name: `resource.action`)
- **Controller**: `App\Http\Controllers\ResourceController@action`
- **FormRequest**: `App\Http\Requests\ActionResourceRequest`
- **Policy**: `App\Policies\ResourcePolicy@permission`
- **Service**: `App\Services\ResourceService`
- **Database Tables**: `projects`, `project_organizations`
- **View / Component**: `resources/views/projects/action.blade.php`

---

### 4. Edge Cases & Error Handling
- Invalid or corrupted file uploads
- Conflicting date ranges (end date before start date)
- Unauthorized access attempts (403 forbidden)
- Concurrency collisions (simultaneous status updates)

---

### 5. Verification Checklist
- [ ] Unit & Feature tests passing (`php artisan test --filter=ResourceTest`)
- [ ] UI verified on desktop (1440px) and mobile (375px)
- [ ] CSRF token and authorization policies enforced
- [ ] `.ai/state.md` and `.ai/changelog.md` updated
