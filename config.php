<?php
session_start();  // Starts the session

include 'db.php';  // Include PDO function


if (!isset($_SESSION['current_user'])) {
    // For now, mock with ID from DB; in real, query after login
    $_SESSION['current_user'] = ['id' => 1, 'username' => 'testuser'];
}


if (!isset($_SESSION['current_user'])) {
    header('Location: login.php');
    exit;
}
?>