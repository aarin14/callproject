<?php
  require_once "auth.php";

  $historyFile = 'data/history.json';
  $history = file_exists($historyFile) ? json_decode(file_get_contents($historyFile), true) : [];

  if (!is_array($history)) {
      $history = [];
      error_log("Warning: data/history.json is not a valid JSON array.");
  }

  function sentimentIcon($sentiment) {
    return [
      "positive" => "😊",
      "neutral" => "😐",
      "negative" => "😠"
    ][strtolower($sentiment)] ?? "❓";
  }

  function getSentimentColor($sentiment) {
    return match (strtolower($sentiment)) {
      "positive" => "text-green-600",
      "neutral" => "text-yellow-600",
      "negative" => "text-red-600",
      default => "text-gray-600"
    };
  }
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Call History | Helpdesk AI</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 font-sans flex min-h-screen">

<aside class="w-64 bg-blue-800 text-white p-6 hidden md:flex flex-col justify-between shadow-xl">
  <div>
    <h2 class="text-2xl font-bold mb-10">📞 Helpdesk AI</h2>
    <nav class="space-y-4 text-sm">
      <a href="index.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-blue-600">📊 Dashboard</a>
      <a href="upload.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-blue-600">⬆️ Upload Call</a>
      <a href="history.php" class="flex items-center gap-2 px-3 py-2 rounded bg-blue-700">🕓 Call History</a>
      <a href="contact.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-blue-600">✉️ Contact Us</a>

      <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-red-600 text-red-100 mt-10">🚪 Logout</a>
    </nav>
  </div>
  <div class="text-sm text-blue-200 mt-10">Logged in as <?= htmlspecialchars($_SESSION['user']) ?></div>
</aside>

<main class="flex-1 p-6 md:p-10 space-y-10">

  <header class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
    <div>
      <h1 class="text-3xl font-bold mb-1">Call History</h1>
      <p class="text-gray-600">Review past call transcripts and sentiment outcomes.</p>
    </div>
  </header>

  <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
    <table class="w-full table-auto text-sm">
      <thead class="bg-gray-100 text-gray-600 uppercase text-left">
        <tr>
          <th class="px-4 py-2">Caller</th>
          <th class="px-4 py-2">Sentiment</th>
          <th class="px-4 py-2">Transcript</th>
          <th class="px-4 py-2">Date</th>
        </tr>
      </thead>
      <tbody>
        <?php
          foreach (array_reverse($history) as $call) {
            $icon = sentimentIcon($call['sentiment']);
            $colorClass = getSentimentColor($call['sentiment']);
            $caller = htmlspecialchars($call['caller']);
            $transcript = htmlspecialchars($call['transcript']);
            $timestamp = htmlspecialchars($call['timestamp']);
            $sentiment = ucfirst(htmlspecialchars($call['sentiment']));

            echo "<tr class='border-b hover:bg-gray-50 transition'>";
            echo "<td class='px-4 py-3'>{$caller}</td>";
            echo "<td class='px-4 py-3 font-semibold {$colorClass}'>{$icon} {$sentiment}</td>";
            echo "<td class='px-4 py-3 max-w-md truncate' title='{$transcript}'>{$transcript}</td>";
            echo "<td class='px-4 py-3'>{$timestamp}</td>";
            echo "</tr>";
          }
        ?>
      </tbody>
    </table>
  </div>

  <footer class="text-center text-gray-500 text-xs pt-10">
    &copy; 2025 Helpdesk AI · Secure call data storage in place.
  </footer>
</main>

</body>
</html>
