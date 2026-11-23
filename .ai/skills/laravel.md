# Laravel 12 Engineering Skill

> **Platform**: UEW Innovation Hub Enterprise  
> **Domain**: Backend Architecture & Enterprise Framework Standards

---

## 1. Core Principles
- Strict separation of concerns: Thin Controllers, Rich Models, Dedicated FormRequests, Granular Policies, and Domain Services.
- Dependency injection over static facades where testability is enhanced.
- Strongly typed PHP 8.4 syntax (return types, parameter types, union types, match expressions).

---

## 2. Controller Pattern
```php
namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $projectService
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Project::class);
        $projects = $this->projectService->getFilteredPaginatedProjects(request()->all());
        return view('projects.index', compact('projects'));
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);
        $project = $this->projectService->createProject($request->validated(), $request->user());

        return redirect()->route('projects.show', $project)
            ->with('status', 'Project successfully registered with UEW Innovation Hub.');
    }
}
```

---

## 3. Eloquent Standards
- Always define `$fillable` (never empty `$guarded`).
- Always define explicit attribute casting with `casts()` method.
- Define explicit inverse relationships for every `belongsTo` / `hasMany`.
- Use Eloquent local scopes for recurring business queries (`scopeActive()`, `scopeFilterByYear()`).
