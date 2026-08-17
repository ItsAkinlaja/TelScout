<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FollowUp::with(['opportunity.job', 'opportunity.company', 'emailMessage'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'pending');
        }

        $followUps = $query->orderBy('scheduled_at')->paginate(20);

        return response()->json($followUps);
    }

    public function complete(Request $request, FollowUp $followUp): JsonResponse
    {
        if ($followUp->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $followUp->update(['status' => 'completed']);

        return response()->json(['message' => 'Follow-up marked complete.', 'follow_up' => $followUp]);
    }

    public function cancel(Request $request, FollowUp $followUp): JsonResponse
    {
        if ($followUp->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate([
            'reason' => 'sometimes|string|max:255',
        ]);

        $followUp->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $data['reason'] ?? 'manually_cancelled',
        ]);

        return response()->json(['message' => 'Follow-up cancelled.', 'follow_up' => $followUp]);
    }
}
