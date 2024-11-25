<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tic-Tac-Toe Lobby</title>
    @vite(['resources/css/app.css', 'resources/js/app.js']) <!-- Include Tailwind and Vue -->
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-4xl">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Tic-Tac-Toe Lobby</h2>
            <!-- Vue Lobby Component -->
            <div id="app">
                <lobby-component :player="@json($lobby)"></lobby-component>
            </div>
        </div>
    </div>
    @vite('resources/js/app.js') <!-- Include compiled JS -->
</body>
</html>