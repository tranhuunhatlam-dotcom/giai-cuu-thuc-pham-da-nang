<?php
// FILE: shop/xoa_tui.php
// Mục đích: Xóa túi bất ngờ nếu chưa có đơn hàng hoạt động

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('shop_owner');
$user = get_logged_in_user($conn);
$shop = get_shop_by_owner_id($conn, $user['id']);

$bag_id = (int)($_GET['id'] ?? 0);
if ($bag_id <= 0) {
    header("Location: " . BASE_URL . "shop/danh_sach_tui.php");
    exit;
}

// Kiểm tra xem có đơn hàng nào của túi này không
$stmt_check = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM orders WHERE bag_id = ?");
mysqli_stmt_bind_param($stmt_check, "i", $bag_id);
mysqli_stmt_execute($stmt_check);
$res_check = mysqli_stmt_get_result($stmt_check);
$row = mysqli_fetch_assoc($res_check);
mysqli_stmt_close($stmt_check);

if ($row['total'] > 0) {
    // Đã có đơn hàng, không được xóa để bảo vệ lịch sử giao dịch
    header("Location: " . BASE_URL . "shop/danh_sach_tui.php?error_delete=1");
    exit;
}

// Tiến hành xóa túi
$stmt_del = mysqli_prepare($conn, "DELETE FROM bags WHERE id = ? AND shop_id = ?");
mysqli_stmt_bind_param($stmt_del, "ii", $bag_id, $shop['id']);
mysqli_stmt_execute($stmt_del);
mysqli_stmt_close($stmt_del);

header("Location: " . BASE_URL . "shop/danh_sach_tui.php?deleted=1");
exit;
?>
