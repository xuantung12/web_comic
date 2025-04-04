<?php
session_start();
include 'db.php'; // Đảm bảo file này kết nối đúng với database

// Kiểm tra nếu ID manga đã được truyền vào URL
$manga_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($manga_id === 0) {
    die("Truyện không tồn tại.");
}

// Kiểm tra xem ID có được truyền qua không
if (isset($_GET['id'])) {
    $manga_id = (int)$_GET['id'];

    // Truy vấn để lấy thông tin của truyện
    $sql = "SELECT m.title, m.statuss, m.description, m.author, g.genre_name 
            FROM manga m
            JOIN manga_genres mg ON m.id = mg.manga_id
            JOIN genres g ON mg.genre_id = g.id
            WHERE m.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $manga_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Kiểm tra nếu có kết quả
    if ($result->num_rows > 0) {
        // Lấy dữ liệu của truyện
        $manga = $result->fetch_assoc();

        // Hiển thị ảnh bìa, tên truyện và trạng thái
        $title = htmlspecialchars($manga['title']);
        $statuss = htmlspecialchars($manga['statuss']);
        $decscription = htmlspecialchars($manga['description']);
        $author = htmlspecialchars($manga["author"]);
        $cover_image = $_SESSION['cover_image'];
        $cover_image_path = "uploads/" . $cover_image;

        // // Hiển thị ảnh bìa
        // echo "<div class='cover-image-wrapper'>";
        // echo "<img src='$cover_image_path' alt='Cover Image' width='200' class='cover-image'>";
        // echo "</div>";
        
        // echo "<p><strong class='name-title'></strong> $title</p>";
        // // Hiển thị tiêu đề và trạng thái
        // echo "<div class='details-wrapper'>";
        // echo "<p><strong class='name-statuss'>Tiến độ:</strong> $statuss</p>";
        // echo "<p><strong class='name-author'>Tác giả:</strong> $author</p>";
        // echo "<p><strong class='name-decription'>Mô tả truyện:</strong> $decscription</p>";
        
        // Truy vấn để lấy thể loại dựa trên tiêu đề manga
        // $sql = "SELECT g.genre_name FROM manga m
        //         JOIN manga_genres mg ON m.id = mg.manga_id
        //         JOIN genres g ON mg.genre_id = g.id
        //         WHERE m.title = ?";

        // $stmt = $conn->prepare($sql);
        // $stmt->bind_param('s', $title);
        // $stmt->execute();
        // $result = $stmt->get_result();

        // // Hiển thị thể loại
        // echo "<p><strong>Thể loại:</strong> ";
        // $genres = [];
        // while ($genre_row = $result->fetch_assoc()) {
        //     $genres[] = htmlspecialchars($genre_row['genre_name']);
        // }
        // echo implode(', ', $genres); // Nối các thể loại với nhau
        // echo "</p>";

        echo "</div>";
    } else {
        echo "<p>Không có thông tin để hiển thị.</p>";
    }
}

// Kiểm tra xem có phải vào trang từ test1.php không
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'test1.php') !== false) {
    // Cập nhật lượt xem nếu người dùng vào từ test1.php
    $update_views = $conn->prepare("UPDATE manga SET views = views + 1 WHERE id = ?");
    $update_views->bind_param("i", $manga_id);
    $update_views->execute();
}

// Kiểm tra nếu người dùng đã đăng nhập
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Xử lý khi người dùng nhấn thích
if (isset($_POST['like_chapter'])) {
    $chapter_id = intval($_POST['chapter_id']);

    if ($user_id) {
        // Kiểm tra xem đã like trước đó chưa
        $check_like = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND chapter_id = ?");
        $check_like->bind_param("ii", $user_id, $chapter_id);
        $check_like->execute();
        $result = $check_like->get_result();

        if ($result->num_rows > 0) {
            // Nếu đã thích thì xóa like (bỏ thích)
            $delete_like = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND chapter_id = ?");
            $delete_like->bind_param("ii", $user_id, $chapter_id);
            if ($delete_like->execute()) {
                echo "Đã bỏ thích thành công!";
            }
        } else {
            // Nếu chưa thích thì thêm mới
            $insert_like = $conn->prepare("INSERT INTO likes (user_id, chapter_id) VALUES (?, ?)");
            $insert_like->bind_param("ii", $user_id, $chapter_id);
            if ($insert_like->execute()) {
                echo "Thích thành công!";
            }
        }
    } else {
        echo "Vui lòng đăng nhập để thích truyện.";
    }

    // Sau khi thực hiện hành động, chuyển hướng lại trang
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();  // Ngừng script để tránh tiếp tục thực thi các mã phía dưới
}

// Xử lý khi người dùng nhấn theo dõi
if (isset($_POST['follow_manga'])) {
    if ($user_id) {
        // Kiểm tra xem đã follow trước đó chưa
        $check_follow = $conn->prepare("SELECT * FROM follows WHERE user_id = ? AND manga_id = ?");
        $check_follow->bind_param("ii", $user_id, $manga_id);
        $check_follow->execute();
        $result = $check_follow->get_result();

        if ($result->num_rows > 0) {
            // Nếu đã follow thì xóa follow (bỏ theo dõi)
            $delete_follow = $conn->prepare("DELETE FROM follows WHERE user_id = ? AND manga_id = ?");
            $delete_follow->bind_param("ii", $user_id, $manga_id);
            if ($delete_follow->execute()) {
                echo "Đã bỏ theo dõi thành công!";
            }
        } else {
            // Nếu chưa follow thì thêm mới
            $insert_follow = $conn->prepare("INSERT INTO follows (user_id, manga_id) VALUES (?, ?)");
            $insert_follow->bind_param("ii", $user_id, $manga_id);
            if ($insert_follow->execute()) {
                echo "Theo dõi thành công!";
            }
        }
    } else {
        echo "Vui lòng đăng nhập để theo dõi truyện.";
    }

    // Sau khi thực hiện hành động, chuyển hướng lại trang
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();  // Ngừng script để tránh tiếp tục thực thi các mã phía dưới
}

// Lấy thông tin truyện
$query = $conn->prepare("SELECT * FROM manga WHERE id = ?");
$query->bind_param("i", $manga_id);
$query->execute();
$result = $query->get_result();
$manga = $result->fetch_assoc();

if (!$manga) {
    die("Truyện không tồn tại.");
}

// Lấy số lượt thích và theo dõi của truyện
$query_likes = $conn->prepare("SELECT COUNT(*) AS total_likes FROM likes WHERE chapter_id = ?");
$query_likes->bind_param("i", $manga_id);
$query_likes->execute();
$total_likes = $query_likes->get_result()->fetch_assoc()['total_likes'];

$query_follows = $conn->prepare("SELECT COUNT(*) AS total_follows FROM follows WHERE manga_id = ?");
$query_follows->bind_param("i", $manga_id);
$query_follows->execute();
$total_follows = $query_follows->get_result()->fetch_assoc()['total_follows'];

// Kiểm tra nếu người dùng đã like hoặc follow truyện này
$is_liked = false;
$is_followed = false;

if ($user_id) {
    // Kiểm tra nếu người dùng đã like
    $check_like = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND chapter_id = ?");
    $check_like->bind_param("ii", $user_id, $manga_id);
    $check_like->execute();
    $like_result = $check_like->get_result();
    if ($like_result->num_rows > 0) {
        $is_liked = true;
    }

    // Kiểm tra nếu người dùng đã follow
    $check_follow = $conn->prepare("SELECT * FROM follows WHERE user_id = ? AND manga_id = ?");
    $check_follow->bind_param("ii", $user_id, $manga_id);
    $check_follow->execute();
    $follow_result = $check_follow->get_result();
    if ($follow_result->num_rows > 0) {
        $is_followed = true;
    }
}

// Xử lý thêm chapter mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $chapter_number = null;
    $image = $_FILES['image']['name'];
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($image);

    // Lấy số chapter tiếp theo
    $sql = "SELECT MAX(chapter_number) AS max_chapter FROM chapters WHERE manga_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $manga_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $chapter_number = $row['max_chapter'] + 1; // Tính toán chapter tiếp theo

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        $sql = "INSERT INTO chapters (manga_id, chapter_number, image) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $manga_id, $chapter_number, $target_file);

        if ($stmt->execute()) {
            header("Location: view_chapter.php?id=$manga_id");
            echo "<p style='color: green;'>Thêm chapter thành công!</p>";
            exit();
            
        } else {
            echo "<p style='color: red;'>Lỗi khi thêm chapter: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Lỗi khi upload hình ảnh.</p>";
    }
}

// Xử lý xóa chapter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $chapter_id = intval($_POST['chapter_id']);

    // Lấy thông tin chapter để xóa file hình ảnh
    $sql = "SELECT image FROM chapters WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $chapter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $chapter = $result->fetch_assoc();

    if ($chapter) {
        // Xóa file hình ảnh
        if (file_exists($chapter['image'])) {
            unlink($chapter['image']);
        }

        // Xóa chapter trong cơ sở dữ liệu
        $sql = "DELETE FROM chapters WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $chapter_id);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>Xóa chapter thành công!</p>";
        } else {
            echo "<p style='color: red;'>Lỗi khi xóa chapter: " . $conn->error . "</p>";
        }
    }
}

// Lấy danh sách chapter của truyện
$sql = "SELECT * FROM chapters WHERE manga_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $manga_id);
$stmt->execute();
$chapters = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đọc truyện</title>
    <link rel="stylesheet" href="view_chapte.css">
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


    <div class="comic-contents">
        <div class="background-comic">
            <class class="cover-image-wrapper">
                <img src="<?= htmlspecialchars($cover_image_path) ?>" alt='Cover Image' width='200' class='cover-image'>
            </class>
            <div class="detail-wrapper">
                <class class="title-wrapper">
                    <p><strong class="name-title"></strong><?php echo htmlspecialchars($manga['title'])?></p>
                </class>
                <class class="detail-wrapper1">
                    <p><strong class="name-status">Tiến độ:</strong><?php echo htmlspecialchars($manga['statuss'])?></p>
                    <p><strong class="name-athor">Tác giả:</strong><?php echo htmlspecialchars($manga['author']) ?></p>
                    <p><strong class="person-view">Lượt xem:</strong> <?php echo $manga['views']; ?></p>
                    <p><strong class="person-like">Lượt thích:</strong> <?php echo $total_likes; ?></p>
                    <p><strong class="person-follows">Lượt theo dõi:</strong> <?php echo $total_follows; ?></p>
                    <p>
                        <strong class="name-gender">Thể loại:</strong>
                        <?php $sql = "SELECT g.genre_name FROM manga m
                                JOIN manga_genres mg ON m.id = mg.manga_id
                                JOIN genres g ON mg.genre_id = g.id
                                WHERE m.title = ?";

                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('s', $title);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        // Hiển thị thể loại
                        $genres = [];
                        while ($genre_row = $result->fetch_assoc()) {
                            $genres[] = htmlspecialchars($genre_row['genre_name']);
                        }
                        echo implode(', ', $genres); // Nối các thể loại với nhau
                        ?>
                    </p>
                    
                </class>

                <div class="buttons">
                    <div class="read-from-the-beginning">
                        <!-- Nút đọc từ đầu  -->
                        <?php
                        // Truy vấn chapter đầu tiên (chapter 1)
                        $first_chapter = $conn->query("SELECT id FROM chapters WHERE chapter_number = 1 LIMIT 1")->fetch_assoc();
                        ?>

                        <?php if ($first_chapter): ?>
                            <button onclick="window.location.href='read_chapter.php?chapter_id=<?= $first_chapter['id'] ?>'">
                                Đọc từ đầu
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="like-button">
                        <!-- Nút like -->
                        <form method="POST">
                            <input type="hidden" name="chapter_id" value="<?= $manga_id ?>">
                            <button type="submit" name="like_chapter">
                                <?= isset($user_id) && $is_liked ? 'Bỏ thích' : 'Thích' ?>
                            </button>
                        </form>
                    </div>
                    <div class="follow-button">
                        <!-- Nút follow -->
                        <form method="POST">
                            <input type="hidden" name="manga_id" value="<?= $manga_id ?>">
                            <button type="submit" name="follow_manga">
                                <?= isset($user_id) && $is_followed ? 'Bỏ theo dõi' : 'Theo dõi' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="desciption">
            <p><strong class="name-decription">Mô tả truyện:</strong><br><?php echo htmlspecialchars($manga['description'])?></p>
        </div> 
        <div class="list-add-chapter">
            <div class="add-chapter">
                <h2>Thêm chapter mới</h2>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <label for="chapter_number">Số chương:</label>
                    <input type="number" name="chapter_number" id="chapter_number" value="<?= $chapter_number ?>" disabled>
                    <br>
                    <label for="image">Hình ảnh:</label>
                    <input type="file" name="image" id="image" required>
                    <br>
                    <button type="submit">Thêm chapter</button>
                </form>
            </div>
            <div class="list-chapter">            
                <h2>Danh sách chapter</h2>
                <div class="list-table-chapter">
                    <ul>
                        <?php while ($chapter = $chapters->fetch_assoc()): ?>
                            <li class="chapter">
                                <strong class="chapter-number">
                                    <!-- Liên kết chapter_number tới trang read_chapter.php -->
                                    <a href="read_chapter.php?chapter_id=<?= $chapter['id'] ?>">Chapter <?= $chapter['chapter_number'] ?></a>
                                </strong>
                                <strong class="chapter-date">
                                    <!-- <img src="<?= htmlspecialchars($chapter['image']) ?>" alt="Hình ảnh chapter">
                                    <br> -->
                                    <?= $chapter['created_at'] ?>
                                </strong>

                                <!-- Nút sửa và xóa -->
                                <form action="" method="post" style="display: inline;" name="button-edit-delete">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="chapter_id" value="<?= $chapter['id'] ?>">
                                    <button type="submit" onclick="return confirm('Bạn có chắc chắn muốn xóa chapter này?')">Xóa</button>
                                </form>
                                <a href="edit_chapter.php?chapter_id=<?= $chapter['id'] ?>">Sửa</a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>  
        </div>
    </div>
</body>
</html>
