
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartDesk | Support Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-100 text-gray-800">

    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-indigo-600">SmartDesk</h1>
                <p class="text-sm text-gray-500">Customer Support System</p>
            </div>

            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600">
                    {{ auth()->user()->name }}
                </span>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-8">

        <div class="mb-8">
            <h2 class="text-3xl font-bold">Support Tickets</h2>
            <p class="text-gray-500 mt-1">
                Manage and monitor customer support requests.
            </p>
        </div>


        <!-- Dashboard Statistics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">

            <!-- Total Tickets -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <p class="text-sm text-gray-500">Total Tickets</p>
                <h3 class="text-3xl font-bold text-gray-800 mt-2">
                    {{ $totalTickets }}
                </h3>
            </div>

            <!-- Agent Reclassified Tickets -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <p class="text-sm text-gray-500">Agent-Reclassified Tickets</p>
                <h3 class="text-3xl font-bold text-indigo-600 mt-2">
                    {{ $agentReclassifiedTickets }}
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $reclassifiedPercentage }}% of all tickets
                </p>
            </div>

            <!-- Category Statistics -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:col-span-2 lg:col-span-1">
                <p class="text-sm text-gray-500 mb-3">Tickets by Category</p>

                <div class="space-y-2">
                    @foreach ([
                        'billing' => 'Billing',
                        'technical' => 'Technical',
                        'account' => 'Account',
                        'feature_request' => 'Feature Request',
                        'other' => 'Other',
                    ] as $key => $label)

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ $label }}</span>
                            <span class="font-semibold text-gray-800">
                                {{ $categoryCounts[$key] }}
                            </span>
                        </div>

                    @endforeach
                </div>
            </div>

        </div>

        <!-- Search and Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">

            <form action="{{ route('dashboard') }}" method="GET">

                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2">
                            Search tickets
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search subject or message..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        >
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Category
                        </label>

                        <select name="category"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">

                            <option value="">All Categories</option>
                            <option value="billing" @selected($category === 'billing')>Billing</option>
                            <option value="technical" @selected($category === 'technical')>Technical</option>
                            <option value="account" @selected($category === 'account')>Account</option>
                            <option value="feature_request" @selected($category === 'feature_request')>Feature Request</option>
                            <option value="other" @selected($category === 'other')>Other</option>

                        </select>
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Priority
                        </label>

                        <select name="priority"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">

                            <option value="">All Priorities</option>
                            <option value="low" @selected($priority === 'low')>Low</option>
                            <option value="medium" @selected($priority === 'medium')>Medium</option>
                            <option value="high" @selected($priority === 'high')>High</option>
                            <option value="urgent" @selected($priority === 'urgent')>Urgent</option>

                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Status
                        </label>

                        <select name="status"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">

                            <option value="">All Statuses</option>
                            <option value="new" @selected($status === 'new')>New</option>
                            <option value="open" @selected($status === 'open')>Open</option>
                            <option value="resolved" @selected($status === 'resolved')>Resolved</option>

                        </select>
                    </div>

                </div>

                <div class="flex gap-3 mt-5">
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                        Apply Filters
                    </button>

                    <a href="{{ route('dashboard') }}"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium">
                        Reset
                    </a>
                </div>

            </form>
        </div>

        <!-- Ticket Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="font-semibold text-lg">All Tickets</h3>
                <p class="text-sm text-gray-500">
                    Showing {{ $tickets->count() }} of {{ $tickets->total() }} tickets
                </p>
            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-sm text-left">

                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-4">Reference</th>
                            <th class="px-6 py-4">Customer</th>
                            <th class="px-6 py-4">Subject</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Priority</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">AI Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        @forelse ($tickets as $ticket)

                            <tr class="hover:bg-gray-50">

                                <td class="px-6 py-4 font-semibold">
                                    <a href="{{ route('tickets.show', $ticket->id) }}"
                                        class="text-indigo-600 hover:text-indigo-800 hover:underline">
                                        {{ $ticket->reference }}
                                    </a>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-medium">
                                        {{ $ticket->name }}
                                    </div>
                                    <div class="text-gray-500 text-xs">
                                        {{ $ticket->email }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 max-w-xs">
                                    <div class="font-medium truncate">
                                        <a href="{{ route('tickets.show', $ticket->id) }}"
                                            class="hover:text-indigo-600">
                                            {{ $ticket->subject }}
                                        </a>
                                    </div>
                                    <div class="text-gray-500 text-xs truncate">
                                        {{ $ticket->summary ?? 'Waiting for AI classification...' }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    {{ $ticket->category
                                        ? ucwords(str_replace('_', ' ', $ticket->category->value))
                                        : 'Pending' }}
                                </td>

                                <td class="px-6 py-4">
                                    @if ($ticket->priority)
                                        {{ ucfirst($ticket->priority->value) }}
                                    @else
                                        Pending
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    {{ ucfirst($ticket->status->value) }}
                                </td>

                                <td class="px-6 py-4">
                                    @if ($ticket->ai_status->value === 'done')
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs">
                                            Done
                                        </span>
                                    @elseif ($ticket->ai_status->value === 'failed')
                                        <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs">
                                            Failed
                                        </span>
                                    @else
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-xs">
                                            Pending
                                        </span>
                                    @endif
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No tickets found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $tickets->links() }}
            </div>

        </div>

    </main>

</body>
</html>
