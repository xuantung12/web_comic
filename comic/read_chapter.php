<?php
require_once 'db.php'; // Kết nối cơ sở dữ liệu

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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đọc Chapter <?= htmlspecialchars($chapter['chapter_number']) ?></title>
    <link rel="stylesheet" href="read_chapter.css">
</head>
<body>
<div class="button-search">
    <form method="GET" action="test1.php" id="searchForm">
        <input type="text" name="search_query" placeholder="Tìm kiếm truyện..." required>
        <button type="submit">Tìm kiếm</button>
    </form>
</div>

<div class="navbar">
        <a href="test1.php">Trang chủ</a>

        <!-- Thể loại -->
        <div class="dropdown">
    <button class="dropbtn">Thể loại</button>
    <div class="dropdown-content">
        <?php
        // Fetch genres from the database
        $genre_query = "SELECT * FROM genres";
        $genre_result = $conn->query($genre_query);

        // Check the query result and create links for each genre
        if ($genre_result->num_rows > 0) {
            while ($genre_row = $genre_result->fetch_assoc()) {
                echo '<a href="test1.php?genre=' . urlencode($genre_row['genre_name']) . '">' . htmlspecialchars($genre_row['genre_name']) . '</a>';
            }
        } else {
            echo "<a href='#'>Không có thể loại nào.</a>";
        }
        ?>
    </div>
    </div>
        <!-- Xếp hạng -->
        <div class="dropdown">
            <button class="dropbtn">Xếp hạng</button>
            <div class="dropdown-content">
                <a href="index.php?ranking=day">Xếp hạng ngày</a>
                <a href="index.php?ranking=week">Xếp hạng tuần</a>
                <a href="index.php?ranking=month">Xếp hạng tháng</a>
                <a href="index.php?ranking=year">Xếp hạng năm</a>
            </div>
        </div>

        <!-- Lịch sử đọc -->
        <a href="reading_history.php">Lịch sử đọc</a>

        <!-- Truyện dành cho con trai và con gái -->
        <a href="comics_for_boy_girl.php?genre=comic_for_boy">Truyện dành cho con trai</a>
        <a href="comics_for_boy_girl.php?genre=comic_for_girl">Truyện dành cho con gái</a>

        <a href="#">Fanpage thảo luận</a>
    </div>

    <div class="main-contents">
        <h1>Đọc Chapter <?= htmlspecialchars($chapter['chapter_number']) ?></h1>
        <!-- Hiển thị hình ảnh của chapter -->
        <img src="<?= htmlspecialchars($chapter['image']) ?>" alt="Hình ảnh chapter">
    </div>
</body>
</html>
