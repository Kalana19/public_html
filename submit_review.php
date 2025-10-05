<?php
include 'config.php';

$error = '';
$success = '';
$product_title = '';
$review_title = '';
$description = '';
$rating = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_title = trim(filter_input(INPUT_POST, 'product_title', FILTER_SANITIZE_STRING) ?? '');
    $review_title = trim(filter_input(INPUT_POST, 'review_title', FILTER_SANITIZE_STRING) ?? '');
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING) ?? '');
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]) ?? 0;

    if (empty($product_title) || empty($review_title) || empty($description) || $rating < 1 || $rating > 5) {
        $error = 'All fields are required. Rating must be between 1-5.';
    } else {
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $image_name = uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_path = $upload_dir . $image_name;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                $error = 'Image upload failed (optional). Review will save without image.';
                $image_path = null;
            }
        }

        if (empty($error)) {
            try {
                $pdo = getPDO();
                
                $stmt = $pdo->prepare("
                    INSERT INTO reviews (user_id, product_title, title, description, rating, date_added, image_path) 
                    VALUES (1, :product_title, :title, :description, :rating, NOW(), :image_path)
                ");
                $stmt->execute([
                    ':product_title' => $product_title,
                    ':title' => $review_title,
                    ':description' => $description,
                    ':rating' => $rating,
                    ':image_path' => $image_path
                ]);
                $success = 'Review submitted successfully!';
                // Reset form
                $product_title = '';
                $review_title = '';
                $description = '';
                $rating = 0;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Review - RMIT Review System</title>
    <style>
        body {font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #002F6C 0%, #4A90E2 100%); min-height: 100vh; color: #333; margin: 0; padding: 0;}
        .container {max-width: 600px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);}
        h1 {text-align: center; color: #002F6C; margin-bottom: 2rem; font-size: 2rem;}
        .form-group {margin-bottom: 1.5rem;}
        label {display: block; color: #002F6C; font-weight: bold; margin-bottom: 0.5rem;}
        input[type="text"], textarea, select {width: 100%; padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px; box-sizing: border-box; transition: border-color 0.3s;}
        input[type="text"]:focus, textarea:focus, select:focus {border-color: #4A90E2; outline: none;}
        textarea {min-height: 120px; resize: vertical;}
        input[type="file"] {padding: 0.5rem;}
        .error {color: #dc3545; background: #f8d7da; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;}
        .success {color: #155724; background: #d4edda; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;}
        .btn {display: block; width: 100%; padding: 1rem; background: #4A90E2; color: white; border: none; border-radius: 8px; font-size: 1.1rem; cursor: pointer; transition: background 0.3s; text-decoration: none; text-align: center;}
        .btn:hover {background: #002F6C;}
        .back-link {text-align: center; margin-top: 1rem;}
        .back-link a {color: #4A90E2; text-decoration: none;}
        .back-link a:hover {text-decoration: underline;}
        @media (max-width: 768px) {
            .container {margin: 1rem; padding: 1rem;}
            h1 {font-size: 1.5rem;}
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Submit a New Review</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="product_title">Product Title:</label>
                <input type="text" id="product_title" name="product_title" value="<?= htmlspecialchars($product_title) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="review_title">Review Title:</label>
                <input type="text" id="review_title" name="review_title" value="<?= htmlspecialchars($review_title) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required><?= htmlspecialchars($description) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="rating">Rating (1-5):</label>
                <select id="rating" name="rating" required>
                    <option value="">Select Rating</option>
                    <option value="1" <?= $rating == 1 ? 'selected' : '' ?>>1 - Poor</option>
                    <option value="2" <?= $rating == 2 ? 'selected' : '' ?>>2 - Fair</option>
                    <option value="3" <?= $rating == 3 ? 'selected' : '' ?>>3 - Good</option>
                    <option value="4" <?= $rating == 4 ? 'selected' : '' ?>>4 - Very Good</option>
                    <option value="5" <?= $rating == 5 ? 'selected' : '' ?>>5 - Excellent</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="image">Product Image (Optional):</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            
            <button type="submit" class="btn">Submit Review</button>
        </form>
        
        <div class="back-link">
            <a href="review_list.php">Back to Reviews</a>
        </div>
    </div>
</body>
</html>