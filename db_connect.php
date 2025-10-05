<?php
$host = 'localhost';
$dbname = 'COSC3046_2550_G13';
$username = 'COSC3046_2550_G13';
$password = 'your_group_password'; // Replace with actual password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
session_start();
?>