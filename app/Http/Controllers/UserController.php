<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Mail\PasswordChangedMail;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::withCount('projects')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%");
                });
            })
            ->when($request->role && $request->role !== 'all', function ($q, $role) {
                $q->where('role', $role);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.created',
            'description' => "Provisioned staff account for {$user->name} ({$user->role})",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('status', "User account for {$user->name} successfully provisioned.");
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $passwordChanged = !empty($data['password']);
        if ($passwordChanged) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if ($passwordChanged) {
            try {
                Mail::to($user->email)->queue(new PasswordChangedMail($user));
            } catch (\Throwable $e) {}
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.updated',
            'description' => "Updated credentials/role for {$user->name}",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('status', "User {$user->name} updated successfully.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['error' => 'Self-deletion is prohibited for active security sessions.']);
        }

        $name = $user->name;
        $user->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.deleted',
            'description' => "De-provisioned user account for {$name}",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('status', "User {$name} has been removed.");
    }

    public function activityLogs(): View
    {
        $logs = ActivityLog::with('user')
            ->latest()
            ->paginate(25);

        return view('admin.activity-logs', compact('logs'));
    }
}
