<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit a Ticket | SmartDesk</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100">

    <div class="max-w-2xl mx-auto px-6 py-12">

        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-gray-900">SmartDesk</h1>
            <p class="text-gray-500 mt-2">How can we help you today?</p>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-8">

            <h2 class="text-xl font-semibold text-gray-900 mb-2">
                Submit a Support Ticket
            </h2>

            <p class="text-sm text-gray-500 mb-8">
                Please provide the details below. Our support team will get back to you.
            </p>

            <form method="POST" action="{{ route('tickets.store') }}">
                @csrf

                <!-- Name -->
                <div class="mb-5">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter your full name"
                    >

                    @error('name')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div class="mb-5">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="example@email.com"
                    >

                    @error('email')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Subject -->
                <div class="mb-5">
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                        Subject
                    </label>

                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        value="{{ old('subject') }}"
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Briefly describe your issue"
                    >

                    @error('subject')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Message -->
                <div class="mb-6">
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                        Message
                    </label>

                    <textarea
                        id="message"
                        name="message"
                        rows="6"
                        required
                        minlength="20"
                        maxlength="5000"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Please explain your issue in detail (20–5,000 characters)"
                    >{{ old('message') }}</textarea>

                    @error('message')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror

                    <p class="text-xs text-gray-400 mt-2">
                        Minimum 20 characters, maximum 5,000 characters.
                    </p>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition"
                >
                    Submit Ticket
                </button>

            </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-8">
            SmartDesk Support Ticket Management System
        </p>

    </div>

</body>
</html>
