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

            // First try to disable foreign key checks
            $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Clear the calls table
            $stmt = $conn->prepare("TRUNCATE TABLE calls");
            $stmt->execute();
            
            // Re-enable foreign key checks
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1");

            $message = "Call history has been cleared successfully.";
        } catch(PDOException $e) {
            $error = "Error clearing history: " . $e->getMessage();
            // Log the error
            error_log("Database error: " . $e->getMessage());
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
    <title>Clear History | Sentiment Analyzer</title>
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

    <main class="flex-1 p-6 md:p-10">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6">Clear Call History</h1>

                <?php if ($message): ?>
                    <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="mb-6">
                    <p class="text-gray-600 mb-4">This action will permanently delete all call history records. This cannot be undone.</p>
                    <p class="text-red-600 font-medium">Are you sure you want to proceed?</p>
                </div>

                <form method="POST" action="clear_history.php" class="space-y-4">
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="confirm" name="confirm" value="yes" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                        <label for="confirm" class="ml-2 block text-sm text-gray-700">
                            I understand that this action cannot be undone and I want to proceed
                        </label>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg transition">
                            Yes, Clear All History
                        </button>
                        <a href="history.php" class="text-gray-600 hover:text-gray-800">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html> 