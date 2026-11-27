<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\StoreUserRequest;
use App\Http\Requests\Workspace\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $role = Role::tryFrom((string) $request->query('role'));

        return view('workspace.users.index', [
            'users' => User::query()
                ->staff()
                ->when($role, fn ($q) => $q->where('role', $role))
                ->search($request->query('search'), ['name', 'email', 'department'])
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'roles' => Role::staff(),
            'filters' => ['role' => $role?->value, 'search' => $request->query('search')],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('workspace.users.create', ['user' => new User, 'roles' => Role::staff()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $user = $this->users->create($request->validated());

        return redirect()->route('workspace.users.index')->with('status', "Account created for {$user->name}. Share the temporary password with them directly.");
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('workspace.users.edit', ['user' => $user, 'roles' => Role::staff()]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $this->users->update($user, $request->validated(), $request->user());

        return redirect()->route('workspace.users.index')->with('status', "{$user->name}'s account was updated.");
    }

    public function toggle(User $user): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        $this->users->setActive($user, ! $user->is_active);

        return back()->with('status', $user->is_active ? "{$user->name} can sign in again." : "{$user->name} has been deactivated and signed out.");
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $this->users->delete($user);

        return redirect()->route('workspace.users.index')->with('status', "{$user->name}'s account was deleted.");
    }
}
