<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Contracts\TicketAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\ClassificationSource;
use Illuminate\Validation\Rule;
use App\Enums\AiStatus;
use App\Jobs\ClassifyTicketJob;

class TicketDetailController extends Controller
{
    public function show(Ticket $ticket)
    {
        return view('tickets.show', compact('ticket'));
    }

    
    public function generateReply(
        Ticket $ticket,
        TicketAiService $aiService
    ) {
        $cacheKey = "ticket:{$ticket->id}:draft-reply";

        $reply = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($ticket, $aiService) {
                return $aiService->draftReply($ticket);
            }
        );

        return back()->with('generated_reply', $reply);
    }

    
    public function saveReply(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:10000'],
        ]);

        $ticket->update([
            'reply' => $validated['reply'],
        ]);

        Cache::forget("ticket:{$ticket->id}:draft-reply");

        return back()->with('success', 'Reply saved successfully.');
    }

    public function retryClassification(Ticket $ticket)
    {
        // Only allow retry if AI classification previously failed
        if ($ticket->ai_status !== AiStatus::FAILED) {
            return back()->with('success', 'This ticket does not require an AI retry.');
        }

        // Change status back to pending
        $ticket->update([
            'ai_status' => AiStatus::PENDING,
        ]);

        // Dispatch the classification job again
        ClassifyTicketJob::dispatch($ticket);

        return back()->with('success', 'AI classification retry has been queued.');
    }

    
    public function updateClassification(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
        ]);

        $categoryChanged = $ticket->category?->value !== $validated['category'];
        $priorityChanged = $ticket->priority?->value !== $validated['priority'];

        $updates = [
            'category' => $validated['category'],
            'priority' => $validated['priority'],
        ];

        if ($categoryChanged) {
            $updates['category_source'] = ClassificationSource::AGENT;
        }

        if ($priorityChanged) {
            $updates['priority_source'] = ClassificationSource::AGENT;
        }

        $ticket->update($updates);

        if ($categoryChanged || $priorityChanged) {
            Cache::forget("ticket:{$ticket->id}:draft-reply");
        }

        return back()->with('success', 'Classification updated successfully.');
    }
}
