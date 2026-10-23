<?php
// FILE: shop/nhap_ma.php
// Mục đích: Chủ quán nhập mã nhận hàng 6 ký tự để bàn giao túi bất ngờ
// Ràng buộc: Kiểm tra mã, đúng quán, đúng trạng thái, chưa quá giờ đóng cửa, chuyển da_nhan + ghi picked_up_at / hold_until
// Báo lỗi tiếng Việt chi tiết cho từng trường hợp

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('shop_owner');
$user = get_logged_in_user($conn);
$shop = get_shop_by_owner_id($conn, $user['id']);

if (!$shop) {
    die("Lỗi: Không tìm thấy quán của bạn.");
}

$error = '';
$success = '';
$order_confirmed = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup_code = strtoupper(trim($_POST['pickup_code'] ?? ''));

    if (empty($pickup_code)) {
        $error = 'Vui lòng nhập mã nhận hàng 6 ký tự của khách hàng.';
    } else {
        // Tìm thông tin đơn hàng theo pickup_code
        $sql = "SELECT o.*, b.name AS bag_name, u.name AS student_name, u.email AS student_email, s.name AS shop_name, s.owner_id
                FROM orders o
                JOIN bags b ON o.bag_id = b.id
                JOIN users u ON o.user_id = u.id
                JOIN shops s ON o.shop_id = s.id
                WHERE o.pickup_code = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $pickup_code);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $order = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        // Kiểm tra các trường hợp vi phạm với thông báo tiếng Việt rõ ràng
        if (!$order) {
            $error = "Mã nhận hàng '{$pickup_code}' không tồn tại trên hệ thống. Vui lòng kiểm tra lại chính xác từng ký tự!";
        } elseif ($order['shop_id'] != $shop['id']) {
            $error = "Mã nhận hàng '{$pickup_code}' thuộc về một cửa hàng khác (" . htmlspecialchars($order['shop_name']) . "), không phải của quán bạn!";
        } elseif ($order['status'] === 'da_nhan' || $order['status'] === 'hoan_thanh') {
            $error = "Mã nhận hàng này đã được xác nhận nhận túi trước đó vào lúc " . format_datetime($order['picked_up_at']) . "! Không thể nhận lần 2.";
        } elseif ($order['status'] === 'huy_boi_khach') {
            $error = "Đơn hàng này đã bị khách hàng hủy trước đó! Quán không giao túi này.";
        } elseif ($order['status'] === 'huy_vang_mat') {
            $error = "Đơn hàng này đã bị hệ thống hủy do khách vắng mặt quá giờ đóng cửa quán! Tiền bồi thường đã chuyển vào ví quán.";
        } elseif ($order['status'] === 'tranh_chap') {
            $error = "Đơn hàng này đang ở trạng thái tranh chấp / khiếu nại!";
        } elseif ($order['status'] !== 'cho_nhan') {
            $error = "Đơn hàng đang ở trạng thái '" . htmlspecialchars($order['status']) . "', không thể nhận hàng.";
        } elseif (strtotime($order['close_at']) < time()) {
            $error = "Đã quá giờ đóng cửa quán (" . format_datetime($order['close_at']) . "). Đơn hàng đã hết hạn nhận túi!";
        } else {
            // ĐỦ ĐIỀU KIỆN HỢP LỆ: TIẾN HÀNH TRANSACTION CHUYỂN SANG 'da_nhan'
            // Chú ý nghiệp vụ: KHÔNG trừ kho thêm, KHÔNG chuyển tiền ngay.
            // Tiền giữ thêm hold_minutes (mặc định 45 phút) để khách mang về kiểm tra.
            $hold_minutes = (int)get_setting($conn, 'hold_minutes', 45);

            mysqli_begin_transaction($conn);

            // Cập nhật trạng thái kèm điều kiện trạng thái cũ là 'cho_nhan'
            $sql_up = "UPDATE orders 
                       SET status = 'da_nhan', 
                           picked_up_at = NOW(), 
                           hold_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) 
                       WHERE id = ? AND shop_id = ? AND status = 'cho_nhan'";
            $stmt_up = mysqli_prepare($conn, $sql_up);
            mysqli_stmt_bind_param($stmt_up, "iii", $hold_minutes, $order['id'], $shop['id']);
            mysqli_stmt_execute($stmt_up);
            $affected = mysqli_stmt_affected_rows($stmt_up);
            mysqli_stmt_close($stmt_up);

            if ($affected > 0) {
                mysqli_commit($conn);

                // Gửi thông báo cho sinh viên
                add_notification($conn, $order['user_id'], "Quán '" . $shop['name'] . "' đã xác nhận bàn giao túi '" . $order['bag_name'] . "' cho bạn. Đơn chuyển sang Đã nhận. Tiền sẽ được tạm giữ {$hold_minutes} phút để bạn kiểm tra.");

                $success = "Xác nhận nhận hàng thành công cho đơn #{$order['id']} (Khách hàng: {$order['student_name']})! Tiền " . format_money($order['price']) . " đang được giữ {$hold_minutes} phút trước khi tự động hoàn tất và cộng vào ví của quán.";
                
                // Lấy lại đơn đã cập nhật để hiển thị thông tin xác nhận
                $stmt_get = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ?");
                mysqli_stmt_bind_param($stmt_get, "i", $order['id']);
                mysqli_stmt_execute($stmt_get);
                $res_get = mysqli_stmt_get_result($stmt_get);
                $order_confirmed = mysqli_fetch_assoc($res_get);
                mysqli_stmt_close($stmt_get);
            } else {
                mysqli_rollback($conn);
                $error = 'Giao dịch thất bại do trạng thái đơn hàng đã thay đổi trên hệ thống!';
            }
        }
    }
}

$page_title = 'Nhập mã nhận hàng';
require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 620px; margin: 30px auto;">
    <div class="card" style="border-radius: 24px; padding: 36px 30px; box-shadow: 0 16px 36px -4px rgba(234, 88, 12, 0.12);">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 56px; height: 56px; background: var(--secondary-gradient); color: white; border-radius: 18px; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 12px; box-shadow: 0 8px 20px var(--secondary-glow);">
                ⚡
            </div>
            <h1 style="font-size: 24px; font-weight: 900; color: var(--text-dark); letter-spacing: -0.5px;">QUẦY THU NGÂN - XÁC NHẬN MÃ NHẬN HÀNG</h1>
            <p style="font-size: 13.5px; color: var(--text-muted); margin-top: 4px;">
                Quán: <strong style="color: var(--secondary-color);"><?php echo htmlspecialchars($shop['name']); ?></strong> (Q. <?php echo htmlspecialchars($shop['district']); ?>)
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="border-radius: 14px;">
                <strong>⚠️ LỖI:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="border-radius: 14px;">
                <strong>✅ THÀNH CÔNG:</strong> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($order_confirmed): ?>
            <div style="background: #f0fdf4; border: 1.5px solid #86efac; padding: 18px 20px; border-radius: 16px; margin-bottom: 24px; font-size: 14px;">
                <h4 style="color: #047857; margin-bottom: 8px; font-weight: 800;">🎉 Chi tiết đơn vừa bàn giao:</h4>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Mã đơn hàng:</span> <strong>#<?php echo $order_confirmed['id']; ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Hình thức thanh toán:</span> 
                    <strong><?php echo get_payment_method_badge($order_confirmed['payment_method'] ?? 'wallet', $order_confirmed['payment_status'] ?? 'da_thanh_toan'); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Thời điểm nhận:</span> <strong><?php echo format_datetime($order_confirmed['picked_up_at']); ?></strong>
                </div>

                <?php if (($order_confirmed['payment_method'] ?? 'wallet') === 'cash'): ?>
                    <div style="background: #fffbeb; border: 1.5px solid #fde68a; padding: 12px 14px; border-radius: 10px; margin-top: 10px; font-size: 13.5px; color: #92400e;">
                        💰 <strong>THU TIỀN MẶT TẠI QUẦY:</strong> Vui lòng thu trực tiếp <strong><?php echo format_money($order_confirmed['price']); ?></strong> từ khách hàng trước khi khách rời quán!
                    </div>
                <?php elseif (($order_confirmed['payment_method'] ?? 'wallet') === 'bank_qr'): ?>
                    <div style="background: #faf5ff; border: 1.5px solid #d8b4fe; padding: 12px 14px; border-radius: 10px; margin-top: 10px; font-size: 13.5px; color: #6b21a8;">
                        📲 <strong>CHUYỂN KHOẢN VIETQR:</strong> Khách chọn chuyển khoản MB Bank <strong><?php echo format_money($order_confirmed['price']); ?></strong>. Vui lòng đối soát biến động số dư.
                    </div>
                <?php else: ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Thời hạn giữ tiền Escrow:</span> <strong style="color: #047857;"><?php echo format_datetime($order_confirmed['hold_until']); ?></strong>
                    </div>
                    <div style="background: white; border: 1px solid #dcfce7; padding: 10px 14px; border-radius: 10px; font-size: 12.5px; color: #166534; line-height: 1.5;">
                        🛡️ Sau mốc thời gian trên (nếu khách không khiếu nại) hoặc khi khách bấm "Tôi hài lòng", tiền sẽ tự động chuyển vào số dư ví của quán.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>shop/nhap_ma.php" method="POST">
            <div class="form-group" style="text-align: center;">
                <label class="form-label" style="font-size: 15px; margin-bottom: 12px; font-weight: 800; color: var(--text-dark);">
                    Nhập mã 6 ký tự hiển thị trên màn hình của khách:
                </label>
                <div style="position: relative; max-width: 320px; margin: 0 auto 18px auto;">
                    <input type="text" name="pickup_code" class="form-control" style="font-size: 32px; text-align: center; font-family: monospace; letter-spacing: 8px; text-transform: uppercase; font-weight: 900; padding: 14px; border: 2px solid #fed7aa; color: var(--secondary-color); background: #fff7ed; border-radius: 16px;" placeholder="VD: 7K9M2P" maxlength="10" autofocus required>
                </div>
            </div>

            <button type="submit" class="btn btn-secondary btn-block btn-lg" style="box-shadow: 0 8px 24px var(--secondary-glow);">
                ⚡ XÁC NHẬN BÀN GIAO TÚI CHO KHÁCH &rarr;
            </button>
        </form>

        <div style="margin-top: 28px; padding: 16px; background: #f8fafc; border: 1px solid var(--border-color); border-radius: 16px; font-size: 13px; color: var(--text-muted); line-height: 1.6;">
            <strong style="color: var(--text-dark); display: block; margin-bottom: 4px;">🛡️ Cam kết an toàn & Quy tắc hệ thống:</strong>
            • Tồn kho đã trừ ngay khi khách đặt đơn, không bị trừ trùng khi nhập mã.<br>
            • Tiền tạm giữ 45 phút để khách mang về kiểm tra (chống hành vi tráo hàng, hàng kém chất lượng).<br>
            • Quán được bảo vệ nếu khách vu khống; được gửi phản đối trong 48h để Admin phân xử.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
