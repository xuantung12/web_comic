<?php
include 'db.php';

// Khởi tạo biến
$message = "";

// Kiểm tra xem có id truyện không
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Lấy thông tin của manga
    $manga_query = "SELECT * FROM manga WHERE id = ?";
    $stmt = $conn->prepare($manga_query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $manga_result = $stmt->get_result();
    $manga = $manga_result->fetch_assoc();

    // Lấy thể loại đã được gán cho manga
    $genres_query = "SELECT g.id, g.genre_name FROM genres g
                     JOIN manga_genres mg ON g.id = mg.genre_id
                     WHERE mg.manga_id = ?";
    $stmt_genres = $conn->prepare($genres_query);
    $stmt_genres->bind_param('i', $id);
    $stmt_genres->execute();
    $selected_genres = $stmt_genres->get_result()->fetch_all(MYSQLI_ASSOC);
    $selected_genre_ids = array_column($selected_genres, 'id');

    // Lấy tất cả các thể loại
    $all_genres_query = "SELECT * FROM genres";
    $all_genres_result = $conn->query($all_genres_query);
}

// Xử lý cập nhật khi form được gửi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_manga'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $author = $_POST['author'];
    $cover_image = $_FILES['cover_image']['name'];
    $target = "uploads/" . basename($cover_image);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Cập nhật thông tin truyện
    $sql = "UPDATE manga SET title = ?, description = ?, author = ?, is_featured = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssiii', $title, $description, $author, $is_featured, $id);

    if ($stmt->execute()) {
        // Xử lý cập nhật ảnh bìa nếu có ảnh mới
        if (!empty($cover_image)) {
            $cover_sql = "SELECT cover_image FROM manga WHERE id = ?";
            $stmt_cover = $conn->prepare($cover_sql);
            $stmt_cover->bind_param('i', $id);
            $stmt_cover->execute();
            $cover_result = $stmt_cover->get_result();
            $cover_row = $cover_result->fetch_assoc();
            $old_cover_path = "uploads/" . $cover_row['cover_image'];

            // Di chuyển ảnh tải lên
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target)) {
                // Xóa ảnh cũ nếu có
                if (file_exists($old_cover_path)) {
                    unlink($old_cover_path);
                }
            } else {
                $message = "Lỗi khi tải ảnh lên.";
            }
        }

        // Cập nhật thể loại
        $delete_genres_sql = "DELETE FROM manga_genres WHERE manga_id = ?";
        $stmt_delete_genres = $conn->prepare($delete_genres_sql);
        $stmt_delete_genres->bind_param('i', $id);
        $stmt_delete_genres->execute();

        if (isset($_POST['genres'])) {
            $sql_genre = "INSERT INTO manga_genres (manga_id, genre_id) VALUES (?, ?)";
            $stmt_genre = $conn->prepare($sql_genre);
            
            foreach ($_POST['genres'] as $genre_id) {
                $stmt_genre->bind_param('ii', $id, $genre_id);
                $stmt_genre->execute();
            }
        }

        $message = "Cập nhật truyện thành công!";
    } else {
        $message = "Lỗi: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa Truyện</title>
    <style>
        .checkbox-grid{
            display: grid;
            grid-template-columns: repeat(4,1fr);
        }
        button{
            padding: 10 15px;
            margin-top: 10px;
        }
        a{
            text-decoration: none;
        }
    </style>
</head>
<body>
    
    <h1>Sửa Thông Tin Truyện</h1>

    <!-- Hiển thị thông báo -->
    <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" action="">
        <input type="hidden" name="id" value="<?php echo $manga['id']; ?>">
        
        <label for="title">Tên truyện:</label><br>
        <input type="text" name="title" value="<?php echo htmlspecialchars($manga['title']); ?>" required><br>

        <label for="description">Mô tả truyện:</label><br>
        <textarea name="description" required><?php echo htmlspecialchars($manga['description']); ?></textarea><br>

        <label for="author">Tác giả:</label><br>
        <input type="text" name="author" value="<?php echo htmlspecialchars($manga['author']); ?>" required><br>

        <label for="cover_image">Ảnh bìa:</label><br>
        <input type="file" name="cover_image"><br>
        <span>(Chọn ảnh mới nếu bạn muốn thay đổi)</span><br>

        <label for="is_featured">Đề cử:</label>
        <input type="checkbox" name="is_featured" value="1" <?php if ($manga['is_featured']) echo 'checked'; ?>><br>

        <label for="genres">Thể loại:</label><br>
        <div class="checkbox-grid">
            <?php while ($genre = $all_genres_result->fetch_assoc()): ?>
                <div class="checkbox-item">
                    <input type="checkbox" name="genres[]" value="<?php echo $genre['id']; ?>" 
                    <?php if (in_array($genre['id'], $selected_genre_ids)) echo 'checked'; ?>>
                    <?php echo $genre['genre_name']; ?><br>
                </div>
            <?php endwhile; ?>
        </div>
        <input type="submit" name="update_manga" value="Cập Nhật Truyện">
    </form>
    <button><a href="index.php">Quay lại</a></button>
</body>
</html>
