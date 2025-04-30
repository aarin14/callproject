<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle POST request for registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $passwordConfirm = $_POST['passwordConfirm'];
    $error = '';

    if (empty($username)) {
        $error .= 'Username is required.<br>';
    } elseif (strlen($username) < 3) {
        $error .= 'Username must be at least 3 characters long.<br>';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error .= 'Username can only contain letters, numbers, and underscores.<br>';
    }

    if (empty($password)) {
        $error .= 'Password is required.<br>';
    } elseif (strlen($password) < 6) {
        $error .= 'Password must be at least 6 characters long.<br>';
    }

    if ($password !== $passwordConfirm) {
        $error .= 'Passwords do not match.<br>';
    }

    if (empty($error)) {
        $usersFile = __DIR__ . '/data/users.json';

        // Debug: Log file path
        error_log("Users file path: $usersFile");

        // Ensure the users.json file exists, or create it
        if (!file_exists($usersFile)) {
            if (!file_put_contents($usersFile, json_encode([]))) {
                die('Error: Unable to create users.json file.');
            }
        }

        // Load existing users
        $rawData = file_get_contents($usersFile);
        $users = json_decode($rawData, true);
        if (!is_array($users)) {
            error_log("users.json is corrupted or empty. Raw data: $rawData");
            $users = [];
        }

        // Debug: Log users array
        error_log("Users array: " . json_encode($users));
        error_log("Attempting to register username: $username");

        // Check if the username already exists (only if users array is not empty)
        if (!empty($users)) {
            foreach ($users as $user) {
                error_log("Comparing with user: " . $user['username']);
                if (strtolower($user['username']) === strtolower($username)) {
                    error_log("Username $username already exists");
                    $error = 'Username already exists.';
                    break;
                }
            }
        }

        if (!$error) {
            // Hash the password before saving
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Add new user to the array
            $users[] = ['username' => $username, 'password' => $hashedPassword];

            // Save the updated users list to users.json
            if (false === file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
                $error = 'Error: Unable to save user data.';
            } else {
                // Redirect to login page
                header('Location: login.php?registration_success=1');
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register – Helpdesk AI</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 dark:bg-gray-900 flex items-center justify-center min-h-screen">

  <form method="POST" action="register.php" class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-md w-full max-w-sm">
    <h1 class="text-2xl font-bold mb-6 text-center text-gray-800 dark:text-gray-100">Create an Account</h1>

    <?php if (!empty($error)): ?>
      <div class="mb-4 text-red-600"><?= $error ?></div>
    <?php endif; ?>

    <div class="mb-4">
      <label for="username" class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">Username</label>
      <input
        type="text"
        id="username"
        name="username"
        required
        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:shadow-outline bg-gray-50 dark:bg-gray-900"
      />
      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Only letters, numbers, and underscores are allowed.</p>
    </div>

    <div class="mb-4 relative">
      <label for="password" class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">Password</label>
      <input
        type="password"
        id="password"
        name="password"
        required
        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:shadow-outline bg-gray-50 dark:bg-gray-900"
      />
      <button type="button" class="absolute top-1/2 right-3 transform -translate-y-1/2 focus:outline-none" id="togglePassword" onclick="togglePasswordVisibility()">
        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5 text-gray-500 dark:text-gray-300">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12c0 2.21-1.79 4-4 4s-4-1.79-4-4 1.79-4 4-4 4 1.79 4 4z" />
        </svg>
      </button>
      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Must be at least 6 characters long.</p>
    </div>

    <div class="mb-4 relative">
      <label for="passwordConfirm" class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300">Confirm Password</label>
      <input
        type="password"
        id="passwordConfirm"
        name="passwordConfirm"
        required
        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:shadow-outline bg-gray-50 dark:bg-gray-900"
      />
      <button type="button" class="absolute top-1/2 right-3 transform -translate-y-1/2 focus:outline-none" id="togglePasswordConfirm" onclick="togglePasswordConfirmVisibility()">
        <svg id="eyeIconConfirm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5 text-gray-500 dark:text-gray-300">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12c0 2.21-1.79 4-4 4s-4-1.79-4-4 1.79-4 4-4 4 1.79 4 4z" />
        </svg>
      </button>
    </div>

    <button type="submit" name="register" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
      Register
    </button>

    <p class="mt-4 text-center text-gray-600 dark:text-gray-400 text-sm">
      Already have an account?
      <a href="login.php" class="text-blue-600 hover:underline">Login</a>
    </p>
  </form>

  <script>
    function togglePasswordVisibility() {
      const passwordField = document.getElementById('password');
      const eyeIcon = document.getElementById('eyeIcon');
      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.classList.remove('text-gray-500', 'dark:text-gray-300');
        eyeIcon.classList.add('text-blue-500');
      } else {
        passwordField.type = 'password';
        eyeIcon.classList.remove('text-blue-500');
        eyeIcon.classList.add('text-gray-500', 'dark:text-gray-300');
      }
    }

    function togglePasswordConfirmVisibility() {
      const passwordConfirmField = document.getElementById('passwordConfirm');
      const eyeIconConfirm = document.getElementById('eyeIconConfirm');
      if (passwordConfirmField.type === 'password') {
        passwordConfirmField.type = 'text';
        eyeIconConfirm.classList.remove('text-gray-500', 'dark:text-gray-300');
        eyeIconConfirm.classList.add('text-blue-500');
      } else {
        passwordConfirmField.type = 'password';
        eyeIconConfirm.classList.remove('text-blue-500');
        eyeIconConfirm.classList.add('text-gray-500', 'dark:text-gray-300');
      }
    }
  </script>

</body>
</html>