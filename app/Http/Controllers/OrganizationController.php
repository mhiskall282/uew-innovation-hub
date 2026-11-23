<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $organizations = Organization::withCount('projects')
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(15);

        return view('organizations.index', compact('organizations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:organizations,name'],
            'type' => ['required', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        $org = Organization::create($validated);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'organization.created',
            'description' => "Registered partner organization: {$org->name}",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('organizations.index')
            ->with('status', "Partner organization '{$org->name}' successfully registered.");
    }
}
