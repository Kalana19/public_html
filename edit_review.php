<?php
include 'config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: review_list.php');
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$review || $review['user_id'] !== $_SESSION['current_user']['id']) {
        header('Location: review_list.php');
        exit;
    }

    $error = '';
    $product_title = $review['product_title'];
    $title = $review['title'];
    $description = $review['description'];
    $rating = $review['rating'];
    $current_image = $review['image_path'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_title = filter_input(INPUT_POST, 'product_title', FILTER_SANITIZE_STRING);
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]) ?? 0;
        $remove_image = filter_input(INPUT_POST, 'remove_image', FILTER_VALIDATE_BOOLEAN) ?? false;

        if (empty($product_title) || empty($title) || empty($description) || $rating < 1 || $rating > 5) {
            $error = 'All fields required. Rating 1-5.';
        } else {
            $image_path = $current_image;
            if ($remove_image && $current_image) {
                unlink($current_image);
                $image_path = null;
            }
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                if ($current_image) unlink($current_image);
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $image_name = uniqid() . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_path = $upload_dir . $image_name;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                    $error = 'Image upload failed.';
                }
            }

            if (empty($error)) {
                $stmt = $pdo->prepare("
                    UPDATE reviews SET 
                        product_title = :product_title, 
                        title = :title, 
                        description = :description, 
                        rating = :rating, 
                        image_path = :image_path 
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':product_title' => $product_title,
                    ':title' => $title,
                    ':description' => $description,
                    ':rating' => $rating,
                    ':image_path' => $image_path,
                    ':id' => $id
                ]);
                header('Location: review_detail.php?id=' . $id);
                exit;
            }
        }
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review - RMIT Review System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #002F6C 0%, #4A90E2 100%); min-height: 100vh; color: #333; }
        .container { max-width: 600px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        h1 { text-align: center; color: #002F6C; margin-bottom: 1.5rem; font-size: 2rem; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #f5c6cb; }
        form { display: flex; flex-direction: column; gap: 1.5rem; }
        label { font-weight: bold; color: #002F6C; }
        input[type="text"], textarea { padding: 0.75rem; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s; }
        input[type="text"]:focus, textarea:focus { border-color: #4A90E2; outline: none; box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1); }
        textarea { resize: vertical; min-height: 100px; }
        .stars { text-align: center; margin: 0.5rem 0; }
        .stars span { font-size: 2rem; cursor: pointer; transition: color 0.3s, transform 0.3s; margin: 0 0.2rem; }
        .stars span:hover { color: #FFD700; transform: scale(1.2); }
        .stars span.selected { color: #FFD700; }
        .current-image, #preview { max-width: 100%; border-radius: 8px; margin-top: 0.5rem; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        button { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; transition: background 0.3s; font-weight: bold; }
        button[type="submit"] { background: #28a745; color: white; }
        button[type="submit"]:hover { background: #218838; }
        button[type="button"] { background: #6c757d; color: white; }
        button[type="button"]:hover { background: #5a6268; }
        .cancel-btn { background: #dc3545; color: white; }
        .cancel-btn:hover { background: #c82333; }
        @media (max-width: 600px) { .container { margin: 1rem; padding: 1.5rem; } h1 { font-size: 1.5rem; } }
    </style>
    <script>
        let selectedRating = <?php echo $rating; ?>;

        function setRating(r) {
            selectedRating = r;
            const stars = document.querySelectorAll('.stars span');
            stars.forEach((star, index) => {
                star.classList.toggle('selected', index < r);
            });
            document.querySelector('input[name="rating"]').value = r;
        }

        function previewImage() {
            const file = document.querySelector('input[name="image"]').files[0];
            const preview = document.getElementById('preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => { preview.src = e.target.result; preview.style.display = 'block'; };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        function removeCurrentImage() {
            document.querySelector('.current-image').style.display = 'none';
            document.querySelector('input[name="remove_image"]').value = '1';
        }

        function validateForm() {
            const productTitle = document.querySelector('input[name="product_title"]').value.trim();
            const title = document.querySelector('input[name="title"]').value.trim();
            const desc = document.querySelector('textarea[name="description"]').value.trim();
            if (!productTitle || !title || !desc || selectedRating < 1 || selectedRating > 5) {
                alert('Please fill all fields and select a rating between 1 and 5 by clicking a star.');
                return false;
            }
            document.querySelector('input[name="rating"]').value = selectedRating; // Ensure final sync
            return true;
        }

        // Init stars
        window.onload = function() { setRating(<?php echo $rating; ?>); };
    </script>
</head>
<body>
    <div class="container">
        <h1>Edit Review</h1>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm();">
            <label for="product_title">Product Title:</label>
            <input type="text" id="product_title" name="product_title" value="<?php echo htmlspecialchars($product_title); ?>" required>
            
            <label for="title">Review Title:</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
            
            <label for="description">Description:</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($description); ?></textarea>
            
            <label for="rating">Rating:</label>
            <div class="stars">
                <span id="star1" onclick="setRating(1)">★</span>
                <span id="star2" onclick="setRating(2)">★</span>
                <span id="star3" onclick="setRating(3)">★</span>
                <span id="star4" onclick="setRating(4)">★</span>
                <span id="star5" onclick="setRating(5)">★</span>
            </div>
            <input type="hidden" name="rating" value="<?php echo $rating; ?>">
            <small>Click a star to select rating (1-5).</small>
            
            <label for="image">Current Image:</label>
            <?php if ($current_image): ?>
                <img src="<?php echo htmlspecialchars($current_image); ?>" alt="Current Image" class="current-image">
                <button type="button" onclick="removeCurrentImage();">Remove</button>
                <input type="hidden" name="remove_image" value="0">
            <?php endif; ?>
            <label for="image">New Image (optional):</label>
            <input type="file" id="image" name="image" accept="image/*" onchange="previewImage();">
            <img id="preview" alt="Preview" style="display: none;">
            
            <button type="submit">Save Changes</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='review_detail.php?id=<?php echo $id; ?>'">Cancel</button>
        </form>
    </div>
</body>
</html>