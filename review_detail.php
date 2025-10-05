<?php
include 'config.php';

// Safe session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get and validate ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header('Location: review_list.php');
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT r.*, u.username AS reviewer FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.id = :id");
    $stmt->execute([':id' => $id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$review) {
        header('Location: review_list.php');
        exit;
    }

    $is_owner = isset($_SESSION['current_user']['id']) && ($review['user_id'] === $_SESSION['current_user']['id']);

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Review Details - RMIT Review System</title>
    <style>
        :root {
            --primary-blue: #002F6C;
            --secondary-blue: #4A90E2;
            --accent-gold: #FFD700;
            --text-dark: #333;
            --bg-white: #FFFFFF;
            --shadow-light: 0 6px 20px rgba(0,0,0,0.15);
            --shadow-hover: 0 10px 40px rgba(0,0,0,0.25);
            --border-radius: 15px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            min-height: 100vh;
            margin: 0;
            color: var(--text-dark);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 2rem 1rem;
        }

        .container {
            background: var(--bg-white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            max-width: 800px;
            width: 100%;
            padding: 2rem;
            box-sizing: border-box;
            transition: var(--transition);
            position: relative;
        }

        .container:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-5px);
        }

        h1 {
            text-align: center;
            color: var(--primary-blue);
            margin-bottom: 1.5rem;
            font-size: 2.5rem;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .product-title {
            font-size: 2rem;
            color: var(--secondary-blue);
            margin-bottom: 1rem;
            font-weight: 600;
            text-align: center;
            border-bottom: 3px solid var(--primary-blue);
            padding-bottom: 0.5rem;
        }

        .review-info {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            line-height: 1.6;
            color: var(--text-dark);
            white-space: pre-wrap;
        }

        .stars {
            font-size: 1.7rem;
            color: var(--accent-gold);
            margin: 0.5rem 0 1rem;
            text-align: center;
        }

        .meta {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .review-image {
            display: block;
            max-width: 100%;
            max-height: 350px;
            margin: 1rem auto;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            object-fit: cover;
            transition: var(--transition);
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 2rem;
        }

        .action-btn {
            background: var(--secondary-blue);
            color: var(--bg-white);
            padding: 0.8rem 1.8rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .action-btn:hover {
            background: var(--primary-blue);
            box-shadow: var(--shadow-hover);
            transform: translateY(-3px);
        }

        .delete {
            background: #dc3545;
        }

        .delete:hover {
            background: #b52b2b;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: var(--secondary-blue);
            font-weight: 600;
            text-decoration: none;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: var(--primary-blue);
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .container {
                padding: 1rem;
            }
            h1 {
                font-size: 1.8rem;
            }
            .product-title {
                font-size: 1.4rem;
            }
            .stars {
                font-size: 1.3rem;
            }
            .action-btn {
                font-size: 0.9rem;
                padding: 0.6rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Review Details</h1>
        <div class="product-title"><?= htmlspecialchars($review['product_title'] ?? 'No Product Title') ?></div>
        <p class="review-info"><strong>Review Title:</strong> <?= htmlspecialchars($review['title'] ?? 'No Title') ?></p>
        <p class="review-info"><strong>Description:</strong><br><?= nl2br(htmlspecialchars($review['description'] ?? '')) ?></p>
        <div class="stars">
            <?= str_repeat('★', $review['rating'] ?? 0) . str_repeat('☆', 5 - ($review['rating'] ?? 0)) ?>
            <span style="font-size: 1rem; color: #666;">(<?= ($review['rating'] ?? 0) ?>/5)</span>
        </div>
        <p class="meta"><strong>Date Added:</strong> <?= date('F j, Y, g:i a', strtotime($review['date_added'] ?? 'now')) ?> &nbsp;|&nbsp; <strong>Reviewer:</strong> <?= htmlspecialchars($review['reviewer'] ?? 'Guest') ?></p>
        <?php if (!empty($review['image_path']) && file_exists($review['image_path'])): ?>
            <img src="<?= htmlspecialchars($review['image_path']) ?>" alt="Review Image" class="review-image">
        <?php endif; ?>

        <div class="actions">
            <a href="review_list.php" class="action-btn">Back to Reviews</a>
            <?php if ($is_owner): ?>
                <a href="edit_review.php?id=<?= urlencode($id) ?>" class="action-btn">Edit</a>
                <a href="delete_review.php?id=<?= urlencode($id) ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this review?')">Delete</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>