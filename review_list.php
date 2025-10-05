<?php
include 'config.php';

// Filters
$search_product = filter_input(INPUT_GET, 'search_product', FILTER_SANITIZE_STRING) ?? '';
$search_title = filter_input(INPUT_GET, 'search_title', FILTER_SANITIZE_STRING) ?? '';
$search_desc = filter_input(INPUT_GET, 'search_desc', FILTER_SANITIZE_STRING) ?? '';
$search_date = filter_input(INPUT_GET, 'search_date', FILTER_SANITIZE_STRING) ?? '';

// Sorting
$sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING) ?? 'datedesc';
$order_by = 'date_added DESC';
if ($sort == 'dateasc') $order_by = 'date_added ASC';
if ($sort == 'ratingdesc') $order_by = 'rating DESC';
if ($sort == 'ratingasc') $order_by = 'rating ASC';

try {
    $pdo = getPDO();
    $sql = "SELECT r.*, r.id, u.username AS reviewer FROM reviews r JOIN users u ON r.user_id = u.id WHERE 1=1";
    $params = [];

    if (!empty($search_product)) {
        $sql .= " AND r.product_title LIKE :search_product";
        $params[':search_product'] = '%' . $search_product . '%';
    }
    if (!empty($search_title)) {
        $sql .= " AND r.title LIKE :search_title";
        $params[':search_title'] = '%' . $search_title . '%';
    }
    if (!empty($search_desc)) {
        $sql .= " AND r.description LIKE :search_desc";
        $params[':search_desc'] = '%' . $search_desc . '%';
    }
    if (!empty($search_date)) {
        $sql .= " AND r.date_added LIKE :search_date";
        $params[':search_date'] = '%' . $search_date . '%';
    }

    $sql .= " ORDER BY $order_by";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filtered_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Reviews - RMIT Review System</title>
    <style>
        body {font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #002F6C 0, #4A90E2 100%); min-height:100vh; color:#333;}
        .container {max-width:1200px; margin:2rem auto; padding:2rem;}
        h1 {text-align:center; color:white; margin-bottom:2rem; font-size:2.5rem; text-shadow:2px 2px 4px rgba(0,0,0,0.3);}
        .search-form {background:white; padding:1.5rem; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.2); margin-bottom:2rem; display:flex; flex-wrap:wrap; gap:1rem; justify-content:center;}
        .search-form input {padding:0.75rem; border:2px solid #ddd; border-radius:8px; min-width:150px; transition: border-color 0.3s;}
        .search-form input:focus {border-color:#4A90E2; outline:none;}
        .search-form button {padding:0.75rem 1.5rem; background:#4A90E2; color:white; border:none; border-radius:8px; cursor:pointer; transition: background 0.3s;}
        .search-form button:hover {background:#002F6C;}
        .reviews-grid {display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1.5rem;}
        .review-item {background:white; border-radius:15px; padding:1.5rem; box-shadow:0 8px 25px rgba(0,0,0,0.15); transition:transform 0.3s, box-shadow 0.3s;}
        .review-item:hover {transform:translateY(-5px); box-shadow:0 12px 35px rgba(0,0,0,0.2);}
        .review-item h3 {color:#002F6C; margin-bottom:0.5rem;}
        .review-item p {margin-bottom:0.5rem; line-height:1.5;}
        .stars {color:#FFD700; font-size:1.2rem;}
        .submit-btn {display:block; margin:2rem auto; padding:1rem 2rem; background:#28a745; color:white; border:none; border-radius:8px; font-size:1.1rem; cursor:pointer; text-decoration:none; text-align:center;}
        .submit-btn:hover {background:#218838;}
        .no-reviews {text-align:center; color:white; font-size:1.2rem; margin:2rem 0;}
        @media (max-width: 768px) {
            .search-form {flex-direction: column; align-items: center;}
            .reviews-grid {grid-template-columns:1fr;}
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Product Reviews</h1>

    <form method="GET" class="search-form">
        <input type="text" name="search_product" placeholder="Search Product Title" value="<?= htmlspecialchars($search_product) ?>">
        <input type="text" name="search_title" placeholder="Search Title" value="<?= htmlspecialchars($search_title) ?>">
        <input type="text" name="search_desc" placeholder="Search Description" value="<?= htmlspecialchars($search_desc) ?>">
        <input type="text" name="search_date" placeholder="Date (e.g., 2025-10)" value="<?= htmlspecialchars($search_date) ?>">
        <select name="sort">
            <option value="datedesc" <?= $sort == 'datedesc' ? 'selected' : '' ?>>Date (Newest)</option>
            <option value="dateasc" <?= $sort == 'dateasc' ? 'selected' : '' ?>>Date (Oldest)</option>
            <option value="ratingdesc" <?= $sort == 'ratingdesc' ? 'selected' : '' ?>>Rating (High)</option>
            <option value="ratingasc" <?= $sort == 'ratingasc' ? 'selected' : '' ?>>Rating (Low)</option>
        </select>
        <button type="submit">Filter</button>
        <a href="review_list.php"><button type="button">Clear</button></a>
    </form>

    <?php if (empty($filtered_reviews)): ?>
        <p class="no-reviews">No reviews available. <a href="submit_review.php" class="submit-btn">Add one!</a></p>
    <?php else: ?>
        <div class="reviews-grid">
        <?php foreach ($filtered_reviews as $review): ?>
            <div class="review-item">
                <h3><?= htmlspecialchars($review['product_title'] ?? 'No Title') ?></h3>
                <p><strong>Title:</strong> <?= htmlspecialchars($review['title'] ?? 'No Title') ?></p>
                <p><strong>Review:</strong> <?= htmlspecialchars(substr($review['description'] ?? '', 0, 100)) . (strlen($review['description'] ?? '') > 100 ? '...' : '') ?></p>
                <p class="stars"><?= str_repeat('★', $review['rating'] ?? 0) . str_repeat('☆', 5 - ($review['rating'] ?? 0)) ?></p>
                <p><strong>Date:</strong> <?= date('d-m-Y', strtotime($review['date_added'] ?? 'now')) ?></p>
                <p><strong>Reviewer:</strong> <?= htmlspecialchars($review['reviewer'] ?? '') ?></p>
                <?php if (isset($review['image_path']) && $review['image_path']): ?>
                    <img src="<?= htmlspecialchars($review['image_path']) ?>" alt="Review Image" style="max-width:100px; border-radius:8px; margin:0.5rem 0;">
                <?php endif; ?>
                <a href="review_detail.php?id=<?= urlencode($review['id'] ?? 0) ?>">View Details</a>
                <?php if (($review['user_id'] ?? 0) == ($_SESSION['current_user_id'] ?? 0)): ?>
                    <a href="edit_review.php?id=<?= urlencode($review['id'] ?? 0) ?>">Edit</a>
                    <a href="delete_review.php?id=<?= urlencode($review['id'] ?? 0) ?>" onclick="return confirm('Are you sure?')">Delete</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <a href="submit_review.php" class="submit-btn">Submit a Review</a>
</div>
</body>
</html>