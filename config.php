<?php
// FILE: config.php
// Mục đích: Cấu hình hệ thống, múi giờ, phiên làm việc và kết nối CSDL MySQL bằng mysqli thủ tục

// 1. Thiết lập múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// 2. Khởi động phiên làm việc (Session) nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Thông tin kết nối MySQL (môi trường XAMPP mặc định)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'giaicuu';

// 4. Kết nối CSDL bằng mysqli (hướng thủ tục)
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// 5. Kiểm tra kết nối
if (!$conn) {
    die("Lỗi kết nối cơ sở dữ liệu: " . mysqli_connect_error());
}

// 6. Đảm bảo hỗ trợ tiếng Việt có dấu đầy đủ (utf8mb4)
mysqli_set_charset($conn, "utf8mb4");

// 7. Định nghĩa đường dẫn gốc website
define('BASE_URL', '/giaicuu/');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
?>
