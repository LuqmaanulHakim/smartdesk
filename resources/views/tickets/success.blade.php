<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Submitted | SmartDesk</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center">

    <div class="w-full max-w-lg bg-white rounded-xl shadow-lg p-8 mx-6 text-center">

        <div class="mb-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                <span class="text-green-600 text-3xl">✓</span>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-gray-900">
            Ticket Submitted Successfully!
        </h1>

        <p class="text-gray-500 mt-3">
            Thank you for contacting SmartDesk. Your support ticket has been received.
        </p>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 mt-8">
            <p class="text-sm text-gray-500">
                Your Ticket Reference
            </p>

            <p class="text-2xl font-bold text-blue-600 mt-2">
                {{ $ticket->reference }}
            </p>
        </div>

        <p class="text-sm text-gray-500 mt-6">
            Please keep this reference number for future enquiries.
        </p>

        <a
            href="{{ route('tickets.create') }}"
            class="inline-block mt-8 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition"
        >
            Submit Another Ticket
        </a>

    </div>

</body>
</html>