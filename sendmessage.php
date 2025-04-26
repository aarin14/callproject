<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = htmlspecialchars($_POST['name']);
  $email = htmlspecialchars($_POST['email']);
  $message = htmlspecialchars($_POST['message']);

  // You can send email, or store to database - here we just write to a file
  $log = "[" . date("Y-m-d H:i:s") . "] From: $name <$email>\nMessage: $message\n\n";
  file_put_contents('data/messages.log', $log, FILE_APPEND);

  // Redirect back to contact page
  header('Location: contact.php?sent=1');
  exit();
}
?>
