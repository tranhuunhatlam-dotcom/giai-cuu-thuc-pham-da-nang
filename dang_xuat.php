<?php
// FILE: dang_xuat.php
// Mục đích: Xóa phiên làm việc (Session) và đăng xuất khỏi hệ thống

require_once __DIR__ . '/config.php';

// Hủy toàn bộ thông tin trong session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Điều hướng về trang đăng nhập với thông báo
header("Location: " . BASE_URL . "dang_nhap.php?logout=1");
exit;
?>
