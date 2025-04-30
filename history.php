<?php
require_once "auth.php";
require_once "utils.php";

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sentimentanalyser";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get filter parameters
$sentiment_filter = isset($_GET['sentiment']) ? $_GET['sentiment'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query
$query = "SELECT * FROM calls WHERE 1=1";
$params = array();

if (!empty($sentiment_filter)) {
    $query .= " AND sentiment = :sentiment";
    $params[':sentiment'] = $sentiment_filter;
}

if (!empty($date_filter)) {
    $query .= " AND DATE(timestamp) = :date";
    $params[':date'] = $date_filter;
}

if (!empty($search)) {
    $query .= " AND (caller LIKE :search OR transcribed_text LIKE :search)";
    $params[':search'] = "%$search%";
}

// Add sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'timestamp';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$query .= " ORDER BY $sort $order";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$calls = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call History | Sentiment Analyzer</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slideIn {
            animation: slideIn 0.5s ease-out both;
        }
        .sentiment-positive { background-color: #dcfce7; color: #166534; }
        .sentiment-negative { background-color: #fee2e2; color: #991b1b; }
        .sentiment-neutral { background-color: #f3f4f6; color: #374151; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans flex min-h-screen">
    <aside class="w-64 bg-gray-800 text-white p-6 hidden md:flex flex-col justify-between shadow-xl">
        <div>
            <h2 class="text-2xl font-bold mb-10">Sentiment Analyzer</h2>
            <nav class="space-y-4 text-sm">
                <a href="welcome.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">📊 Dashboard</a>
                <a href="record_audio.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">🎤 Record Audio</a>
                <a href="upload.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-700">📝 Upload Audio</a>
                <a href="history.php" class="flex items-center gap-2 px-3 py-2 rounded bg-gray-700">🕓 Call History</a>
                <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-red-600 text-red-100 mt-10">🚪 Logout</a>
            </nav>
        </div>
        <div class="text-sm text-gray-300 mt-10">Logged in as <?= htmlspecialchars($_SESSION['user']) ?></div>
    </aside>

    <main class="flex-1 p-6 md:p-10 space-y-6">
        <header>
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Call History</h1>
                <?php if ($_SESSION['user'] === 'admin'): ?>
                    <a href="clear_history.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">
                        Clear History
                    </a>
                <?php endif; ?>
            </div>
            <!-- <h1 class="text-3xl font-bold mb-2 animate-slideIn">Call History</h1> -->
            <p class="text-gray-600 animate-slideIn">View and analyze past call recordings and their sentiment analysis.</p>
        </header>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow p-6 animate-slideIn">
            <form id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Search caller or text" 
                           class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sentiment</label>
                    <select name="sentiment" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All</option>
                        <option value="positive" <?= $sentiment_filter === 'positive' ? 'selected' : '' ?>>Positive</option>
                        <option value="negative" <?= $sentiment_filter === 'negative' ? 'selected' : '' ?>>Negative</option>
                        <option value="neutral" <?= $sentiment_filter === 'neutral' ? 'selected' : '' ?>>Neutral</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($date_filter) ?>" 
                           class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div class="bg-white rounded-xl shadow overflow-hidden animate-slideIn">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?sort=caller&order=<?= ($sort === 'caller' && $order === 'ASC') ? 'DESC' : 'ASC' ?>" 
                                   class="flex items-center gap-1">
                                    Caller
                                    <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?sort=sentiment&order=<?= ($sort === 'sentiment' && $order === 'ASC') ? 'DESC' : 'ASC' ?>" 
                                   class="flex items-center gap-1">
                                    Sentiment
                                    <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Text Preview
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?sort=timestamp&order=<?= ($sort === 'timestamp' && $order === 'ASC') ? 'DESC' : 'ASC' ?>" 
                                   class="flex items-center gap-1">
                                    Date
                                    <i class="fas fa-sort"></i>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($calls as $call): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= htmlspecialchars($call['caller'] ?: 'Anonymous') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                sentiment-<?= strtolower($call['sentiment']) ?>">
                                        <?= ucfirst(htmlspecialchars($call['sentiment'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 truncate max-w-xs">
                                        <?= htmlspecialchars(substr($call['transcribed_text'], 0, 100)) ?>...
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y g:i A', strtotime($call['timestamp'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="showDetails(<?= htmlspecialchars(json_encode($call)) ?>)" 
                                            class="text-blue-600 hover:text-blue-900">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-xl font-bold" id="modalTitle">Call Details</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="space-y-4" id="modalContent">
                    <!-- Content will be dynamically populated -->
                </div>
                <div class="mt-4 flex justify-end">
                    <button onclick="closeModal()" 
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show modal with call details
        function showDetails(call) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('modalContent');
            
            content.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Caller</h4>
                        <p>${call.caller || 'Anonymous'}</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Sentiment</h4>
                        <span class="px-2 py-1 inline-flex text-sm font-semibold rounded-full sentiment-${call.sentiment.toLowerCase()}">
                            ${call.sentiment}
                        </span>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Transcribed Text</h4>
                        <p class="text-gray-600 whitespace-pre-wrap">${call.transcribed_text}</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Date & Time</h4>
                        <p>${new Date(call.timestamp).toLocaleString()}</p>
                    </div>
                </div>
            `;
            
            modal.classList.remove('hidden');
        }

        // Close modal
        function closeModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('detailsModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Handle filter form submission
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            window.location.href = `history.php?${params.toString()}`;
        });
    </script>
</body>
</html> 