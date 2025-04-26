<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "auth.php";
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentiment Analysis | Helpdesk AI</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slideIn {
            animation: slideIn 0.5s ease-out both;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans flex min-h-screen">
    <aside class="w-64 bg-blue-800 text-white p-6 hidden md:flex flex-col justify-between shadow-xl">
        <div>
            <h2 class="text-2xl font-bold mb-10">📞 Helpdesk AI</h2>
            <nav class="space-y-4 text-sm">
                <a href="index.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-blue-600">📊 Dashboard</a>
                <a href="upload.php" class="flex items-center gap-2 px-3 py-2 rounded bg-blue-700">📝 Analyze Text</a>
                <a href="history.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-blue-600">🕓 Call History</a>
                <a href="contact.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-blue-600">📬 Contact Us</a>
                <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-red-600 text-red-100 mt-10">🚪 Logout</a>
            </nav>
        </div>
        <div class="text-sm text-blue-200 mt-10">Logged in as <?= htmlspecialchars($_SESSION['user']) ?></div>
    </aside>

    <main class="flex-1 p-6 md:p-10 space-y-10">
        <header>
            <h1 class="text-3xl font-bold mb-2 animate-slideIn">Analyze Text Sentiment</h1>
            <p class="text-gray-600 animate-slideIn">Enter the transcribed text below to analyze its sentiment.</p>
        </header>

        <div class="bg-white rounded-xl shadow p-6 max-w-2xl animate-slideIn">
            <form method="POST" action="handle_upload.php" class="space-y-6">
                <div>
                    <label for="caller" class="block text-gray-700 mb-1">Caller Name (Optional)</label>
                    <input type="text" name="caller" id="caller" placeholder="Enter caller name" class="w-full px-4 py-3 rounded border focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="transcribed_text" class="block text-gray-700 mb-1">Transcribed Text</label>
                    <textarea name="transcribed_text" id="transcribed_text" rows="5" required placeholder="Paste or type the transcribed text here" class="w-full px-4 py-3 rounded border focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded transition">Analyze Sentiment</button>
            </form>
        </div>

        <footer class="text-center text-gray-500 text-xs pt-10">
            © 2025 Helpdesk AI · Secure processing in place.
        </footer>
    </main>
</body>
</html>