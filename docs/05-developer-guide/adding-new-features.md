# Adding New Features

> **Audience:** Developers · **Last verified:** 2026-11-30 against commit `9f3bdee`
> **Related:** [backend-development](backend-development.md), [frontend-development](frontend-development.md), [database-development](database-development.md), [testing-guide](testing-guide.md), `../04-architecture/authorization-architecture.md`

A checklist and a worked example for adding a feature to the staff workspace, following the pattern used by the gallery (albums). The example feature, "Testimonials" (short quotes from programme alumni, managed by staff), is invented for this page. Nothing named `Testimonial` exists in the code. The files you would copy from are real.

## Checklist

| # | Step | Where | Reference to copy |
| --- | --- | --- | --- |
| 1 | Migration | `database/migrations/` | `2026_11_30_000001_create_gallery_tables.php` |
| 2 | Enum (if the model has a status or type) | `app/Enums/` | `ProjectStatus.php` |
| 3 | Model, factory | `app/Models/`, `database/factories/` | `Album.php`, `ProgramFactory.php` |
| 4 | Permission, and role mapping | `app/Enums/Permission.php`, `app/Enums/Role.php` | `ContentDraft`, `ContentPublish` |
| 5 | Policy | `app/Policies/` | `AlbumPolicy.php` |
| 6 | FormRequest | `app/Http/Requests/Workspace/` | `PostRequest.php` |
| 7 | Service | `app/Services/` | `GalleryService.php`, `ProjectService.php` |
| 8 | Controller | `app/Http/Controllers/Workspace/` | `AlbumController.php` |
| 9 | Routes | `routes/web.php` (workspace group) | the `albums` block |
| 10 | Views and navigation | `resources/views/workspace/<feature>/`, `components/layouts/workspace.blade.php` | `workspace/albums/` |
| 11 | Tests | `tests/Feature/` | `GalleryTest.php`, `PrivilegesTest.php`, `RouteSmokeTest.php` |
| 12 | Docs and project memory | `docs/`, `.ai/state.md`, `.ai/changelog.md` | |

Run the checks in [development-workflow](development-workflow.md) at the end.

## Worked example: Testimonials

### 1. Migration

```powershell
php artisan make:migration create_testimonials_table
```

Rename the file to follow the dated pattern if the generator date differs from your sequence (`YYYY_MM_DD_NNNNNN_create_testimonials_table.php`). Write it additive and reversible:

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name', 120);
            $table->string('quote', 500);
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
```

Rules from `.ai/rules/database.md`: explicit foreign key behaviour, an index on filtered columns, no loose JSON. CI runs the migration up, down one step and up again on PostgreSQL.

### 2. Model and factory

```php
class Testimonial extends Model   // models in this repo are plain `class`, not `final`
{
    protected $fillable = ['author_name', 'quote', 'program_id'];
    protected $attributes = ['is_published' => false];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class)->withTrashed();
    }
}
```

`is_published` stays out of `$fillable`; only the service sets it (the same rule `Album` follows). Add `database/factories/TestimonialFactory.php` for tests.

### 3. Permission and roles

Reuse an existing permission when it fits. The gallery reuses `content.draft` and `content.publish`. If the feature needs its own, add a case in `app/Enums/Permission.php`:

```php
case TestimonialsManage = 'testimonials.manage';
```

Then edit **both** `match` blocks in `Permission`: `label()` and `group()`. A missing arm throws `UnhandledMatchError` when the Roles & permissions page renders. Finally add the case to the roles that should hold it in `Role::permissions()`. The Hub Director gets it automatically (`Permission::cases()`).

`AppServiceProvider::registerPermissionGates()` turns every case into a gate, so `can:testimonials.manage` and `@can('testimonials.manage')` work with no more code. The Roles & permissions page (`RoleController`) lists permissions from `Permission::cases()`, so it updates by itself.

> **Warning:** Changing `Role::permissions()` changes who can do what in production the moment it deploys. Keep least privilege. Review the matrix in `../04-architecture/authorization-architecture.md` and update it.

### 4. Policy

```php
final class TestimonialPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermission(Permission::TestimonialsManage); }
    public function create(User $user): bool { return $user->hasPermission(Permission::TestimonialsManage); }
    public function update(User $user, Testimonial $testimonial): bool { return $user->hasPermission(Permission::TestimonialsManage); }
    public function delete(User $user, Testimonial $testimonial): bool { return $user->hasPermission(Permission::TestimonialsManage); }
}
```

Laravel discovers `App\Policies\TestimonialPolicy` for `App\Models\Testimonial` by naming convention, as for the existing policies.

`AppServiceProvider` registers no explicit `Gate::policy()` calls. Auto-discovery works: `Gate::getPolicyFor(App\Models\Album::class)` returns `App\Policies\AlbumPolicy` (checked in Tinker).

### 5. FormRequest

```php
class TestimonialRequest extends FormRequest
{
    public function authorize(): bool { return true; }   // the route middleware and the controller's Gate::authorize decide

    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:120'],
            'quote' => ['required', 'string', 'max:500'],
            'program_id' => ['nullable', 'exists:programs,id'],
        ];
    }
}
```

### 6. Service

```php
final class TestimonialService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function save(Testimonial $testimonial, array $data, User $user): Testimonial
    {
        return DB::transaction(function () use ($testimonial, $data, $user) {
            $isNew = ! $testimonial->exists;
            $testimonial->fill($data);
            if ($isNew) { $testimonial->created_by = $user->id; }
            $testimonial->save();
            $this->audit->record($isNew ? 'testimonial.created' : 'testimonial.updated', "Saved testimonial by {$testimonial->author_name}", $testimonial);
            return $testimonial;
        });
    }
}
```

### 7. Controller

```php
class TestimonialController extends Controller
{
    public function __construct(private readonly TestimonialService $testimonials) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Testimonial::class);
        return view('workspace.testimonials.index', ['testimonials' => Testimonial::query()->with('program')->latest()->paginate(20)]);
    }

    public function store(TestimonialRequest $request): RedirectResponse
    {
        Gate::authorize('create', Testimonial::class);
        $this->testimonials->save(new Testimonial, $request->validated(), $request->user());
        return redirect()->route('workspace.testimonials.index')->with('status', 'Testimonial saved.');
    }
}
```

Note `with('program')`: lazy loading throws outside production.

### 8. Routes

Inside the `workspace` group in `routes/web.php`, under a `can:` middleware group. The outer group already applies `auth`, `active`, `verified.email` and `can:workspace.access`:

```php
Route::middleware('can:testimonials.manage')->group(function () {
    Route::resource('testimonials', Workspace\TestimonialController::class)->except('show');
});
```

Add `->middleware('throttle:...')` to POST routes that accept uploads or can be abused. Route names become `workspace.testimonials.index` and so on.

### 9. Views and navigation

Create `resources/views/workspace/testimonials/{index,create,edit}.blade.php` using `<x-layouts.workspace>`, `<x-ui.page-header>`, `<x-ui.card>`, `<x-ui.empty>` and the form components (copy `workspace/albums/index.blade.php`). Show an empty state and field errors. Add a sidebar entry to the `$groups` array in `resources/views/components/layouts/workspace.blade.php`:

```php
['label' => 'Testimonials', 'icon' => 'photo', 'route' => 'workspace.testimonials.index', 'match' => 'workspace.testimonials.*', 'can' => 'testimonials.manage'],
```

Entries are filtered by `$user->can($item['can'])`, so staff without the permission do not see them. Use an icon name that exists in `components/ui/icon.blade.php`.

### 10. Tests

1. **Feature test** `tests/Feature/Workspace/TestimonialTest.php` with `RefreshDatabase`: an authorised role can create and see an item; an unauthorised role gets 403; a validation error is returned for bad input; an audit row exists.
2. **PrivilegesTest.** `test_every_state_changing_workspace_route_enforces_its_permission` iterates every POST, PUT, PATCH and DELETE route under `workspace` that has a `can:` middleware and checks each role lacking that permission gets 403 or 404. Your new routes are covered automatically, but run it, and raise its minimum (`assertGreaterThanOrEqual(120, ...)`) only if the matrix grows a lot. If you changed `Role::permissions()`, also read the explicit role tests in that file for expectations that no longer hold.
3. **RouteSmokeTest.** It requests every named GET route as a guest and each role and fails on a 500. A route with no model binding is requested as it is. If your index route needs a parameter, add a binding to the `$bindings` array in `RouteSmokeTest::urls()`. Add data for your model to the demo seeder only if the smoke test needs a record.
4. **Playwright**, only for a new user journey that matters. See [testing-guide](testing-guide.md).

```powershell
php artisan test --filter TestimonialTest
php artisan test --filter PrivilegesTest
php artisan test --filter RouteSmokeTest
vendor\bin\pint
php artisan test
```

### 11. Documentation

- `../06-api-reference/endpoints.md` if you add an API endpoint.
- `../07-database/tables-and-columns.md` for the new table.
- `../04-architecture/authorization-architecture.md` and `../02-user-guide/user-roles-and-permissions.md` if the permission matrix changed.
- `.ai/state.md` and `.ai/changelog.md` (the project asks for this after meaningful changes).

## Other feature types

| Feature | Differences |
| --- | --- |
| Member portal page | Routes in the `portal` group (`auth`, `active`, `verified.email`, `onboarded`), controller in `app/Http/Controllers/Portal/`, views in `resources/views/portal/` |
| Public page | Routes outside auth, controller in `Site/`, views in `resources/views/site/`, cache heavy queries as `MetricsService` does |
| API endpoint | Route in `routes/api.php` under `api.v1.`, controller in `Api/V1/`, JSON resource in `Http/Resources/Api/V1/`. Read-only. See `../06-api-reference/` |
| Queued e-mail | Mailable in `app/Mail/`, template keys in `app/Support/EmailTemplates.php`; see how `TemplatedMail` is used |
