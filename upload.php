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
    <aside class="w-64 bg-gray-800 text-white p-6 hidden md:flex flex-col justify-between shadow-xl">
        <div>
            <h2 class="text-2xl font-bold mb-10">Sentiment Analyzer</h2>
            <nav class="space-y-4 text-sm">
                <a href="welcome.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">📊 Dashboard</a>
                <a href="record_audio.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">🎤 Record Audio</a>
                <a href="upload.php" class="flex items-center gap-2 px-3 py-2 rounded bg-gray-700">📝 Upload Audio</a>
                <a href="history.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">🕓 Call History</a>
                <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-red-600 text-red-100 mt-10">🚪 Logout</a>
            </nav>
        </div>
        <div class="text-sm text-gray-300 mt-10">Logged in as <?= htmlspecialchars($_SESSION['user']) ?></div>
    </aside>

    <main class="flex-1 p-6 md:p-10 space-y-10">
        <header>
            <h1 class="text-3xl font-bold mb-2 animate-slideIn">Analyze Audio Sentiment</h1>
            <p class="text-gray-600 animate-slideIn">Upload an audio file to analyze its sentiment.</p>
        </header>

        <div class="bg-white rounded-xl shadow p-6 max-w-2xl animate-slideIn">
            <form method="POST" action="handle_upload.php" enctype="multipart/form-data" class="space-y-6" id="analysisForm">
                <div>
                    <label for="caller" class="block text-gray-700 mb-1">Caller Name (Optional)</label>
                    <input type="text" name="caller" id="caller" placeholder="Enter caller name" class="w-full px-4 py-3 rounded border focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="audio_file" class="block text-gray-700 mb-1">Audio File</label>
                    <input type="file" name="audio_file" id="audio_file" accept="audio/*" class="w-full px-4 py-3 rounded border focus:ring-2 focus:ring-blue-500">
                    <p class="text-sm text-gray-500 mt-1">Supported formats: MP3, WAV, M4A</p>
                </div>

                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded transition">Analyze Audio</button>
            </form>
        </div>

        <footer class="text-center text-gray-500 text-xs pt-10">
            © 2025 Helpdesk AI · Secure processing in place.
        </footer>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('analysisForm');
            const audioFile = document.getElementById('audio_file');

            form.addEventListener('submit', function(e) {
                if (!audioFile.files.length) {
                    e.preventDefault();
                    alert('Please select an audio file');
                    return;
                }
            });
        });
    </script>
</body>
</html>