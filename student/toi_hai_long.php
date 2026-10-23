<?php
// FILE: student/toi_hai_long.php
// Mục đích: Sinh viên bấm "Tôi hài lòng" để nhả tiền sớm cho quán, chuyển đơn sang 'hoan_thanh'

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('student');
$user = get_logged_in_user($conn);

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header("Location: " . BASE_URL . "student/don_hang.php");
    exit;
}

// Lấy thông tin đơn hàng và chủ quán
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

if ($order['status'] !== 'da_nhan') {
    die("Lỗi: Chỉ các đơn ở trạng thái 'Đã nhận' mới có thể thực hiện xác nhận hài lòng.");
}

// BẮT ĐẦU TRANSACTION CHUYỂN TIỀN CHO QUÁN
mysqli_begin_transaction($conn);

// 1. Cập nhật trạng thái đơn sang 'hoan_thanh' với điều kiện cũ là 'da_nhan'
$stmt_up = mysqli_prepare($conn, "UPDATE orders SET status = 'hoan_thanh' WHERE id = ? AND user_id = ? AND status = 'da_nhan'");
mysqli_stmt_bind_param($stmt_up, "ii", $order['id'], $user['id']);
mysqli_stmt_execute($stmt_up);
$aff_up = mysqli_stmt_affected_rows($stmt_up);
mysqli_stmt_close($stmt_up);

if ($aff_up > 0) {
    $pay_method = $order['payment_method'] ?? 'wallet';

    // 2. Nếu thanh toán qua ví, cộng tiền vào ví chủ quán
    if ($pay_method === 'wallet') {
        $stmt_pay = mysqli_prepare($conn, "UPDATE users SET balance = balance + ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_pay, "di", $order['price'], $order['owner_id']);
        mysqli_stmt_execute($stmt_pay);
        mysqli_stmt_close($stmt_pay);

        // 3. Ghi nhật ký ví tiền của chủ quán
        $reason = "Nhận tiền đơn hàng hoàn thành sớm #" . $order['id'] . " (Khách đã bấm Tôi hài lòng)";
        $stmt_log = mysqli_prepare($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt_log, "idsi", $order['owner_id'], $order['price'], $reason, $order['id']);
        mysqli_stmt_execute($stmt_log);
        mysqli_stmt_close($stmt_log);
    }

    // Commit transaction
    mysqli_commit($conn);

    // Gửi thông báo cho hai bên
    if ($pay_method === 'wallet') {
        add_notification($conn, $order['owner_id'], "Khách hàng đã bấm 'Tôi hài lòng' cho đơn #" . $order['id'] . ". Tiền " . format_money($order['price']) . " đã được cộng ngay vào ví của bạn.");
    } else {
        add_notification($conn, $order['owner_id'], "Khách hàng đã bấm 'Tôi hài lòng' cho đơn #" . $order['id'] . " (" . ($pay_method === 'cash' ? 'Tiền mặt' : 'VietQR') . "). Đơn đã hoàn tất!");
    }
    add_notification($conn, $user['id'], "Cảm ơn bạn đã phản hồi hài lòng cho đơn #" . $order['id'] . "!");

    header("Location: " . BASE_URL . "student/don_chi_tiet.php?id=" . $order['id'] . "&satisfied=1");
    exit;
} else {
    mysqli_rollback($conn);
    die("Lỗi: Không thể hoàn tất đơn hàng do trạng thái đơn đã thay đổi.");
}
?>
