# Laravel Engineering Rules

> **Platform**: UEW Innovation Hub Enterprise  

## What to Use:
- **Dedicated Form Requests**: Validate inputs and handle authorization via `authorize()` and `rules()` methods.
- **Eloquent Relationships**: Define explicit relation methods (`belongsToMany`, `hasMany`, `belongsTo`) with appropriate pivot parameters.
- **Strict Casts & Attributes**: Define `$fillable` arrays, native `$casts` (dates, booleans, integers), and accessors where appropriate.
- **Route Model Binding**: Use implicit or scoped route model binding (`Route::get('/projects/{project:slug}', ...)`) for clean URLs.
- **Named Routes**: Always generate URLs using `route('projects.index')` or `route('projects.show', $project)`.
- **Reusable Blade Components**: Encapsulate UI elements in `<x-npontu.button>`, `<x-npontu.badge>`, `<x-npontu.modal>`, `<x-npontu.stat-card>`.
- **Domain Service Classes**: Place complex operations (such as multi-partner sync, telemetry aggregation, or Excel export generation) in `App\Services\`.
- **Database Transactions**: Wrap multi-table operations (e.g. project creation + partner pivot inserts + file upload) in `DB::transaction()`.
- **Policies & Gates**: Enforce server-side permissions on models via `app/Policies/ProjectPolicy.php`, etc.

## What to Avoid:
- **Fat Controllers**: Never put raw queries, complex business logic, or file manipulation directly inside controller action methods.
- **Database queries inside Blade templates**: All queries must be executed in controllers or view composers and passed cleanly to views.
- **Raw SQL without parameter binding**: Avoid `DB::raw()` with variable concatenation.
- **Over-engineered abstractions**: Avoid building generic repository patterns over Eloquent when Eloquent already provides an Active Record and Query Builder implementation.
- **Hardcoded URLs**: Never hardcode internal paths in views or redirect statements.
