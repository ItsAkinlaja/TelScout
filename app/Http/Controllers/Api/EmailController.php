<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomationSettings;
use App\Models\EmailMessage;
use App\Models\EmailEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = EmailMessage::with(['opportunity.company', 'opportunity.job'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $emails = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($emails);
    }

    public function show(Request $request, EmailMessage $email): JsonResponse
    {
        $this->authorize('view', $email);

        return response()->json($email->load(['opportunity.company', 'opportunity.job', 'events']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'opportunity_id'  => 'nullable|exists:opportunities,id',
            'recipient_email' => 'required|email',
            'recipient_name'  => 'nullable|string',
            'subject'         => 'required|string|max:500',
            'body_text'       => 'required|string',
            'body_html'       => 'nullable|string',
        ]);

        $email = EmailMessage::create(array_merge($data, [
            'user_id' => $request->user()->id,
            'status'  => 'draft',
        ]));

        return response()->json($email, 201);
    }

    public function update(Request $request, EmailMessage $email): JsonResponse
    {
        $this->authorize('update', $email);

        $data = $request->validate([
            'recipient_email' => 'sometimes|email',
            'recipient_name'  => 'sometimes|nullable|string',
            'subject'         => 'sometimes|string|max:500',
            'body_text'       => 'sometimes|string',
            'body_html'       => 'sometimes|nullable|string',
        ]);

        $email->update($data);

        return response()->json($email);
    }

    public function approve(Request $request, EmailMessage $email): JsonResponse
    {
        $this->authorize('update', $email);

        if (!in_array($email->status, ['draft', 'rejected'])) {
            return response()->json(['message' => 'Email cannot be approved in its current state.'], 422);
        }

        $email->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        EmailEvent::create([
            'email_message_id' => $email->id,
            'event_type'       => 'approved',
            'description'      => 'Email approved for sending.',
        ]);

        return response()->json(['message' => 'Email approved.', 'email' => $email]);
    }

    public function reject(Request $request, EmailMessage $email): JsonResponse
    {
        $this->authorize('update', $email);

        $email->update(['status' => 'rejected']);

        return response()->json(['message' => 'Email rejected.', 'email' => $email]);
    }

    public function send(Request $request, EmailMessage $email): JsonResponse
    {
        $this->authorize('update', $email);

        if ($email->status !== 'approved') {
            return response()->json(['message' => 'Email must be approved before sending.'], 422);
        }

        // Safety checks
        if (empty($email->recipient_email)) {
            return response()->json(['message' => 'No recipient email address.'], 422);
        }

        // Domain blacklist — never cold-email these regardless of user config
        if ($this->isBlacklistedDomain($email->recipient_email)) {
            return response()->json(['message' => 'Sending to this domain is not allowed.'], 422);
        }

        $settings    = AutomationSettings::where('user_id', $request->user()->id)->first();
        $userId      = $request->user()->id;

        // Daily limit
        $dailyLimit = $settings?->daily_send_limit ?? 10;
        $sentToday  = EmailMessage::where('user_id', $userId)
            ->where('status', 'sent')
            ->whereDate('sent_at', today())
            ->count();

        if ($sentToday >= $dailyLimit) {
            return response()->json(['message' => "Daily send limit of {$dailyLimit} reached."], 429);
        }

        // Hourly limit — prevents bursting the full daily quota in minutes
        $hourlyLimit = $settings?->hourly_send_limit ?? 5;
        $sentThisHour = EmailMessage::where('user_id', $userId)
            ->where('status', 'sent')
            ->where('sent_at', '>=', now()->subHour())
            ->count();

        if ($sentThisHour >= $hourlyLimit) {
            return response()->json(['message' => "Hourly send limit of {$hourlyLimit} reached. Try again shortly."], 429);
        }

        // Check duplicate recipient
        $alreadySent = EmailMessage::where('user_id', $userId)
            ->where('recipient_email', $email->recipient_email)
            ->where('status', 'sent')
            ->exists();

        if ($alreadySent) {
            return response()->json(['message' => 'A message has already been sent to this recipient.'], 422);
        }

        // Dispatch to queue
        \App\Jobs\SendEmailJob::dispatch($email->id, $request->user()->id);

        $email->update(['status' => 'queued']);

        EmailEvent::create([
            'email_message_id' => $email->id,
            'event_type'       => 'queued',
            'description'      => 'Email queued for sending.',
        ]);

        return response()->json(['message' => 'Email queued for sending.', 'email' => $email]);
    }

    /**
     * Domains that should never receive cold outreach from this system.
     * Includes major ESPs, role-based abuse addresses, and legal traps.
     */
    private function isBlacklistedDomain(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1));

        $blacklisted = [
            // Generic inbox providers — people don't want cold-emails here
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com',
            'icloud.com', 'me.com', 'aol.com', 'protonmail.com',
            // Role/abuse addresses that will hurt sender reputation
            'example.com', 'example.org', 'test.com', 'mailinator.com',
            'guerrillamail.com', 'tempmail.com', 'throwam.com',
        ];

        return in_array($domain, $blacklisted, strict: true);
    }

    public function destroy(Request $request, EmailMessage $email): JsonResponse
    {
        $this->authorize('delete', $email);

        if ($email->status === 'sent') {
            return response()->json(['message' => 'Cannot delete a sent email.'], 422);
        }

        $email->delete();

        return response()->json(['message' => 'Email deleted.']);
    }
}
