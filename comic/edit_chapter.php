<?php
require_once 'db.php'; // Kết nối tới cơ sở dữ liệu

// Lấy ID chapter từ URL
$chapter_id = isset($_GET['chapter_id']) ? intval($_GET['chapter_id']) : 0;

// Kiểm tra nếu chapter tồn tại
$sql = "SELECT * FROM chapters WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $chapter_id);
$stmt->execute();
$result = $stmt->get_result();
$chapter = $result->fetch_assoc();

if (!$chapter) {
    die("<p style='color: red;'>Chapter không tồn tại hoặc ID không hợp lệ.</p>");
}

// Xử lý cập nhật chapter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $chapter_number = intval($_POST['chapter_number']);
    $image = $_FILES['image']['name'];
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($image);

    // Kiểm tra xem người dùng có tải ảnh mới lên không
    if ($image) {
        // Xóa ảnh cũ nếu có
        if (file_exists($chapter['image'])) {
            unlink($chapter['image']);
        }

        // Upload ảnh mới
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // Cập nhật chapter với ảnh mới
            $sql = "UPDATE chapters SET chapter_number = ?, image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isi", $chapter_number, $target_file, $chapter_id);
        } else {
            echo "<p style='color: red;'>Lỗi khi upload hình ảnh mới.</p>";
            exit;
        }
    } else {
        // Nếu không có ảnh mới, chỉ cập nhật số chương
        $sql = "UPDATE chapters SET chapter_number = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $chapter_number, $chapter_id);
    }

    if ($stmt->execute()) {
        echo "<p style='color: green;'>Cập nhật chapter thành công!</p>";
    } else {
        echo "<p style='color: red;'>Lỗi khi cập nhật chapter: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa chapter</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        input, button { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Sửa chapter: Chapter <?= $chapter['chapter_number'] ?></h1>

    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update">
        <label for="chapter_number">Số chương:</label>
        <input type="number" name="chapter_number" id="chapter_number" value="<?= $chapter['chapter_number'] ?>" required>
        <br>
        <label for="image">Hình ảnh:</label>
        <input type="file" name="image" id="image">
        <br>
        <p>Ảnh hiện tại: <img src="<?= htmlspecialchars($chapter['image']) ?>" alt="Ảnh chapter" style="max-width: 300px;"></p>
        <button type="submit">Cập nhật</button>
    </form>
    
    <br>
    <a href="view_chapter.php?manga_id=<?= $chapter['manga_id'] ?>">Trở lại danh sách chapter</a>
</body>
</html>