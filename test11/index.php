<?php
include 'db.php';

// Xử lý thêm truyện
if (isset($_POST['add_manga'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $author = $_POST['author'];
    $cover_image = $_FILES['cover_image']['name'];
    $target = "uploads/" . basename($cover_image);
    $statuss = $_POST['statuss'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $comic_for_boy = isset($_POST['comic_for_boy']) ? 1 : 0;
    $comic_for_girl = isset($_POST['comic_for_girl']) ? 1: 0;

    // Kiểm tra và di chuyển ảnh tải lên
    if ($_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        if (!file_exists('uploads')) {
            mkdir('uploads', 0755, true);
        }
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target)) {
            // Chèn manga vào bảng manga
            $sql = "INSERT INTO manga (title, description, cover_image, author, is_featured, statuss, comic_for_boy, comic_for_girl) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssssiii', $title, $description, $cover_image, $author, $statuss, $is_featured, $comic_for_boy, $comic_for_girl);
            
            if ($stmt->execute()) {
                $manga_id = $stmt->insert_id;  // Lấy ID của manga vừa chèn

                // Chèn thể loại vào bảng manga_genres
                if (isset($_POST['genres'])) {
                    $sql_genre = "INSERT INTO manga_genres (manga_id, genre_id) VALUES (?, ?)";
                    $stmt_genre = $conn->prepare($sql_genre);
                    
                    foreach ($_POST['genres'] as $genre_id) {
                        $stmt_genre->bind_param('ii', $manga_id, $genre_id);
                        $stmt_genre->execute();
                    }
                }
                echo "Thêm truyện thành công!";
            } else {
                echo "Lỗi: " . $stmt->error;
            }
        } else {
            echo "Lỗi khi tải ảnh lên.";
        }
    } else {
        echo "Lỗi upload: " . $_FILES['cover_image']['error'];
    }
}

// Xử lý xóa truyện
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Step 1: Delete associated genres from the manga_genres table
    $delete_genres_sql = "DELETE FROM manga_genres WHERE manga_id = ?";
    $stmt = $conn->prepare($delete_genres_sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();

    // Step 2: Retrieve the cover image name for deletion
    $cover_sql = "SELECT cover_image FROM manga WHERE id = ?";
    $stmt = $conn->prepare($cover_sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $cover_result = $stmt->get_result();

    // Check if the query returned a result
    if ($cover_result->num_rows > 0) {
        $cover_row = $cover_result->fetch_assoc();
        $cover_image = $cover_row['cover_image'];

        // Check if cover_image is not empty before constructing the file path
        if (!empty($cover_image)) {
            $cover_image_path = "uploads/" . $cover_image;

            // Step 4: Delete the cover image file if it exists
            if (file_exists($cover_image_path)) {
                unlink($cover_image_path);
            }
        }
    }

    // Step 3: Delete the manga entry
    $delete_sql = "DELETE FROM manga WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo "Xóa truyện thành công!";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// Process the genre filter and search query
$genre = isset($_GET['genre']) ? $_GET['genre'] : '';
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';

// Number of manga per page
$limit = 24;

// Get the current page from the URL, default is 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Check if filtering by genre and/or search query
if (!empty($genre) && !empty($search_query)) {
    // Filter by both genre and search query
    $sql = "SELECT m.* FROM manga m
            JOIN manga_genres mg ON m.id = mg.manga_id
            JOIN genres g ON mg.genre_id = g.id
            WHERE g.genre_name = ? AND m.title LIKE ?
            ORDER BY m.created_at DESC LIMIT ?, ?";
    
    $stmt = $conn->prepare($sql);
    $search_term = '%' . $search_query . '%';
    $stmt->bind_param('ssii', $genre, $search_term, $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    // Get the total count for pagination
    $total_query = "SELECT COUNT(*) FROM manga m
                    JOIN manga_genres mg ON m.id = mg.manga_id
                    JOIN genres g ON mg.genre_id = g.id
                    WHERE g.genre_name = ? AND m.title LIKE ?";
    $stmt_total = $conn->prepare($total_query);
    $stmt_total->bind_param('ss', $genre, $search_term);
    $stmt_total->execute();
    $total_result = $stmt_total->get_result();
    $total_rows = $total_result->fetch_row()[0];
    $total_pages = ceil($total_rows / $limit);

} elseif (!empty($genre)) {
    // Filter by genre only
    $sql = "SELECT m.* FROM manga m
            JOIN manga_genres mg ON m.id = mg.manga_id
            JOIN genres g ON mg.genre_id = g.id
            WHERE g.genre_name = ?
            ORDER BY m.created_at DESC LIMIT ?, ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $genre, $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    // Get the total count for pagination
    $total_query = "SELECT COUNT(*) FROM manga m
                    JOIN manga_genres mg ON m.id = mg.manga_id
                    JOIN genres g ON mg.genre_id = g.id
                    WHERE g.genre_name = ?";
    $stmt_total = $conn->prepare($total_query);
    $stmt_total->bind_param('s', $genre);
    $stmt_total->execute();
    $total_result = $stmt_total->get_result();
    $total_rows = $total_result->fetch_row()[0];
    $total_pages = ceil($total_rows / $limit);

} elseif (!empty($search_query)) {
    // Filter by search query only
    $sql = "SELECT * FROM manga WHERE title LIKE ? ORDER BY created_at DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $search_term = '%' . $search_query . '%';
    $stmt->bind_param('sii', $search_term, $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    // Get the total count for pagination
    $total_query = "SELECT COUNT(*) FROM manga WHERE title LIKE ?";
    $stmt_total = $conn->prepare($total_query);
    $stmt_total->bind_param('s', $search_term);
    $stmt_total->execute();
    $total_result = $stmt_total->get_result();
    $total_rows = $total_result->fetch_row()[0];
    $total_pages = ceil($total_rows / $limit);

} else {
    // No filtering, show all manga
    $sql = "SELECT * FROM manga ORDER BY created_at DESC LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    // Get the total count for pagination
    $total_query = "SELECT COUNT(*) FROM manga";
    $total_result = $conn->query($total_query);
    $total_rows = $total_result->fetch_row()[0];
    $total_pages = ceil($total_rows / $limit);
}



?>




<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Truyện</title>
    <link rel="stylesheet" href="styless.css">
</head>
<body>
    <h1>Quản Lý Truyện</h1>

    <form method="POST" enctype="multipart/form-data">
        <label for="title">Tên truyện:</label><br>
        <input type="text" name="title" required><br>

        <label for="description">Mô tả truyện-chapter:</label><br>
        <textarea name="description" required></textarea><br>

        <label for="author">Tác giả:</label><br>
        <input type="text" name="author" required><br>

        <label for="statuss">StatusStatus: </label>
        <select name="statuss" required>
            <option value="" disabled selected>Chọn trạng thái </option>
            <option value="Đang cập nhật">Đang cập nhật</option>
            <option value="Đã dừng">Đã dừng</option>
        </select><br>

        <label for="cover_image">Ảnh bìa:</label><br>
        <input type="file" name="cover_image" required><br>

        <label for="is_featured">Đề cử:</label>
        <input type="checkbox" name="is_featured" value="1"><br>

        <label for="genres">Thể loại:</label><br>
        <div class="checkbox-gird">
            <?php
            // Lấy danh sách thể loại từ bảng genres
            $genre_query = "SELECT * FROM genres";
            $genre_result = $conn->query($genre_query);

            // Kiểm tra kết quả truy vấn
            if ($genre_result->num_rows > 0) {
                while ($genre = $genre_result->fetch_assoc()) {
                    echo '<div class= "checkbox-item">';
                    echo '<input type="checkbox" name="genres[]" value="' . $genre['id'] . '"> ' . $genre['genre_name'] . '<br>';
                    echo '</div>';
                }
            } else {
                echo "Không có thể loại nào.";
            }
            ?>
        </div>
        <label>Chọn thể loại:</label><br>
        <input type="checkbox" name="comic_for_boy" value="1">
        <label for="comic_for_boy">Comic for Boy</label><br>

        <input type="checkbox" name="comic_for_girl" value="1">
        <label for="comic_for_girl">Comic for Girl</label><br>

        <div class="add_manga_in_web"><input type="submit" name="add_manga" value="Thêm Truyện"></div>
        
    </form>
    
    <form method="GET" action="index.php" id="searchForm">
    <input type="text" name="search_query" placeholder="Tìm kiếm truyện..." required>
    <button type="submit">Tìm kiếm</button>
    </form>


    <div class="navbar">
        <a href="index.php">Trang chủ</a>

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
                echo '<a href="index.php?genre=' . urlencode($genre_row['genre_name']) . '">' . htmlspecialchars($genre_row['genre_name']) . '</a>';
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
        <a href="#">Fangae thảo luận</a>
    </div>

    <?php
    // Lấy danh sách truyện được đề cử
    $featured_sql = "SELECT * FROM manga WHERE is_featured = 1";
    $featured_result = $conn->query($featured_sql);
    ?>
    <h3>Danh Sách Truyện Được Đề Cử</h3>
    <div class="slideshow-container">
        <div class="slides-wrapper">
            <?php while ($row = $featured_result->fetch_assoc()): ?>
                <div class="featured-item">
                    <a href="view_chapters.php?manga_id=<?php echo $row['id']; ?>" class="silde-item">
                        <img src="uploads/<?php echo $row['cover_image']; ?>" alt="<?php echo $row['title']; ?>" class="featured-cover">
                        <div class="featured-title"><?php echo $row['title']; ?></div>
                        <p class="featured-description"><?php echo $row['description']; ?></p>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Dấu hiệu chuyển slide -->
    <div style="text-align:center">
        <span class="dot"></span> 
        <span class="dot"></span> 
        <span class="dot"></span> 
    </div>


<!-- danh sách truyện tìm kiếm -->
    <h2>Danh Sách Truyện</h2>
    <?php if (!empty($search_query)): ?>
    <p>Danh sách truyện mà bạn tìm kiếm cho kết quả: "<?php echo htmlspecialchars($search_query); ?>"</p>
    <?php endif; ?>
    <div class="manga-grid">
        <?php if ($result->num_rows > 0): ?>
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
                    <a href="view_chapters.php?manga_id=<?php echo $row['id']; ?>">
                        <img src="uploads/<?php echo $row['cover_image']; ?>" alt="Manga Cover" class="cover-image">
                    </a>
                    <div class="manga-info">
                        <a href="view_chapters.php?manga_id=<?php echo $row['id']; ?>" class="manga-title">
                        <?php echo htmlspecialchars($row['title']); ?>
                        </a>
                        <p class="manga-description"><?php echo $row['description']; ?></p>
                        <p class="manga-author">Tác giả: <?php echo $row['author']; ?></p>
                    </div>
                </div>
                <div class="action-buttons">
                    <a href="index.php?delete=<?php echo $row['id']; ?>" class="delete-button" onclick="return confirm('Bạn có chắc chắn muốn xóa truyện này?')">Xóa</a>
                    <a href="edit_manga.php?id=<?php echo $row['id']; ?>" class="edit-button">Sửa</a>
                </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Không tìm thấy truyện nào.</p>
        <?php endif; ?>
    </div>



<!-- danh sách truyện ban đầu -->
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
                        <a href="view_chapters.php?manga_id=<?php echo $row['id']; ?>" class="manga-title">
                            <?php echo $row['title']; ?>
                        </a>
                        <p class="manga-description"><?php echo $row['description']; ?></p>
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

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>">Trước</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" <?php if ($i === $page) echo 'class="active"'; ?>><?php echo $i; ?></a>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>">Sau</a>
        <?php endif; ?>
    </div>

<script src="scripts.js"></script>
    
</body>
</html>

