<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicationController extends Controller
{
    public function index(Request $request): View
    {
        $publications = Publication::with('project')
            ->latest('publish_date')
            ->paginate(12);

        return view('publications.index', compact('publications'));
    }

    public function apiIndex(): JsonResponse
    {
        $publications = Publication::with('project:id,title,category')
            ->latest('publish_date')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Publications retrieved successfully',
            'data' => $publications,
        ]);
    }
}
