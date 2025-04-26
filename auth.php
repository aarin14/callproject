<?php
session_start();

if (!isset($_SESSION['user'])) {
    // Check remember_user cookie
    if (isset($_COOKIE['remember_user'])) {
        $usersFile = 'data/users.json';
        $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
        $cookieUsername = $_COOKIE['remember_user'];
        $found = false;
        foreach ($users as $user) {
            if ($user['username'] === $cookieUsername) {
                $_SESSION['user'] = $cookieUsername;
                $found = true;
                break;
            }
        }
        if (!$found) {
            // Invalid cookie, clear it
            setcookie('remember_user', '', time() - 3600, '/', '', true, true);
            header('Location: login.php');
            exit;
        }
    } else {
        // No session or cookie, redirect to login
        header('Location: login.php');
        exit;
    }
}
?>