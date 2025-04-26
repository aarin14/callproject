<?php
  require_once "auth.php";
  $messageSaved = false;

  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $message = trim($_POST["message"]);
    $timestamp = date("Y-m-d H:i:s");

    if ($name && $email && $message) {
      $filePath = 'data/messages.json';
      $messages = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];

      if (!is_array($messages)) $messages = [];

      $messages[] = [
        "name" => $name,
        "email" => $email,
        "message" => $message,
        "timestamp" => $timestamp
      ];

      file_put_contents($filePath, json_encode($messages, JSON_PRETTY_PRINT));
      $messageSaved = true;
    }
  }
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us | Helpdesk AI</title>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 font-sans flex min-h-screen" x-data="{ showToast: <?= $messageSaved ? 'true' : 'false' ?> }" x-init="setTimeout(() => showToast = false, 4000)">

<!-- Sidebar -->
<aside class="w-64 bg-blue-800 text-white p-6 hidden md:flex flex-col justify-between shadow-xl">
  <div>
    <h2 class="text-2xl font-bold mb-10">📞 Helpdesk AI</h2>
    <nav class="space-y-4 text-sm">
      <a href="index.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-blue-600">📊 Dashboard</a>
      <a href="upload.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-blue-600">⬆️ Upload Call</a>
      <a href="history.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-blue-600">🕓 Call History</a>
      <a href="contact.php" class="flex items-center gap-2 px-3 py-2 rounded bg-blue-700">📬 Contact Us</a>
      <a href="logout.php" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-red-600 text-red-100 mt-10">🚪 Logout</a>
    </nav>
  </div>
  <div class="text-sm text-blue-200 mt-10">Logged in as <?= htmlspecialchars($_SESSION['user']) ?></div>
</aside>

<!-- Main -->
<main class="flex-1 p-6 md:p-10 space-y-10">
  <header>
    <h1 class="text-3xl font-bold mb-2 animate-slideIn">Contact Us</h1>
    <p class="text-gray-600 dark:text-gray-400 animate-slideIn">Have feedback or questions? Let us know below.</p>
  </header>

  <!-- Toast Notification -->
  <div x-show="showToast" x-transition class="fixed top-6 right-6 bg-green-600 text-white px-4 py-3 rounded shadow-lg flex items-center gap-2 z-50">
    ✅ Message sent successfully!
  </div>

  <!-- Contact Form -->
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 max-w-2xl animate-slideIn" x-data="{
      form: { name: '', email: '', message: '' },
      chars: 0,
      clearForm() {
        this.form.name = '';
        this.form.email = '';
        this.form.message = '';
        this.chars = 0;
      },
      submitted: <?= $messageSaved ? 'true' : 'false' ?>,
  }" x-init="if (submitted) clearForm()">
    <form method="POST" class="space-y-6">
      <input type="text" name="name" x-model="form.name" placeholder="Your Name" required class="w-full px-4 py-3 rounded border focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
      
      <input type="email" name="email" x-model="form.email" placeholder="Your Email" required class="w-full px-4 py-3 rounded border focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">

      <textarea name="message" x-model="form.message" maxlength="500" rows="5" placeholder="Your Message..." required class="w-full px-4 py-3 rounded border focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"></textarea>

      <div class="text-sm text-right text-gray-500" x-text="`${form.message.length}/500 characters`"></div>

      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition">Send Message</button>
    </form>
  </div>

  <footer class="text-center text-gray-500 dark:text-gray-400 text-xs pt-10">
    &copy; 2025 Helpdesk AI · Your feedback makes us better.
  </footer>
</main>
</body>
</html>
