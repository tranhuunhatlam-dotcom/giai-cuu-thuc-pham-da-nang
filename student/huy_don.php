<?php
// FILE: student/huy_don.php
// Mục đích: Hủy đơn hàng trước giờ đóng cửa (ít nhất cancel_before_close_minutes)
// Thực hiện hoàn 100% tiền vào ví và CỘNG LẠI kho nguyên tử trong Transaction

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('student');
$user = get_logged_in_user($conn);

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header("Location: " . BASE_URL . "student/don_hang.php");
    exit;
}

// Lấy thông tin đơn hàng và quán
$sql = "SELECT o.*, s.owner_id 
        FROM orders o 
        JOIN shops s ON o.shop_id = s.id 
        WHERE o.id = ? AND o.user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user['id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$order) {
    die("Lỗi: Không tìm thấy đơn hàng.");
}

// Kiểm tra trạng thái phải là 'cho_nhan'
if ($order['status'] !== 'cho_nhan') {
    die("Lỗi: Đơn hàng ở trạng thái '" . htmlspecialchars($order['status']) . "' không được phép hủy.");
}

// Kiểm tra thời gian: phải còn cách giờ đóng cửa ít nhất cancel_before_close_minutes (mặc định 90 phút)
$cancel_mins = (int)get_setting($conn, 'cancel_before_close_minutes', 90);
$close_timestamp = strtotime($order['close_at']);
$cancel_deadline = $close_timestamp - ($cancel_mins * 60);

if (time() >= $cancel_deadline) {
    die("Lỗi: Đã quá thời hạn hủy đơn! Quy định bắt buộc phải hủy trước giờ đóng cửa ít nhất {$cancel_mins} phút.");
}

// BẮT ĐẦU TRANSACTION HỦY ĐƠN & HOÀN TIỀN & CỘNG KHO
mysqli_begin_transaction($conn);

// 1. Đổi trạng thái đơn sang 'huy_boi_khach' kèm điều kiện trạng thái cũ là 'cho_nhan'
$stmt_up = mysqli_prepare($conn, "UPDATE orders SET status = 'huy_boi_khach' WHERE id = ? AND user_id = ? AND status = 'cho_nhan'");
mysqli_stmt_bind_param($stmt_up, "ii", $order['id'], $user['id']);
mysqli_stmt_execute($stmt_up);
$aff_up = mysqli_stmt_affected_rows($stmt_up);
mysqli_stmt_close($stmt_up);

if ($aff_up > 0) {
    // 2. Hoàn 100% tiền vào ví sinh viên (nếu đơn thanh toán qua ví)
    if (($order['payment_method'] ?? 'wallet') === 'wallet') {
        $stmt_refund = mysqli_prepare($conn, "UPDATE users SET balance = balance + ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_refund, "di", $order['price'], $user['id']);
        mysqli_stmt_execute($stmt_refund);
        mysqli_stmt_close($stmt_refund);

        // Ghi nhật ký ví tiền
        $reason = "Hoàn tiền 100% do sinh viên hủy đơn #" . $order['id'] . " đúng quy định";
        $stmt_log = mysqli_prepare($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt_log, "idsi", $user['id'], $order['price'], $reason, $order['id']);
        mysqli_stmt_execute($stmt_log);
        mysqli_stmt_close($stmt_log);
    }

    // 3. Cộng lại kho cho túi
    $stmt_stock = mysqli_prepare($conn, "UPDATE bags SET stock = stock + 1 WHERE id = ?");
    mysqli_stmt_bind_param($stmt_stock, "i", $order['bag_id']);
    mysqli_stmt_execute($stmt_stock);
    mysqli_stmt_close($stmt_stock);

    // Commit toàn bộ thay đổi
    mysqli_commit($conn);

    // Gửi thông báo cho sinh viên và chủ quán
    $notif_msg = (($order['payment_method'] ?? 'wallet') === 'wallet') 
        ? "Bạn đã hủy thành công đơn #" . $order['id'] . ". Số tiền " . format_money($order['price']) . " đã được hoàn lại vào ví."
        : "Bạn đã hủy thành công đơn #" . $order['id'] . " (Hình thức: " . (($order['payment_method'] ?? '') === 'cash' ? 'Tiền mặt' : 'VietQR') . ").";
    add_notification($conn, $user['id'], $notif_msg);
    add_notification($conn, $order['owner_id'], "Khách đã hủy đơn #" . $order['id'] . " trước thời hạn. Túi bất ngờ đã được tự động cộng lại vào kho hàng của quán.");

    header("Location: " . BASE_URL . "student/don_chi_tiet.php?id=" . $order['id']);
    exit;
} else {
    // Trạng thái đã bị đổi trước đó, hủy thất bại
    mysqli_rollback($conn);
    die("Lỗi: Không thể hủy đơn hàng do trạng thái đơn đã thay đổi.");
}
?>
