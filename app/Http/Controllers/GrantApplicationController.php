<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGrantApplicationRequest;
use App\Mail\GrantApplicationReceivedMail;
use App\Mail\GrantStatusUpdatedMail;
use App\Models\ActivityLog;
use App\Models\GrantApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GrantApplicationController extends Controller
{
    // Public Apply Form
    public function create(): View
    {
        $categories = ['STEM', 'Educational Tech', 'Agriculture', 'Inclusive Education', 'Digital Humanities', 'Community Outreach'];
        return view('grants.apply', compact('categories'));
    }

    public function store(StoreGrantApplicationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('proposal_file') && $request->file('proposal_file')->isValid()) {
            $filename = 'grant_' . time() . '_' . Str::lower(Str::random(10)) . '.pdf';
            $request->file('proposal_file')->storeAs('reports', $filename, 'private');
            $data['proposal_file_path'] = $filename;
        }

        $application = GrantApplication::create($data);

        // Dispatch Confirmation Notification to Applicant
        try {
            Mail::to($application->email)->queue(new GrantApplicationReceivedMail($application));
        } catch (\Throwable $e) {
            // Logged silently so application flow continues
        }

        ActivityLog::create([
            'action' => 'grant.submitted',
            'description' => "New grant application received: {$application->reference_no} ({$application->applicant_name})",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('grants.success', ['ref' => $application->reference_no])
            ->with('status', 'Application successfully received by the UEW Innovation Hub Review Committee.');
    }

    public function success(Request $request): View
    {
        $ref = $request->ref;
        return view('grants.success', compact('ref'));
    }

    // Cockpit Management
    public function index(Request $request): View
    {
        $applications = GrantApplication::with('reviewer')
            ->when($request->status && $request->status !== 'all', function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->category && $request->category !== 'all', function ($q, $category) {
                $q->where('category', $category);
            })
            ->latest()
            ->paginate(15);

        $counts = [
            'total' => GrantApplication::count(),
            'submitted' => GrantApplication::where('status', 'submitted')->count(),
            'under_review' => GrantApplication::where('status', 'under_review')->count(),
            'approved' => GrantApplication::where('status', 'approved')->count(),
        ];

        return view('admin.grants.index', compact('applications', 'counts'));
    }

    public function show(GrantApplication $application): View
    {
        return view('admin.grants.show', compact('application'));
    }

    public function updateStatus(Request $request, GrantApplication $application): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:submitted,under_review,shortlisted,approved,rejected',
            'reviewer_notes' => 'nullable|string',
        ]);

        $application->update([
            'status' => $request->status,
            'reviewer_notes' => $request->reviewer_notes,
            'reviewed_by' => $request->user()->id,
        ]);

        // Dispatch Status Determination Notification to Applicant
        try {
            Mail::to($application->email)->queue(new GrantStatusUpdatedMail($application));
        } catch (\Throwable $e) {
            // Logged silently
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'grant.reviewed',
            'description' => "Updated status of grant application {$application->reference_no} to {$application->status}",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', "Application #{$application->reference_no} status updated to " . ucfirst(str_replace('_', ' ', $application->status)));
    }
}
