<?php
// FILE: student/don_chi_tiet.php
// Mục đích: Chi tiết đơn hàng, hiển thị mã nhận hàng cỡ lớn, đếm ngược thời gian và các hành động (Hủy đơn, Tôi hài lòng, Báo cáo gian lận)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('student');
$user = get_logged_in_user($conn);

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header("Location: " . BASE_URL . "student/don_hang.php");
    exit;
}

// Lấy thông tin đơn hàng của sinh viên
$sql = "SELECT o.*, b.name AS bag_name, b.description AS bag_desc, b.image AS bag_image, 
               b.pickup_start, b.pickup_end,
               s.name AS shop_name, s.address AS shop_address, s.district AS shop_district, s.type AS shop_type
        FROM orders o
        JOIN bags b ON o.bag_id = b.id
        JOIN shops s ON o.shop_id = s.id
        WHERE o.id = ? AND o.user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user['id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$order) {
    die("Lỗi: Không tìm thấy đơn hàng hoặc đơn không thuộc về bạn.");
}

// Lấy thông tin cấu hình thời gian
$cancel_mins = (int)get_setting($conn, 'cancel_before_close_minutes', 90);
$hold_mins = (int)get_setting($conn, 'hold_minutes', 45);

$now = time();
$close_timestamp = strtotime($order['close_at']);
$cancel_deadline_timestamp = $close_timestamp - ($cancel_mins * 60);
$can_cancel = ($order['status'] === 'cho_nhan' && $now < $cancel_deadline_timestamp);
$rem_cancel_mins = ($cancel_deadline_timestamp > $now) ? (int)ceil(($cancel_deadline_timestamp - $now) / 60) : 0;

// Lấy thông tin tranh chấp nếu đơn này đang có tranh chấp
$dispute = null;
if (in_array($order['status'], ['tranh_chap', 'hoan_tien_sv', 'chuyen_tien_quan'])) {
    $stmt_d = mysqli_prepare($conn, "SELECT * FROM disputes WHERE order_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_d, "i", $order['id']);
    mysqli_stmt_execute($stmt_d);
    $res_d = mysqli_stmt_get_result($stmt_d);
    $dispute = mysqli_fetch_assoc($res_d);
    mysqli_stmt_close($stmt_d);
}

require_once __DIR__ . '/../services/ai_service.php';

// Xử lý gửi đánh giá 5 sao
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_review'])) {
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');
    if ($rating < 1 || $rating > 5) $rating = 5;

    $stmt_chk = mysqli_prepare($conn, "SELECT id FROM reviews WHERE order_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_chk, "i", $order['id']);
    mysqli_stmt_execute($stmt_chk);
    $res_chk = mysqli_stmt_get_result($stmt_chk);
    if (!mysqli_fetch_assoc($res_chk)) {
        // Phân tích cảm xúc và gắn nhãn tự động bằng AI
        $ai_res = ai_analyze_review_sentiment($comment, $rating);
        $ai_sentiment = $ai_res['sentiment'];
        $ai_tags = $ai_res['tags'];

        $stmt_ins_rev = mysqli_prepare($conn, "INSERT INTO reviews (order_id, user_id, shop_id, rating, comment, ai_sentiment, ai_tags, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt_ins_rev, "iiiisss", $order['id'], $user['id'], $order['shop_id'], $rating, $comment, $ai_sentiment, $ai_tags);
        mysqli_stmt_execute($stmt_ins_rev);
        mysqli_stmt_close($stmt_ins_rev);

        header("Location: " . BASE_URL . "student/don_chi_tiet.php?id=" . $order['id'] . "&reviewed=1");
        exit;
    }
    mysqli_stmt_close($stmt_chk);
}

// Lấy thông tin review hiện có (nếu có)
$existing_review = null;
$stmt_r = mysqli_prepare($conn, "SELECT * FROM reviews WHERE order_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_r, "i", $order['id']);
mysqli_stmt_execute($stmt_r);
$res_r = mysqli_stmt_get_result($stmt_r);
$existing_review = mysqli_fetch_assoc($res_r);
mysqli_stmt_close($stmt_r);

$notice = '';
if (isset($_GET['success'])) {
    $notice = 'Đặt túi bất ngờ thành công! Hãy lưu lại mã nhận hàng để lấy túi nhé.';
}
if (isset($_GET['satisfied'])) {
    $notice = 'Cảm ơn bạn! Đơn hàng đã hoàn thành và tiền đã được chuyển cho chủ quán. Bạn có thể để lại đánh giá bên dưới!';
}
if (isset($_GET['reported'])) {
    $notice = 'Báo cáo khiếu nại gian lận của bạn đã được ghi nhận. Tiền đang được tạm giữ an toàn!';
}
if (isset($_GET['reviewed'])) {
    $notice = 'Đánh giá của bạn đã được gửi thành công! Cảm ơn bạn đã chia sẻ trải nghiệm.';
}

$page_title = 'Chi tiết đơn hàng #' . $order['id'];
require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 750px; margin: 20px auto;">
    <div style="margin-bottom: 15px;">
        <a href="<?php echo BASE_URL; ?>student/don_hang.php" style="text-decoration: none; color: var(--primary-color); font-weight: 600;">
            &larr; Quay lại danh sách đơn hàng
        </a>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">
            <span>Chi Tiết Đơn Hàng #<?php echo $order['id']; ?></span>
            <span><?php echo get_order_status_badge($order['status']); ?></span>
        </div>

        <!-- MÃ NHẬN HÀNG NỔI BẬT KIỂU VÉ THƯỞNG (TICKET STYLE) -->
        <?php if ($order['status'] === 'cho_nhan'): ?>
            <div class="pickup-code-box">
                <span class="badge badge-warning" style="font-size: 13px; padding: 6px 14px; margin-bottom: 8px;">
                    🎫 VÉ NHẬN HÀNG TẠI QUẦN
                </span>
                <div style="font-size: 15px; font-weight: 800; color: #9a3412; margin-top: 6px;">
                    ĐƯA MÃ NÀY CHO NHÂN VIÊN KHI ĐẾN LẤY TÚI:
                </div>
                <div class="pickup-code-display">
                    <?php echo htmlspecialchars($order['pickup_code']); ?>
                </div>
                <div style="font-size: 13.5px; color: #78350f; max-width: 500px; margin: 0 auto;">
                    Nhân viên quán sẽ nhập mã 6 ký tự này vào hệ thống để xác nhận bàn giao túi cho bạn.
                </div>
            </div>
        <?php else: ?>
            <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; display: flex; justify-content: space-between; align-items: center;">
                <span>Mã nhận hàng của đơn:</span>
                <strong style="font-family: monospace; font-size: 20px; color: #0f172a; letter-spacing: 2px;"><?php echo htmlspecialchars($order['pickup_code']); ?></strong>
            </div>
        <?php endif; ?>

        <!-- Thông tin món ăn & Quán & Giao hàng -->
        <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                <h3 style="color: var(--primary-color); margin: 0;"><?php echo htmlspecialchars($order['bag_name']); ?></h3>
                <?php if (($order['delivery_type'] ?? 'pickup') === 'delivery'): ?>
                    <span class="badge badge-success" style="font-size: 12.5px; padding: 5px 12px;">🛵 Đơn Đặt Ship Tận Nơi</span>
                <?php else: ?>
                    <span class="badge badge-info" style="font-size: 12.5px; padding: 5px 12px;">🏪 Tự Đến Quán Lấy</span>
                <?php endif; ?>
            </div>

            <div style="font-size: 14px; color: var(--text-dark); line-height: 1.8;">
                🏪 Quán: <strong><?php echo htmlspecialchars($order['shop_name']); ?></strong> (<?php echo htmlspecialchars($order['shop_type']); ?>)<br>
                📍 Địa chỉ quán: <strong><?php echo htmlspecialchars($order['shop_address']); ?>, Q. <?php echo htmlspecialchars($order['shop_district']); ?></strong><br>
                
                <?php if (($order['delivery_type'] ?? 'pickup') === 'delivery'): ?>
                    <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 10px; padding: 10px 14px; margin: 10px 0;">
                        <strong style="color: #166534; font-size: 14px;">📦 Thông Tin Giao Hàng Shipper:</strong><br>
                        🛵 Địa chỉ nhận: <strong><?php echo htmlspecialchars($order['shipping_address'] ?? 'Ký túc xá sinh viên'); ?></strong><br>
                        📞 SĐT người nhận: <strong><?php echo htmlspecialchars($order['shipping_phone'] ?? 'Chưa cung cấp'); ?></strong><br>
                        💵 Phí giao hàng: <strong><?php echo format_money($order['shipping_fee'] ?? 12000); ?></strong> (Đã tính vào tổng thanh toán)<br>
                        <small style="color: #15803d;">💡 Shipper của quán sẽ gọi điện khi đến nơi. Bạn chỉ cần đọc Mã nhận hàng <strong><?php echo htmlspecialchars($order['pickup_code']); ?></strong> để xác nhận!</small>
                    </div>
                <?php endif; ?>

                💵 Tổng thanh toán: <strong style="color: var(--secondary-color); font-size: 16px;"><?php echo format_money($order['price']); ?></strong>
                <span style="margin-left: 8px;"><?php echo get_payment_method_badge($order['payment_method'] ?? 'wallet', $order['payment_status'] ?? 'da_thanh_toan'); ?></span><br>

                <?php if (($order['payment_method'] ?? 'wallet') === 'bank_qr'): ?>
                    <!-- Khung thông tin Chuyển khoản VietQR -->
                    <div style="background: #faf5ff; border: 1.5px solid #d8b4fe; border-radius: 14px; padding: 16px 20px; margin: 12px 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <strong style="color: #6b21a8; font-size: 15px; display: flex; align-items: center; gap: 6px;">
                                <span>📲</span> Thông Tin Chuyển Khoản Ngân Hàng (VietQR)
                            </strong>
                            <?php if (($order['payment_status'] ?? '') === 'da_thanh_toan'): ?>
                                <span class="badge badge-success" style="font-size: 12px;">✓ Đã xác nhận thanh toán</span>
                            <?php else: ?>
                                <span class="badge badge-purple" style="font-size: 12px;">⏳ Đang chờ xác nhận tiền về</span>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 18px; align-items: center; flex-wrap: wrap;">
                            <div>
                                <img src="https://img.vietqr.io/image/MB-0345678999-compact2.png?amount=<?php echo (int)$order['price']; ?>&addInfo=GIAICUU+DON<?php echo $order['id']; ?>+MA<?php echo $order['pickup_code']; ?>&accountName=QUY+GIAI+CUU+THUC+PHAM+DA+NANG" 
                                     alt="Mã QR Chuyển khoản MB Bank" 
                                     style="width: 135px; height: 135px; object-fit: contain; border-radius: 10px; background: white; border: 1px solid #d8b4fe; padding: 4px; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);">
                                <small style="display: block; text-align: center; color: #7e22ce; font-weight: 700; margin-top: 4px; font-size: 11px;">Quét bằng app ngân hàng</small>
                            </div>
                            <div style="font-size: 13.5px; line-height: 1.8; color: #4c1d95; flex: 1; min-width: 250px;">
                                <div>🏦 Ngân hàng: <strong>MB Bank (Ngân Hàng Quân Đội)</strong></div>
                                <div>💳 Số tài khoản (STK): <strong style="font-family: monospace; font-size: 16px; color: #1e1b4b; background: white; padding: 2px 8px; border-radius: 6px; border: 1px solid #c084fc;">0345678999</strong></div>
                                <div>👤 Tên tài khoản: <strong>QUY GIAI CUU THUC PHAM DA NANG</strong></div>
                                <div>💰 Số tiền cần chuyển: <strong style="color: var(--secondary-color); font-size: 15px;"><?php echo format_money($order['price']); ?></strong></div>
                                <div>📝 Nội dung chuyển khoản: <code style="font-size: 13px; font-weight: bold; background: white; padding: 3px 8px; border-radius: 6px; border: 1px dashed #a855f7;">GIAICUU DON<?php echo $order['id']; ?> MA<?php echo $order['pickup_code']; ?></code></div>
                            </div>
                        </div>
                    </div>
                <?php elseif (($order['payment_method'] ?? 'wallet') === 'cash'): ?>
                    <!-- Khung thông tin Tiền mặt -->
                    <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; padding: 12px 16px; margin: 12px 0; color: #92400e; font-size: 13.5px;">
                        <strong>💵 Thanh toán Tiền Mặt khi nhận hàng:</strong><br>
                        Vui lòng chuẩn bị sẵn đúng số tiền <strong><?php echo format_money($order['price']); ?></strong> để gửi cho nhân viên quán khi bạn đến nhận túi (hoặc gửi cho shipper giao hàng).
                    </div>
                <?php endif; ?>

                📦 Khung giờ lấy / nhận hàng: <strong><?php echo date('H:i', strtotime($order['pickup_start'])); ?> - <?php echo date('H:i d/m/Y', strtotime($order['pickup_end'])); ?></strong><br>
                ⛔ Giờ quán đóng cửa: <strong><?php echo format_datetime($order['close_at']); ?></strong>
            </div>
        </div>

        <!-- CÁC TRẠNG THÁI VÀ THAO TÁC CỤ THỂ -->
        
        <!-- 1. Trạng thái: Chờ nhận hàng -->
        <?php if ($order['status'] === 'cho_nhan'): ?>
            <div style="background: #e8f5e9; border: 1px solid #c8e6c9; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="color: #2e7d32; margin-bottom: 8px;">Quy định Hủy đơn & Nhận hàng:</h4>
                <p style="font-size: 14px; margin-bottom: 10px;">
                    - Bạn được hủy đơn và nhận lại 100% tiền nếu còn cách giờ đóng cửa ít nhất <strong><?php echo $cancel_mins; ?> phút</strong> (hạn chót hủy: <strong><?php echo date('H:i d/m/Y', $cancel_deadline_timestamp); ?></strong>).<br>
                    - Nếu không hủy cũng không đến lấy trước giờ đóng cửa (<?php echo format_datetime($order['close_at']); ?>), đơn sẽ thành <strong>Hủy vắng mặt</strong> và tiền sẽ chuyển cho quán bồi thường.
                </p>

                <?php if ($can_cancel): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <span class="time-countdown">
                            ⏱ Còn <strong><?php echo $rem_cancel_mins; ?> phút</strong> để bạn có thể hủy đơn
                        </span>
                        <a href="<?php echo BASE_URL; ?>student/huy_don.php?id=<?php echo $order['id']; ?>" class="btn btn-danger" onclick="return confirm('Bạn có chắc muốn hủy đơn này? Tiền sẽ được hoàn lại 100% vào ví và túi sẽ được trả lại kho.');">
                            HỦY ĐƠN & HOÀN TIỀN 100%
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning" style="margin-bottom: 0;">
                        ⚠️ <strong>Đã quá thời hạn hủy đơn</strong> (ít hơn <?php echo $cancel_mins; ?> phút trước giờ đóng cửa). Bạn hãy sắp xếp đến quán nhận túi trước giờ đóng cửa để không bị mất tiền nhé!
                    </div>
                <?php endif; ?>
            </div>

            <!-- Nếu quán đóng cửa bất ngờ không giao hàng, sinh viên có thể báo cáo tranh chấp -->
            <div style="text-align: right;">
                <a href="<?php echo BASE_URL; ?>student/bao_cao.php?order_id=<?php echo $order['id']; ?>" style="font-size: 13px; color: #c62828;">
                    Gặp sự cố với quán? Báo cáo khiếu nại tại đây
                </a>
            </div>

        <!-- 2. Trạng thái: Đã nhận hàng (Đang giữ tiền hold_minutes) -->
        <?php elseif ($order['status'] === 'da_nhan'): ?>
            <div style="background: #e1f5fe; border: 1px solid #81d4fa; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="color: #0277bd; margin-bottom: 8px;">Đơn hàng đã nhận - Tiền đang được bảo vệ!</h4>
                <p style="font-size: 14px; margin-bottom: 10px;">
                    Bạn đã nhận túi vào lúc: <strong><?php echo format_datetime($order['picked_up_at']); ?></strong>.<br>
                    Hệ thống đang giữ tiền của bạn đến: <strong><?php echo format_datetime($order['hold_until']); ?></strong> (thời gian giữ: <?php echo $hold_mins; ?> phút) để bạn mang về kiểm tra chất lượng thực phẩm.
                </p>

                <?php
                $rem_hold_mins = get_remaining_minutes($order['hold_until']);
                ?>
                <div class="time-countdown" style="margin-bottom: 14px;">
                    ⏱ Còn <strong><?php echo $rem_hold_mins; ?> phút</strong> giữ tiền để khiếu nại nếu đồ ăn có vấn đề
                </div>

                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <!-- Nút Tôi Hài Lòng -->
                    <a href="<?php echo BASE_URL; ?>student/toi_hai_long.php?id=<?php echo $order['id']; ?>" class="btn btn-primary" onclick="return confirm('Bạn hài lòng với túi thực phẩm này? Tiền sẽ được chuyển ngay vào ví của chủ quán.');">
                        👍 TÔI HÀI LÒNG (NHẢ TIỀN CHO QUÁN NGAY)
                    </a>

                    <!-- Nút Báo cáo gian lận -->
                    <a href="<?php echo BASE_URL; ?>student/bao_cao.php?order_id=<?php echo $order['id']; ?>" class="btn btn-danger">
                        ⚠️ BÁO CÁO GIAN LẬN / THỰC PHẨM HỎNG
                    </a>
                </div>
            </div>

        <!-- 3. Trạng thái: Tranh chấp / Khiếu nại -->
        <?php elseif ($order['status'] === 'tranh_chap'): ?>
            <div style="background: #ffebee; border: 1px solid #ffcdd2; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="color: #c62828; margin-bottom: 8px;">Đơn hàng đang ở trạng thái Tranh chấp</h4>
                <p style="font-size: 14px; margin-bottom: 10px;">
                    Tiền của bạn đang được đóng băng an toàn trong hệ thống, chưa chuyển cho chủ quán.
                </p>
                <?php if ($dispute): ?>
                    <div style="background: white; padding: 12px; border-radius: 6px; font-size: 13px;">
                        <div><strong>Lý do khiếu nại:</strong> <?php echo htmlspecialchars($dispute['reason']); ?></div>
                        <div><strong>Ghi chú của bạn:</strong> <?php echo nl2br(htmlspecialchars($dispute['student_note'] ?? '')); ?></div>
                        <?php if (!empty($dispute['student_image'])): ?>
                            <div style="margin-top: 8px;">
                                <strong>Ảnh bằng chứng:</strong><br>
                                <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($dispute['student_image']); ?>" alt="Ảnh chứng minh" style="max-width: 200px; border-radius: 4px; border: 1px solid #ccc; margin-top: 4px;">
                            </div>
                        <?php endif; ?>

                        <hr style="margin: 10px 0;">

                        <div>
                            <strong>Phản hồi từ chủ quán:</strong> 
                            <?php if (!empty($dispute['shop_note'])): ?>
                                <div><?php echo nl2br(htmlspecialchars($dispute['shop_note'])); ?></div>
                                <?php if (!empty($dispute['shop_image'])): ?>
                                    <div style="margin-top: 6px;">
                                        <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($dispute['shop_image']); ?>" alt="Ảnh quán phản đối" style="max-width: 200px; border-radius: 4px; border: 1px solid #ccc;">
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-style: italic;">Quán chưa gửi phản đối (thời hạn 48 giờ).</span>
                            <?php endif; ?>
                        </div>

                        <div style="margin-top: 8px; font-weight: bold; color: #6a1b9a;">
                            Trạng thái xử lý: 
                            <?php if ($dispute['status'] === 'cho_shop_phan_hoi'): ?>
                                Đang chờ quán phản hồi hoặc hết hạn 48 giờ
                            <?php else: ?>
                                Đang chờ Quản trị viên (Admin) xem xét và phân xử
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        <!-- 4. Trạng thái: Hoàn thành -->
        <?php elseif ($order['status'] === 'hoan_thanh'): ?>
            <div class="alert alert-success" style="border-radius: 14px;">
                🎉 <strong>Đơn hàng đã hoàn thành tốt đẹp!</strong> Tiền đã được giải ngân cho chủ quán. Cảm ơn bạn đã chung tay chống lãng phí thực phẩm tại Đà Nẵng!
            </div>

            <!-- Khối đánh giá 5 sao cho quán -->
            <?php if ($existing_review): ?>
                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; padding: 22px; border-radius: 18px; margin-top: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span class="badge badge-success" style="font-size: 12px;">✓ Đã đánh giá trải nghiệm</span>
                        <span style="font-size: 13px; color: var(--text-muted);"><?php echo format_datetime($existing_review['created_at']); ?></span>
                    </div>
                    <div style="color: #f59e0b; font-size: 20px; margin-bottom: 8px;">
                        <?php echo str_repeat('⭐', $existing_review['rating']); ?>
                        <span style="font-size: 15px; font-weight: 800; color: var(--text-dark); margin-left: 6px;">(<?php echo $existing_review['rating']; ?>/5 sao)</span>
                    </div>
                    <p style="font-size: 14px; color: #475569; font-style: italic; line-height: 1.6;">
                        "<?php echo nl2br(htmlspecialchars($existing_review['comment'] ?? '')); ?>"
                    </p>
                </div>
            <?php else: ?>
                <div class="card" style="margin-top: 20px; background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%); border: 1.5px solid #fde68a; border-radius: 20px; padding: 24px;">
                    <h3 style="color: #b45309; font-size: 18px; font-weight: 800; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                        <span>⭐</span> Đánh Giá Chất Lượng Túi Thực Phẩm
                    </h3>
                    <p style="font-size: 13.5px; color: #78350f; margin-bottom: 18px;">
                        Trải nghiệm mở túi bất ngờ hôm nay của bạn thế nào? Hãy chấm điểm để giúp các bạn sinh viên khác tham khảo và góp ý cho quán nhé!
                    </p>

                    <form action="<?php echo BASE_URL; ?>student/don_chi_tiet.php?id=<?php echo $order['id']; ?>" method="POST">
                        <div class="form-group">
                            <label class="form-label">Chọn số sao đánh giá:</label>
                            <select name="rating" class="form-control" style="font-size: 15px; font-weight: bold; color: #b45309; max-width: 240px;">
                                <option value="5" selected>⭐⭐⭐⭐⭐ (5/5) Tuyệt vời, đồ tươi ngon</option>
                                <option value="4">⭐⭐⭐⭐ (4/5) Rất tốt, hài lòng</option>
                                <option value="3">⭐⭐⭐ (3/5) Tạm ổn, ăn được</option>
                                <option value="2">⭐⭐ (2/5) Chưa ưng ý lắm</option>
                                <option value="1">⭐ (1/5) Thất vọng</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nhận xét chi tiết (mùi vị, số lượng, độ tươi sạch):</label>
                            <textarea name="comment" class="form-control" rows="3" placeholder="Ví dụ: Bánh Croissant bơ thơm lừng, còn mềm và xốp, nhận túi lúc 20:30 vẫn còn ấm..."></textarea>
                        </div>

                        <button type="submit" name="action_review" value="1" class="btn btn-secondary" style="font-weight: 800;">
                            GỬI ĐÁNH GIÁ 5 SAO &rarr;
                        </button>
                    </form>
                </div>
            <?php endif; ?>


        <!-- 5. Trạng thái: Admin hoàn tiền cho SV -->
        <?php elseif ($order['status'] === 'hoan_tien_sv'): ?>
            <div class="alert alert-success">
                Admin đã phân xử: <strong>CHẤP THUẬN KHIẾU NẠI</strong>.<br>
                Tiền <?php echo format_money($order['price']); ?> đã được hoàn trả 100% vào ví của bạn. Quán đã bị ghi nhận 1 lần vi phạm chất lượng.<br>
                Ghi chú từ Admin: <em><?php echo htmlspecialchars($dispute['admin_note'] ?? 'Không có'); ?></em>
            </div>

        <!-- 6. Trạng thái: Admin bác khiếu nại, chuyển tiền quán -->
        <?php elseif ($order['status'] === 'chuyen_tien_quan'): ?>
            <div class="alert alert-danger">
                Admin đã phân xử: <strong>BÁC BỎ KHIẾU NẠI</strong>.<br>
                Tiền đã được chuyển cho quán. Tài khoản sinh viên bị tính 1 lần cảnh cáo (Nếu bị 3 lần cảnh cáo tài khoản sẽ bị khóa).<br>
                Lý do từ Admin: <em><?php echo htmlspecialchars($dispute['admin_note'] ?? 'Không có'); ?></em>
            </div>

        <!-- 7. Hủy bởi khách -->
        <?php elseif ($order['status'] === 'huy_boi_khach'): ?>
            <div class="alert alert-info">
                Đơn hàng đã được hủy bởi bạn trước thời hạn đóng cửa. Tiền đã được hoàn 100% vào ví.
            </div>

        <!-- 8. Hủy vắng mặt -->
        <?php elseif ($order['status'] === 'huy_vang_mat'): ?>
            <div class="alert alert-warning">
                Đơn hàng bị <strong>Hủy do vắng mặt</strong> vì bạn không đến nhận túi trước giờ đóng cửa quán. Tiền đã được chuyển cho quán để bồi thường chi phí chuẩn bị thực phẩm.
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
