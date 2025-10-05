<?php
function getPDO() {
    $host = 'talsprddb02.int.its.rmit.edu.au'; // from your email
    $port = 3306; // from your email
    $dbname = 'COSC3046_2550_G13'; // your database name
    $username = 'COSC3046_2550_G13'; // your username
    $password = 'hUKqh6eEU2Gc'; // your password

    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>