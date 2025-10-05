<?php
include 'config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($review && $review['user_id'] === $_SESSION['current_user']['id']) {
            if ($review['image_path']) unlink($review['image_path']);
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
            $stmt->execute([':id' => $id]);
        }
    } catch (PDOException $e) {
        // Optional: Log error
    }
}
header('Location: review_list.php');
exit;
?>