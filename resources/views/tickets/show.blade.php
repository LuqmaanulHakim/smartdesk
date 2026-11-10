
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ticket->reference }} | SmartDesk</title>
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

    <main class="max-w-5xl mx-auto px-6 py-8">

        <!-- Back button -->
        <a href="{{ route('dashboard') }}"
            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
            ← Back to Dashboard
        </a>

        <!-- Ticket heading -->
        <div class="mt-6 mb-6">
            <p class="text-sm text-gray-500">{{ $ticket->reference }}</p>
            <h2 class="text-3xl font-bold mt-1">
                {{ $ticket->subject }}
            </h2>
            <p class="text-sm text-gray-500 mt-2">
                Submitted {{ $ticket->created_at->format('d M Y, h:i A') }}
            </p>
        </div>

        <!-- Customer Information -->
        <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Customer Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Customer Name</p>
                    <p class="font-medium">{{ $ticket->name }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Email Address</p>
                    <p class="font-medium">{{ $ticket->email }}</p>
                </div>
            </div>
        </section>

        <!-- Customer Message -->
        <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Customer Message</h3>

            <div class="bg-gray-50 rounded-lg p-4 whitespace-pre-line leading-relaxed">{{ $ticket->message }}</div>
        </section>
        
        <!-- AI Classification -->
        <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">

            <h3 class="text-lg font-semibold mb-4">AI Classification</h3>

            @if ($ticket->ai_status->value === 'done')

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">

                    <div>
                        <p class="text-sm text-gray-500">Category</p>
                        <p class="font-semibold">
                            {{ ucwords(str_replace('_', ' ', $ticket->category->value)) }}
                        </p>
                        <p class="text-xs text-gray-400">
                            Source: {{ ucfirst($ticket->category_source?->value ?? 'N/A') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Priority</p>
                        <p class="font-semibold">
                            {{ ucfirst($ticket->priority->value) }}
                        </p>
                        <p class="text-xs text-gray-400">
                            Source: {{ ucfirst($ticket->priority_source?->value ?? 'N/A') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Sentiment</p>
                        <p class="font-semibold">
                            {{ ucfirst($ticket->sentiment->value) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Language</p>
                        <p class="font-semibold">{{ strtoupper($ticket->language ?? 'N/A') }}</p>
                    </div>

                </div>

                <div class="mt-5 pt-5 border-t border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">AI Summary</p>
                    <p>{{ $ticket->summary }}</p>
                </div>

            @elseif ($ticket->ai_status->value === 'failed')

                <p class="text-red-600">AI classification failed. Please retry.</p>

            @else

                <p class="text-yellow-600">AI classification is still processing.</p>

            @endif


            <!-- Manual Classification Update -->
            @if ($ticket->ai_status->value === 'done')

                <div class="mt-6 border-t border-gray-200 pt-5">

                    <h4 class="text-md font-semibold mb-3">
                        Update Classification
                    </h4>

                    <form action="{{ route('tickets.updateClassification', $ticket->id) }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <div>
                                <label class="block text-sm font-medium mb-2">Category</label>

                                <select name="category"
                                    class="w-full border border-gray-300 rounded-lg p-3">

                                    @foreach (\App\Enums\TicketCategory::cases() as $category)
                                        <option value="{{ $category->value }}"
                                            @selected($ticket->category === $category)>
                                            {{ ucwords(str_replace('_', ' ', $category->value)) }}
                                        </option>
                                    @endforeach

                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">Priority</label>

                                <select name="priority"
                                    class="w-full border border-gray-300 rounded-lg p-3">

                                    @foreach (\App\Enums\TicketPriority::cases() as $priority)
                                        <option value="{{ $priority->value }}"
                                            @selected($ticket->priority === $priority)>
                                            {{ ucfirst($priority->value) }}
                                        </option>
                                    @endforeach

                                </select>
                            </div>

                        </div>

                        <button type="submit"
                            class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                            Update Classification
                        </button>

                    </form>

                </div>

            @endif


            <!-- AI Processing Status -->
            <div class="mt-6 border-t border-gray-200 pt-5">

                <h4 class="text-sm font-semibold mb-3">AI Processing Status</h4>

                @if ($ticket->ai_status->value === 'pending')

                    <span class="text-yellow-700 bg-yellow-100 px-3 py-1 rounded-full text-sm">
                        Pending
                    </span>

                @elseif ($ticket->ai_status->value === 'done')

                    <span class="inline-block text-green-700 bg-green-100 px-3 py-1 rounded-full text-sm font-medium">
                        Completed
                    </span>

                @elseif ($ticket->ai_status->value === 'failed')

                    <span class="text-red-700 bg-red-100 px-3 py-1 rounded-full text-sm">
                        Failed
                    </span>

                    <form action="{{ route('tickets.retryClassification', $ticket->id) }}"
                        method="POST"
                        class="mt-3">

                        @csrf

                        <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                            Retry AI Classification
                        </button>

                    </form>

                @endif

            </div>

        </section>


        <!-- Ticket Status -->
        <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-3">Ticket Status</h3>

            <span class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">
                {{ ucfirst($ticket->status->value) }}
            </span>
        </section>

        <!-- Reply Section -->
            <section class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">

                <h3 class="text-lg font-semibold mb-4">Agent Reply</h3>

                <!-- Success Message -->
                @if (session('success'))
                    <p class="text-sm text-green-700 mb-4">
                        {{ session('success') }}
                    </p>
                @endif

                <!-- Generate AI Reply -->
                <form action="{{ route('tickets.generateReply', $ticket->id) }}" method="POST">
                    @csrf

                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                        Generate AI Reply
                    </button>
                </form>

                <!-- Save Reply -->
                <form action="{{ route('tickets.saveReply', $ticket->id) }}" method="POST" class="mt-5">
                    @csrf

                    <label class="block text-sm font-medium mb-2">
                        Edit Your Reply
                    </label>

                    <textarea
                        name="reply"
                        rows="7"
                        required
                        maxlength="10000"
                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        placeholder="Generate an AI reply or write your own reply here..."
                    >{{ old('reply', session('generated_reply', $ticket->reply)) }}</textarea>

                    @error('reply')
                        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                        class="mt-4 bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                        Save Reply
                    </button>
                </form>
            </section>

    </main>

</body>
</html>
