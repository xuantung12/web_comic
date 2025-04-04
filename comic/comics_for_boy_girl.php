<?php
include 'db.php';

if (isset($_GET['genre'])) {
    $genre_choice = $_GET['genre'];
    $query = "";

    // Đặt số lượng bản ghi trên mỗi trang
    $limit = 5;

    // Lấy trang hiện tại từ URL (nếu không có thì mặc định là 1)
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max($page, 1); // Đảm bảo trang luôn >= 1

    // Tính vị trí bắt đầu
    $offset = ($page - 1) * $limit;

    // Xác định truy vấn dựa trên thể loại đã chọn
    if ($genre_choice === 'comic_for_boy') {
        $query = "SELECT * FROM manga WHERE comic_for_boy = 1 LIMIT $limit OFFSET $offset";
    } elseif ($genre_choice === 'comic_for_girl') {
        $query = "SELECT * FROM manga WHERE comic_for_girl = 1 LIMIT $limit OFFSET $offset";
    } else {
        echo "Thể loại không hợp lệ.";
        exit; // Thoát nếu thể loại không hợp lệ
    }

    // Kiểm tra xem truy vấn có rỗng không trước khi thực hiện
    if (!empty($query)) {
        $result = $conn->query($query);

        // Truy vấn tổng số bản ghi cho thể loại đã chọn
        $total_query = "";
        if ($genre_choice === 'comic_for_boy') {
            $total_query = "SELECT COUNT(*) AS total FROM manga WHERE comic_for_boy = 1";
        } elseif ($genre_choice === 'comic_for_girl') {
            $total_query = "SELECT COUNT(*) AS total FROM manga WHERE comic_for_girl = 1";
        }

        $total_result = $conn->query($total_query);
        $total_row = $total_result->fetch_assoc();
        $total_records = $total_row['total'];

        // Tính tổng số trang
        $total_pages = ceil($total_records / $limit);
    }
} else {
    echo "Không có thể loại nào được chọn.";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="comics_for_boy_girl.css">
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
    </div>


    <div class="manga-grid">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="manga-item">
                <div class="manga-content">
                    <div class="time-port">
                        <td><?php
                            $created_at = new DateTime($row['created_at']);
                            $now = new DateTime();
                            $interval = $now->diff($created_at);

                            if ($interval->y > 0) {
                                echo $interval->y . " năm trước";
                            } elseif ($interval->m > 0) {
                                echo $interval->m . " tháng trước";
                            } elseif ($interval->d > 0) {
                                echo $interval->d . " ngày trước";
                            } elseif ($interval->h > 0) {
                                echo $interval->h . " giờ trước";
                            } elseif ($interval->i > 0) {
                                echo $interval->i . " phút trước";
                            } else {
                                echo "Vừa mới";
                            }
                        ?></td>
                    </div>
                    <img src="uploads/<?php echo $row['cover_image']; ?>" alt="Manga Cover" class="cover-image">
                    <div class="manga-info">
                        <a href="view_chapter.php?id=<?php echo $row['id']; ?>" class="manga-title">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </a>

                        <?php
                            // Truy vấn số lượng chapter cho truyện này
                            $manga_id = $row['id'];
                            $chapter_sql = "SELECT COUNT(*) AS chapter_count FROM chapters WHERE manga_id = ?";
                            $chapter_stmt = $conn->prepare($chapter_sql);
                            $chapter_stmt->bind_param("i", $manga_id);
                            $chapter_stmt->execute();
                            $chapter_result = $chapter_stmt->get_result();
                            $chapter_row = $chapter_result->fetch_assoc();
                            $chapter_count = $chapter_row['chapter_count'];
                        ?>

                        <p class="manga-chapter">Chapter: <?php echo $chapter_count; ?></p>

                        <p class="manga-author">Tác giả: <?php echo $row['author']; ?></p>
                    </div>
                </div>
                <div class="action-buttons">
                    <a href="index.php?delete=<?php echo $row['id']; ?>" class="delete-button" onclick="return confirm('Bạn có chắc chắn muốn xóa truyện này?')">Xóa</a>
                    <a href="edit_manga.php?id=<?php echo $row['id']; ?>" class="edit-button">Sửa</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

<!-- Liên kết phân trang -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?genre=<?php echo $genre_choice; ?>&page=<?php echo $page - 1; ?>">Trang trước</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <strong><?php echo $i; ?></strong>
            <?php else: ?>
                <a href="?genre=<?php echo $genre_choice; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if
        ($page < $total_pages): ?>
        <a href="?genre=<?php echo $genre_choice; ?>&page=<?php echo $page + 1; ?>">Trang sau</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

</body>
</html>