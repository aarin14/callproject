<?php
session_start();
require_once "auth.php";

// Check if user is admin
if ($_SESSION['user'] !== 'admin') {
    header('Location: history.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        try {
            // Database connection
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "sentimentanalyser";

            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Clear the calls table
            $stmt = $conn->prepare("TRUNCATE TABLE calls");
            $stmt->execute();

            $message = "Database has been cleared successfully.";
        } catch(PDOException $e) {
            $error = "Error clearing database: " . $e->getMessage();
        }
    } else {
        $error = "Please confirm the action by checking the confirmation box.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clear Database | Sentiment Analyzer</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 font-sans flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-800 text-white p-6 hidden md:flex flex-col justify-between shadow-xl">
        <div>
            <h2 class="text-2xl font-bold mb-10">Sentiment Analyzer</h2>
            <nav class="space-y-4 text-sm">
                <a href="welcome.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">📊 Dashboard</a>
                <a href="record_audio.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">🎤 Record Audio</a>
                <a href="upload.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">📝 Upload Audio</a>
                <a href="history.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">🕓 Call History</a>
                <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-red-600 text-red-100 mt-10">🚪 Logout</a>
            </nav>
        </div>
        <div class="text-sm text-gray-300 mt-10">Logged in as <?= htmlspecialchars($_SESSION['user']) ?></div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold mb-8">Clear Database</h1>
            
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold mb-4">⚠️ Warning</h2>
                    <p class="text-gray-600 mb-4">
                        This action will permanently delete all call records from the database. This operation cannot be undone.
                    </p>
                    <p class="text-gray-600">
                        Please make sure you have backed up any important data before proceeding.
                    </p>
                </div>

                <form method="POST" class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="confirm" name="confirm" value="yes" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                        <label for="confirm" class="ml-2 block text-sm text-gray-700">
                            I understand that this action cannot be undone and I want to proceed
                        </label>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="history.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                            Cancel
                        </a>
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                            Clear Database
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html> 