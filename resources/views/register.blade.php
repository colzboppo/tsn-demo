<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tic-Tac-Toe Lobby</title>
        @vite(['resources/css/app.css', 'resources/js/app.js']) <!-- Include Tailwind and Vue -->
    </head>
    <body class="bg-gray-100 flex items-center justify-center h-screen">
        <div class="w-full max-w-md">
            <div class="bg-white shadow-md rounded-lg p-6 mx-auto">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Register for Tic-Tac-Toe</h2>
                <form action="/player" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Your Name</label>
                        <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Enter your name" required>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Register
                    </button>
                </form>
            </div>
        </div>
    </body>
</html>