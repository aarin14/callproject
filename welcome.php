<?php
session_start();
require_once "auth.php";

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sentimentanalyser";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get total calls count
    $stmt = $conn->query("SELECT COUNT(*) FROM calls");
    $total_calls = $stmt->fetchColumn();

    // Get sentiment distribution
    $stmt = $conn->query("SELECT sentiment, COUNT(*) as count FROM calls GROUP BY sentiment");
    $sentiment_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent calls
    $stmt = $conn->query("SELECT * FROM calls ORDER BY timestamp DESC LIMIT 5");
    $recent_calls = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate average sentiment
    $positive_count = 0;
    $negative_count = 0;
    $neutral_count = 0;
    foreach ($sentiment_data as $row) {
        if ($row['sentiment'] === 'positive') $positive_count = $row['count'];
        if ($row['sentiment'] === 'negative') $negative_count = $row['count'];
        if ($row['sentiment'] === 'neutral') $neutral_count = $row['count'];
    }
    $total = $positive_count + $negative_count + $neutral_count;
    $avg_sentiment = $total > 0 ? round(($positive_count - $negative_count) / $total * 100) : 0;

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sentiment Analyzer</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card-hover:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .sentiment-positive { background-color: #dcfce7; color: #166534; }
        .sentiment-negative { background-color: #fee2e2; color: #991b1b; }
        .sentiment-neutral { background-color: #f3f4f6; color: #374151; }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-800 text-white p-6 hidden md:flex flex-col justify-between shadow-xl">
        <div>
            <h2 class="text-2xl font-bold mb-10">Sentiment Analyzer</h2>
            <nav class="space-y-4 text-sm">
                <a href="welcome.php" class="flex items-center gap-2 px-3 py-2 rounded bg-gray-700">📊 Dashboard</a>
                <a href="record_audio.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">🎤 Record Audio</a>
                <a href="upload.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">📝 Upload Audio</a>
                <a href="history.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">🕓 Call History</a>
                <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-red-600 text-red-100 mt-10">🚪 Logout</a>
            </nav>
        </div>
        <div class="text-sm text-gray-300 mt-10">Logged in as <?= htmlspecialchars($_SESSION['user']) ?></div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 md:p-10 space-y-10">
        <header>
            <h1 class="text-3xl font-bold mb-2 animate-fade-in">Welcome, <?= htmlspecialchars($_SESSION['user']) ?>!</h1>
            <p class="text-gray-600 animate-fade-in">Here's your sentiment analysis overview</p>
        </header>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 gap-6">
            <div class="bg-white rounded-xl shadow-lg p-6 animate-fade-in">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500">Total Calls</p>
                        <h3 class="text-2xl font-bold"><?= number_format($total_calls) ?></h3>
                    </div>
                    <div class="text-3xl text-blue-500">📞</div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-green-500">+<?= $total_calls > 0 ? round(($total_calls - 100) / 100 * 100) : 0 ?>%</span>
                        <span class="text-gray-500">vs last month</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Recent Activity -->
        <div class="grid grid-cols-1 gap-6">
            <div class="bg-white rounded-xl shadow-lg p-6 animate-fade-in">
                <h2 class="text-xl font-semibold mb-4">Recent Analysis</h2>
                <div class="space-y-4">
                    <?php 
                    // Get exactly 5 recent calls
                    $stmt = $conn->query("SELECT * FROM calls ORDER BY timestamp DESC LIMIT 5");
                    $recent_calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($recent_calls) > 0):
                        foreach ($recent_calls as $call): 
                    ?>
                    <div class="flex items-center justify-between p-4 rounded-lg sentiment-<?= strtolower($call['sentiment']) ?>">
                        <div>
                            <p class="font-medium"><?= htmlspecialchars($call['caller'] ?: 'Anonymous Caller') ?></p>
                            <p class="text-sm text-gray-600"><?= date('M j, Y g:i A', strtotime($call['timestamp'])) ?></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm font-medium"><?= ucfirst($call['sentiment']) ?></span>
                    </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <div class="text-center text-gray-500 py-4">
                        No recent calls to display
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <a href="record_audio.php" class="card-hover bg-white rounded-xl shadow-lg p-6 animate-fade-in">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-microphone text-xl text-blue-500"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold">Record New Call</h3>
                        <p class="text-sm text-gray-500">Start a new recording</p>
                    </div>
                </div>
            </a>

            <a href="upload.php" class="card-hover bg-white rounded-xl shadow-lg p-6 animate-fade-in">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-upload text-xl text-green-500"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold">Upload Recording</h3>
                        <p class="text-sm text-gray-500">Upload existing audio</p>
                    </div>
                </div>
            </a>

            <a href="history.php" class="card-hover bg-white rounded-xl shadow-lg p-6 animate-fade-in">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-history text-xl text-purple-500"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold">View History</h3>
                        <p class="text-sm text-gray-500">Check past analyses</p>
                    </div>
                </div>
            </a>
        </div>

        <footer class="text-center text-gray-500 text-xs pt-10">
            © 2025 Helpdesk AI · Secure processing in place.
        </footer>
    </main>

    <script>
        // Add hover effects to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card-hover');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html> 