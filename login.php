<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: record_audio.php');
    exit;
}

$usersFile = __DIR__ . '/data/users.json';
$error = '';

if (!file_exists($usersFile)) {
    die('Error: users.json not found. Please register first.');
}

$raw = file_get_contents($usersFile);
$users = json_decode($raw, true);

if (!is_array($users)) {
    die('Error: users.json is corrupted.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error = 'Both username and password are required.';
    } else {
        $found = false;
        foreach ($users as $u) {
            if (isset($u['username']) && $u['username'] === $username) {
                $found = true;
                if (password_verify($password, $u['password'])) {
                    $_SESSION['user'] = $username;
                    if ($remember) {
                        // Set a cookie to remember the user for 30 days
                        $expiry = time() + (30 * 24 * 60 * 60);
                        setcookie('remember_user', $username, $expiry, '/', '', true, true);
                    }
                    header('Location: welcome.php');
                    exit;
                } else {
                    $error = 'Incorrect password.';
                }
                break;
            }
        }
        if (!$found) {
            $error = 'Username not found.';
        }
    }
} elseif (isset($_COOKIE['remember_user'])) {
    // Validate cookie against users.json
    $cookieUsername = $_COOKIE['remember_user'];
    $found = false;
    foreach ($users as $u) {
        if (isset($u['username']) && $u['username'] === $cookieUsername) {
            $found = true;
            $_SESSION['user'] = $cookieUsername;
            header('Location: welcome.php');
            exit;
        }
    }
    if (!$found) {
        // Invalid cookie, clear it
        setcookie('remember_user', '', time() - 3600, '/', '', true, true);
        $error = 'Invalid session. Please log in again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login – Helpdesk AI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script>
    function togglePassword() {
      const input = document.getElementById("password");
      const icon = document.getElementById("toggleIcon");
      if (input.type === "password") {
        input.type = "text";
        icon.textContent = "🙈";
      } else {
        input.type = "password";
        icon.textContent = "👁️";
      }
    }
  </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 flex items-center justify-center min-h-screen transition duration-300 ease-in-out">

  <form method="POST" action="login.php" class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-2xl w-full max-w-sm animate-fade-in">
    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800 dark:text-gray-100">Login to Helpdesk AI</h1>

    <?php if ($error): ?>
      <div class="mb-4 text-red-500 text-center font-medium"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="mb-4">
      <label for="username" class="block text-gray-700 dark:text-gray-300 mb-1">Username</label>
      <input type="text" name="username" id="username" required
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500" />
    </div>

    <div class="mb-6 relative">
      <label for="password" class="block text-gray-700 dark:text-gray-300 mb-1">Password</label>
      <input type="password" name="password" id="password" required
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500" />
      <button type="button" onclick="togglePassword()" class="absolute top-9 right-3 text-sm text-gray-600 dark:text-gray-400" title="Toggle visibility">
        <span id="toggleIcon">👁️</span>
      </button>
    </div>

    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center">
        <input type="checkbox" name="remember" id="remember"
          class="rounded border-gray-300 dark:border-gray-700 text-blue-600 focus:ring-blue-500" />
        <label for="remember" class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</label>
      </div>
      <a href="#" class="text-sm text-blue-600 hover:underline">Forgot password?</a>
    </div>

    <button type="submit"
      class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition duration-200 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
      Login
    </button>

    <div class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
      Don't have an account?
      <a href="register.php" class="text-blue-600 hover:underline">Register</a>
    </div>

  </form>

</body>
</html>