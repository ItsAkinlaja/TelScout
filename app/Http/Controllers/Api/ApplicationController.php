<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $applications = Application::with([
            'opportunity.job',
            'opportunity.company',
            'opportunity.emails',
            'notes',
        ])
        ->where('user_id', $request->user()->id)
        ->orderBy('status')
        ->orderBy('sort_order')
        ->get();

        // Group by status for Kanban
        $kanban = $applications->groupBy('status');

        return response()->json([
            'applications' => $applications,
            'kanban'       => $kanban,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'opportunity_id' => 'required|exists:opportunities,id',
            'status'         => 'sometimes|in:discovered,shortlisted,contacted,follow_up,replied,interview,offer,rejected,closed',
        ]);

        $opportunity = Opportunity::findOrFail($data['opportunity_id']);
        if ($opportunity->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $application = Application::firstOrCreate(
            ['opportunity_id' => $data['opportunity_id']],
            array_merge($data, ['user_id' => $request->user()->id])
        );

        return response()->json($application->load(['opportunity.job', 'opportunity.company', 'notes']), 201);
    }

    public function show(Request $request, Application $application): JsonResponse
    {
        if ($application->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json($application->load([
            'opportunity.job',
            'opportunity.company',
            'opportunity.emails.events',
            'notes',
        ]));
    }

    public function update(Request $request, Application $application): JsonResponse
    {
        if ($application->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate([
            'status'          => 'sometimes|in:discovered,shortlisted,contacted,follow_up,replied,interview,offer,rejected,closed',
            'sort_order'      => 'sometimes|integer',
            'notes'           => 'sometimes|nullable|string',
            'interview_dates' => 'sometimes|nullable|array',
            'applied_at'      => 'sometimes|nullable|date',
        ]);

        $application->update($data);

        // Sync opportunity status
        if (isset($data['status'])) {
            $application->opportunity()->update(['status' => $data['status']]);
        }

        return response()->json($application->load(['opportunity.job', 'opportunity.company', 'notes']));
    }

    public function addNote(Request $request, Application $application): JsonResponse
    {
        if ($application->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate(['content' => 'required|string']);
        $note = $application->notes()->create($data);

        return response()->json($note, 201);
    }
}
