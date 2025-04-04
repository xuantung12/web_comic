<?php
session_start();
require_once 'db.php'; // Kết nối cơ sở dữ liệu

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $avatar = null;

    // Xử lý ảnh đại diện
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatar_name = time() . '_' . $_FILES['avatar']['name'];
        $avatar_path = 'uploads/' . $avatar_name;
        move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path);
        $avatar = $avatar_path;
    }

    // Thêm người dùng vào cơ sở dữ liệu
    $sql = "INSERT INTO users (username, email, password, avatar) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $email, $password, $avatar);

    if ($stmt->execute()) {
        echo "Đăng ký thành công! <a href='login.php'>Đăng nhập</a>";
    } else {
        echo "Lỗi: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
</head>
<body>
    <h1>Đăng ký</h1>
    <form action="register.php" method="POST" enctype="multipart/form-data">
        <label>Tên đăng nhập:</label>
        <input type="text" name="username" required><br>
        <label>Email:</label>
        <input type="email" name="email" required><br>
        <label>Mật khẩu:</label>
        <input type="password" name="password" required><br>
        <label>Ảnh đại diện:</label>
        <input type="file" name="avatar" accept="image/*"><br>
        <button type="submit">Đăng ký</button>
    </form>
</body>
</html>
