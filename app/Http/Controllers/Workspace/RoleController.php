<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

/**
 * Read-only view of the role → permission matrix (app/Enums/Role.php), so the
 * Hub Director can see exactly who can do what. Gated by can:users.manage.
 */
class RoleController extends Controller
{
    public function __invoke(): View
    {
        $counts = User::query()->selectRaw('role, COUNT(*) as aggregate')
            ->where('is_active', true)->groupBy('role')->toBase()->pluck('aggregate', 'role');

        return view('workspace.roles.index', [
            'roles' => Role::cases(),
            'groups' => collect(Permission::cases())->groupBy(fn (Permission $p) => $p->group()),
            'counts' => $counts,
        ]);
    }
}
