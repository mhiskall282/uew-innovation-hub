<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    private const AREAS = ['auth', 'project', 'grant', 'post', 'publication', 'organization', 'user'];

    public function __invoke(Request $request): View
    {
        $area = in_array($request->query('area'), self::AREAS, true) ? $request->query('area') : null;
        $userId = ctype_digit((string) $request->query('user')) ? (int) $request->query('user') : null;

        return view('workspace.audit.index', [
            'logs' => ActivityLog::query()
                ->with('user:id,name')
                ->when($area, fn ($q) => $q->where('action', 'like', "{$area}.%"))
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
                ->search($request->query('search'), ['description', 'action', 'ip_address'])
                ->latest('id')
                ->paginate(30)
                ->withQueryString(),
            'areas' => self::AREAS,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filters' => ['area' => $area, 'user' => $userId, 'search' => $request->query('search')],
        ]);
    }
}
