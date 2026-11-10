<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Http\Request;

class AgentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');
        $priority = $request->query('priority');
        $status = $request->query('status');
        $search = $request->query('search');

        // Existing ticket filtering
        $query = Ticket::query();

        if (in_array($category, array_column(TicketCategory::cases(), 'value'))) {
            $query->where('category', $category);
        }

        if (in_array($priority, array_column(TicketPriority::cases(), 'value'))) {
            $query->where('priority', $priority);
        }

        if (in_array($status, array_column(TicketStatus::cases(), 'value'))) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();

        // Dashboard statistics: ticket counts by category
        $categoryCounts = Ticket::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $categoryCounts = collect(TicketCategory::cases())
            ->mapWithKeys(function ($category) use ($categoryCounts) {
                return [
                    $category->value => $categoryCounts->get($category->value, 0)
                ];
            });

        // Total number of tickets
        $totalTickets = Ticket::count();

        // Count tickets where an agent manually changed category or priority
        // Each ticket is counted only once.
        $agentReclassifiedTickets = Ticket::where(function ($query) {
            $query->where('category_source', 'agent')
                ->orWhere('priority_source', 'agent');
        })->count();

        // Calculate percentage safely
        $reclassifiedPercentage = $totalTickets > 0
            ? round(($agentReclassifiedTickets / $totalTickets) * 100, 1)
            : 0;

        return view('dashboard', compact(
            'tickets',
            'category',
            'priority',
            'status',
            'search',
            'categoryCounts',
            'totalTickets',
            'agentReclassifiedTickets',
            'reclassifiedPercentage'
        ));
    }
}
