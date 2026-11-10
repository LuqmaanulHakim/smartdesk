<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Models\Ticket;
use App\Enums\TicketStatus;
use App\Enums\AiStatus;
use Illuminate\Support\Str;
use App\Jobs\ClassifyTicketJob;

class TicketController extends Controller
{
    // Show the public ticket submission form
    public function create()
    {
        return view('tickets.create');
    }

    // Store a new customer ticket
    public function store(StoreTicketRequest $request)
    {
        $ticket = Ticket::create([
            'reference' => 'SD-' . str_pad(
                (string) ((Ticket::max('id') ?? 0) + 1),
                6,
                '0',
                STR_PAD_LEFT
            ),

            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,

            'status' => TicketStatus::NEW,
            'ai_status' => AiStatus::PENDING,
        ]);

        // Dispatch the classification job
        ClassifyTicketJob::dispatch($ticket);

        return redirect()
            ->route('tickets.success', $ticket->id)
            ->with('reference', $ticket->reference);
    }

    // Show ticket submission success page
    public function success(Ticket $ticket)
    {
        return view('tickets.success', compact('ticket'));
    }
}