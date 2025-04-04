<?php
$servername = "localhost"; // Địa chỉ server MySQL (thường là localhost)
$username = "root";        // Tên đăng nhập MySQL (thường là root)
$password = "";            // Mật khẩu MySQL (thường là trống nếu dùng XAMPP)
$dbname = "comic_db";      // Tên cơ sở dữ liệu

// Kết nối tới MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}


?>

