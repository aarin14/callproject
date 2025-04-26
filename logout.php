<?php
session_start();
session_destroy();
setcookie('remember_user', '', time() - 3600, '/', '', true, true);
header('Location: landing.html');
exit;
?>