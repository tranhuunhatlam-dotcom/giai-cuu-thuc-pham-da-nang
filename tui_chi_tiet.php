<?php
// FILE: tui_chi_tiet.php
// Mục đích: Xem chi tiết túi bất ngờ, hiển thị thông tin trực quan cao cấp và thực hiện đặt hàng nguyên tử

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$bag_id = (int)($_GET['id'] ?? 0);
if ($bag_id <= 0) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

// Lấy thông tin túi và cửa hàng
$sql = "SELECT b.*, s.name AS shop_name, s.address AS shop_address, s.district AS shop_district, s.type AS shop_type, s.image AS shop_image, s.owner_id
        FROM bags b
        JOIN shops s ON b.shop_id = s.id
        WHERE b.id = ? AND s.status = 'da_duyet' LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $bag_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$bag = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$bag) {
    die("Lỗi: Túi bất ngờ không tồn tại hoặc quán chưa được duyệt.");
}

$user = get_logged_in_user($conn);
$error = '';
$now_str = date('Y-m-d H:i:s');
$now_time = time();

$open_time = strtotime($bag['open_at']);
$close_time = strtotime($bag['close_at']);
$is_open = ($now_time >= $open_time);
$is_closed = ($now_time > $close_time);
$rem_open_mins = get_remaining_minutes($bag['open_at']);
$discount_pct = round((1 - ($bag['web_price'] / $bag['original_price'])) * 100);

// Xử lý khi sinh viên bấm nút Đặt Mua
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_order'])) {
    if (!$user) {
        header("Location: " . BASE_URL . "dang_nhap.php?error=need_login");
        exit;
    }

    if ($user['role'] !== 'student') {
        $error = 'Chỉ tài khoản Sinh viên mới có quyền đặt mua túi bất ngờ.';
    } elseif ($user['is_locked'] == 1) {
        $error = 'Tài khoản của bạn đang bị khóa, không thể đặt hàng.';
    } elseif (!$is_open) {
        $error = 'Chưa tới giờ mở bán túi! Vui lòng chờ thêm ' . $rem_open_mins . ' phút.';
    } elseif ($is_closed) {
        $error = 'Quán đã đóng cửa, không thể đặt đơn cho túi này.';
    } else {
        // Kiểm tra giới hạn số túi mỗi ngày (max_bags_per_user)
        $max_bags = (int)get_setting($conn, 'max_bags_per_user', 2);
        $stmt_limit = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM orders WHERE user_id = ? AND DATE(created_at) = CURDATE() AND status != 'huy_boi_khach'");
        mysqli_stmt_bind_param($stmt_limit, "i", $user['id']);
        mysqli_stmt_execute($stmt_limit);
        $res_limit = mysqli_stmt_get_result($stmt_limit);
        $row_limit = mysqli_fetch_assoc($res_limit);
        mysqli_stmt_close($stmt_limit);

        if ($row_limit['total'] >= $max_bags) {
            $error = "Bạn đã đạt giới hạn tối đa {$max_bags} túi/ngày để nhường cơ hội cứu trợ cho các bạn sinh viên khác!";
        } else {
            // Kiểm tra mã voucher (nếu có nhập)
            $voucher_code = strtoupper(trim($_POST['voucher_code'] ?? ''));
            $discount_amount = 0.00;
            $voucher_id = null;
            $bag_price = $bag['web_price'];

            if (!empty($voucher_code)) {
                $v_res = check_voucher($conn, $voucher_code, $bag['web_price']);
                if ($v_res['valid']) {
                    $discount_amount = $v_res['discount'];
                    $voucher_id = $v_res['voucher']['id'];
                    $bag_price = max(0, $bag['web_price'] - $discount_amount);
                } else {
                    $error = $v_res['error'];
                }
            }

            // Xử lý phương thức nhận hàng (Tự đến lấy hoặc Đặt Ship giao tận nơi)
            $delivery_type = trim($_POST['delivery_type'] ?? 'pickup');
            $shipping_address = trim($_POST['shipping_address'] ?? '');
            $shipping_phone = trim($_POST['shipping_phone'] ?? '');
            $shipping_fee = 0.00;

            if ($delivery_type === 'delivery') {
                $shipping_fee = 12000.00; // Phí ship trợ giá sinh viên nội thành Đà Nẵng
                if (empty($shipping_address) || empty($shipping_phone)) {
                    $error = 'Bạn đã chọn Đặt Ship tận nơi. Vui lòng nhập đầy đủ Địa chỉ giao hàng và Số điện thoại nhận!';
                }
            } else {
                $delivery_type = 'pickup';
                $shipping_address = null;
                $shipping_phone = null;
            }

            // Xử lý phương thức thanh toán
            $payment_method = trim($_POST['payment_method'] ?? 'wallet');
            if (!in_array($payment_method, ['wallet', 'cash', 'bank_qr'])) {
                $payment_method = 'wallet';
            }

            $payment_status = 'da_thanh_toan';
            if ($payment_method === 'cash') {
                $payment_status = 'chua_thanh_toan';
            } elseif ($payment_method === 'bank_qr') {
                $payment_status = 'cho_xac_nhan';
            }

            $final_price = $bag_price + $shipping_fee;

            if (empty($error)) {
                if ($payment_method === 'wallet' && $user['balance'] < $final_price) {
                    $error = 'Số dư trong ví không đủ để thanh toán bằng Ví Ký Quỹ. Cần ' . format_money($final_price) . ', số dư hiện tại là ' . format_money($user['balance']) . '. Vui lòng nạp thêm tiền demo hoặc chọn hình thức Tiền mặt / Chuyển khoản QR.';
                } else {
                    // TIẾN HÀNH ĐẶT HÀNG TRONG TRANSACTION TUÂN THỦ NGHIÊM NGẶT
                    mysqli_begin_transaction($conn);

                    // 1. Trừ kho trực tiếp bằng UPDATE nguyên tử và kiểm tra affected_rows
                    $stmt_stock = mysqli_prepare($conn, "UPDATE bags SET stock = stock - 1 WHERE id = ? AND stock >= 1");
                    mysqli_stmt_bind_param($stmt_stock, "i", $bag['id']);
                    mysqli_stmt_execute($stmt_stock);
                    $stock_affected = mysqli_stmt_affected_rows($stmt_stock);
                    mysqli_stmt_close($stmt_stock);

                    if ($stock_affected <= 0) {
                        mysqli_rollback($conn);
                        $error = 'Rất tiếc, túi này vừa hết hàng trong kho!';
                    } else {
                        $payment_ok = true;
                        if ($payment_method === 'wallet') {
                            // 2. Trừ tiền ảo của sinh viên trực tiếp bằng UPDATE nguyên tử
                            $stmt_pay = mysqli_prepare($conn, "UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
                            mysqli_stmt_bind_param($stmt_pay, "did", $final_price, $user['id'], $final_price);
                            mysqli_stmt_execute($stmt_pay);
                            $pay_affected = mysqli_stmt_affected_rows($stmt_pay);
                            mysqli_stmt_close($stmt_pay);
                            if ($pay_affected <= 0) {
                                $payment_ok = false;
                            }
                        }

                        if (!$payment_ok) {
                            mysqli_rollback($conn);
                            $error = 'Giao dịch trừ tiền ví thất bại hoặc số dư không đủ. Vui lòng thử lại hoặc chọn Tiền mặt / Chuyển khoản QR!';
                        } else {
                            // 3. Nếu dùng voucher, tăng used_count
                            if ($voucher_id) {
                                $stmt_v_up = mysqli_prepare($conn, "UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
                                mysqli_stmt_bind_param($stmt_v_up, "i", $voucher_id);
                                mysqli_stmt_execute($stmt_v_up);
                                mysqli_stmt_close($stmt_v_up);
                            }

                            // 4. Tạo mã nhận hàng 6 ký tự ngẫu nhiên duy nhất
                            $pickup_code = generate_pickup_code($conn);

                            // 5. Tạo bản ghi đơn hàng ở trạng thái 'cho_nhan'
                            $stmt_ins = mysqli_prepare($conn, "INSERT INTO orders (user_id, bag_id, shop_id, price, status, pickup_code, delivery_type, shipping_address, shipping_phone, shipping_fee, close_at, voucher_id, discount_amount, payment_method, payment_status, created_at) VALUES (?, ?, ?, ?, 'cho_nhan', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                            mysqli_stmt_bind_param($stmt_ins, "iiidssssdsidss", $user['id'], $bag['id'], $bag['shop_id'], $final_price, $pickup_code, $delivery_type, $shipping_address, $shipping_phone, $shipping_fee, $bag['close_at'], $voucher_id, $discount_amount, $payment_method, $payment_status);
                            mysqli_stmt_execute($stmt_ins);
                            $new_order_id = mysqli_insert_id($conn);
                            mysqli_stmt_close($stmt_ins);

                            // 6. Ghi nhật ký ví tiền nếu thanh toán bằng ví (wallet_logs)
                            if ($payment_method === 'wallet') {
                                $log_amount = -1 * $final_price;
                                $log_reason = "Thanh toán đơn túi #" . $new_order_id . " (Mã nhận: " . $pickup_code . ")";
                                if ($delivery_type === 'delivery') {
                                    $log_reason .= " [Giao tận nơi: +12.000 đ]";
                                }
                                if ($discount_amount > 0) {
                                    $log_reason .= " [Áp voucher {$voucher_code}: -" . format_money($discount_amount) . "]";
                                }
                                $stmt_log = mysqli_prepare($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                                mysqli_stmt_bind_param($stmt_log, "idsi", $user['id'], $log_amount, $log_reason, $new_order_id);
                                mysqli_stmt_execute($stmt_log);
                                mysqli_stmt_close($stmt_log);
                            }

                            // Cam kết Transaction
                            mysqli_commit($conn);

                            // Gửi thông báo
                            $pay_name = ($payment_method === 'cash') ? 'Tiền mặt khi nhận' : (($payment_method === 'bank_qr') ? 'Chuyển khoản QR' : 'Ví Ký Quỹ');
                            add_notification($conn, $user['id'], "Bạn đã đặt thành công đơn #" . $new_order_id . " ('" . $bag['name'] . "') qua " . $pay_name . ". Mã nhận hàng: " . $pickup_code . ".");
                            add_notification($conn, $bag['owner_id'], "Quán có đơn đặt mới #" . $new_order_id . " ('" . $bag['name'] . "') qua " . $pay_name . ". Hãy chuẩn bị hàng và chờ khách nhận!");

                            // Chuyển hướng tới trang chi tiết đơn hàng
                            header("Location: " . BASE_URL . "student/don_chi_tiet.php?id=" . $new_order_id . "&success=1");
                            exit;
                        }
                    }
                }
            }
        }
    }
}

$page_title = $bag['name'];
require_once __DIR__ . '/header.php';
?>

<div style="max-width: 960px; margin: 24px auto;">
    <div style="margin-bottom: 20px;">
        <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-outline btn-sm" style="font-weight: 700;">
            &larr; Quay lại danh sách săn túi
        </a>
    </div>

    <!-- Thông điệp cam kết mô hình Túi Bất Ngờ -->
    <div class="notice-callout">
        <div class="notice-callout-icon">🎯</div>
        <div class="notice-callout-text">
            <strong>Quy tắc Túi Bất Ngờ: Bạn không chọn được từng món cụ thể, đổi lại giá cực tốt!</strong><br>
            <span>
                Khách mua trực tiếp tại quán (offline) được tự do chọn món nhưng bắt buộc thanh toán 100% giá gốc; mức giá ưu đãi giảm sâu <?php echo $discount_pct; ?>% chỉ dành riêng cho bạn khi đặt trước trên website, vì bạn đồng ý nhận túi ngẫu nhiên để đồng hành cùng quán giải cứu đồ ăn sạch cuối ngày.
            </span>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card" style="padding: 0; overflow: hidden; border-radius: 24px;">
        <div class="form-row" style="gap: 0;">
            <!-- Cột 1: Ảnh sản phẩm sắc nét -->
            <div class="form-col" style="min-width: 360px; background: #f8fafc; display: flex; align-items: center; justify-content: center; position: relative;">
                <?php
                $img_url = !empty($bag['image']) ? BASE_URL . 'uploads/' . htmlspecialchars($bag['image']) : BASE_URL . 'uploads/bag_banh_ngot.jpg';
                ?>
                <img src="<?php echo $img_url; ?>" alt="<?php echo htmlspecialchars($bag['name']); ?>" style="width: 100%; height: 100%; min-height: 420px; object-fit: cover;">
                <span class="bag-discount-tag" style="top: 20px; right: 20px; font-size: 15px; padding: 7px 16px;">
                    TIẾT KIỆM <?php echo $discount_pct; ?>%
                </span>
                <span class="bag-type-tag" style="top: 20px; left: 20px; font-size: 13px;">
                    <?php echo htmlspecialchars($bag['shop_type']); ?>
                </span>
            </div>

            <!-- Cột 2: Thông tin chi tiết & Đặt mua -->
            <div class="form-col" style="padding: 36px 32px; min-width: 340px; display: flex; flex-direction: column;">
                <div style="font-size: 13.5px; color: var(--text-muted); font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                    <span>🏪 Quán: <strong style="color: var(--primary-dark);"><?php echo htmlspecialchars($bag['shop_name']); ?></strong></span>
                    <span class="badge badge-success" style="font-size: 11px; padding: 2px 7px;">✓ Đã kiểm duyệt</span>
                </div>
                <div style="font-size: 13px; color: #64748b; margin-bottom: 16px;">
                    📍 Địa chỉ: <strong><?php echo htmlspecialchars($bag['shop_address']); ?>, Q. <?php echo htmlspecialchars($bag['shop_district']); ?></strong>
                </div>

                <h1 style="color: var(--text-dark); font-size: 26px; font-weight: 900; line-height: 1.3; margin-bottom: 18px; letter-spacing: -0.6px;">
                    <?php echo htmlspecialchars($bag['name']); ?>
                </h1>

                <!-- Khối giá -->
                <div class="bag-prices" style="margin-bottom: 20px; padding-bottom: 16px;">
                    <div>
                        <small style="display: block; font-size: 12px; color: #94a3b8; text-transform: uppercase; font-weight: 800;">Giá gốc tại quán:</small>
                        <span class="original-price" style="font-size: 16px; font-weight: 700;"><?php echo format_money($bag['original_price']); ?></span>
                    </div>
                    <div>
                        <small style="display: block; font-size: 12px; color: var(--secondary-color); text-transform: uppercase; font-weight: 800;">Giá cứu trợ trên web:</small>
                        <span class="web-price" style="font-size: 32px; font-weight: 900;"><?php echo format_money($bag['web_price']); ?></span>
                    </div>
                </div>

                <!-- Mô tả món -->
                <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 16px 18px; border-radius: 14px; margin-bottom: 22px; font-size: 14px;">
                    <strong style="color: var(--text-dark);">Chi tiết túi bất ngờ:</strong>
                    <p style="margin-top: 6px; color: #475569; line-height: 1.65;">
                        <?php echo nl2br(htmlspecialchars($bag['description'] ?? 'Túi ngẫu nhiên các món thơm ngon nướng/nấu trong ngày.')); ?>
                    </p>
                </div>

                <!-- Khung giờ nhận & Hạn chót -->
                <div style="font-size: 13.5px; line-height: 1.8; margin-bottom: 26px; color: #334155; background: #fffbeb; border: 1px solid #fde68a; padding: 14px 18px; border-radius: 14px;">
                    <div>🕒 <strong>Giờ mở bán online:</strong> <?php echo format_datetime($bag['open_at']); ?></div>
                    <div>📦 <strong>Khung giờ đến nhận túi:</strong> <span style="color: #047857; font-weight: 800;"><?php echo date('H:i', strtotime($bag['pickup_start'])); ?> - <?php echo date('H:i d/m/Y', strtotime($bag['pickup_end'])); ?></span></div>
                    <div>⛔ <strong>Giờ quán đóng cửa:</strong> <span style="color: #dc2626; font-weight: 800;"><?php echo format_datetime($bag['close_at']); ?></span></div>
                    <div style="margin-top: 4px;">📦 <strong>Số lượng còn lại:</strong> <strong style="color: <?php echo $bag['stock'] > 0 ? '#047857' : '#dc2626'; ?>; font-size: 15px;"><?php echo $bag['stock']; ?> suất</strong></div>
                </div>

                <!-- Nút thao tác đặt mua -->
                <div style="margin-top: auto;">
                    <?php if ($is_closed): ?>
                        <div class="alert alert-danger" style="margin-bottom: 0;">Quán đã đóng cửa hôm nay. Hãy quay lại vào ngày mai nhé!</div>
                    <?php elseif (!$is_open): ?>
                        <div class="alert alert-warning" style="margin-bottom: 0;">
                            ⏳ Chưa tới giờ mở bán. Túi sẽ mở bán sau <strong><?php echo $rem_open_mins; ?> phút</strong> nữa (lúc <?php echo format_datetime($bag['open_at']); ?>). Vui lòng tải lại trang (F5) khi đến giờ.
                        </div>
                    <?php elseif ($bag['stock'] <= 0): ?>
                        <div class="alert alert-danger" style="margin-bottom: 0;">Rất tiếc, toàn bộ túi bất ngờ của quán đã được giải cứu hết!</div>
                    <?php else: ?>
                        <!-- Đang mở bán và còn hàng -->
                        <?php if (!$user): ?>
                            <a href="<?php echo BASE_URL; ?>dang_nhap.php?error=need_login" class="btn btn-primary btn-block btn-lg">
                                ĐĂNG NHẬP ĐỂ ĐẶT TÚI NÀY &rarr;
                            </a>
                        <?php elseif ($user['role'] !== 'student'): ?>
                            <div class="alert alert-info" style="margin-bottom: 0;">
                                Bạn đang đăng nhập bằng tài khoản <strong><?php echo htmlspecialchars($user['role']); ?></strong>. Chỉ tài khoản Sinh viên mới có thể đặt mua.
                            </div>
                        <?php else: ?>
                            <!-- Tài khoản sinh viên -->
                            <div style="margin-bottom: 14px; font-size: 14px; display: flex; justify-content: space-between; align-items: center;">
                                <span>Số dư ví của bạn: <strong><?php echo format_money($user['balance']); ?></strong></span>
                                <?php if ($user['balance'] < $bag['web_price']): ?>
                                    <a href="<?php echo BASE_URL; ?>student/vi_tien.php" style="color: var(--secondary-color); font-weight: 800; text-decoration: underline;">Nạp thêm tiền demo &rarr;</a>
                                <?php endif; ?>
                            </div>

                            <form action="<?php echo BASE_URL; ?>tui_chi_tiet.php?id=<?php echo $bag['id']; ?>" method="POST">
                                <!-- Lựa chọn Phương thức nhận hàng: Tự lấy hoặc Đặt Ship -->
                                <div style="background: #eff6ff; border: 1.5px solid #bfdbfe; padding: 14px 16px; border-radius: 14px; margin-bottom: 14px;">
                                    <label class="form-label" style="font-size: 13.5px; font-weight: 800; color: #1e40af; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                                        <span>🛵</span> Phương thức nhận hàng:
                                    </label>
                                    <div style="display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 10px;">
                                        <label style="display: flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 700; color: #1e3a8a; cursor: pointer; background: #ffffff; padding: 6px 12px; border-radius: 8px; border: 1px solid #93c5fd;">
                                            <input type="radio" name="delivery_type" value="pickup" checked>
                                            🏪 Tự đến quán lấy (0 đ)
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 700; color: #047857; cursor: pointer; background: #ffffff; padding: 6px 12px; border-radius: 8px; border: 1px solid #86efac;">
                                            <input type="radio" name="delivery_type" value="delivery">
                                            🛵 Đặt Ship tận nơi (+12.000 đ)
                                        </label>
                                    </div>

                                    <div style="background: #ffffff; border: 1px dashed #93c5fd; padding: 10px 12px; border-radius: 10px; font-size: 12.5px; color: #1e40af;">
                                        <div style="margin-bottom: 8px;">
                                            <label style="font-weight: 700; display: block; margin-bottom: 4px;">📍 Địa chỉ nhận hàng (Nếu chọn Đặt Ship):</label>
                                            <input type="text" name="shipping_address" class="form-control" style="font-size: 13px; padding: 6px 10px;" placeholder="Ví dụ: Phòng 302 KTX VKU, Phường Hòa Quý, Ngũ Hành Sơn...">
                                        </div>
                                        <div>
                                            <label style="font-weight: 700; display: block; margin-bottom: 4px;">📞 Số điện thoại nhận hàng (Nếu chọn Đặt Ship):</label>
                                            <input type="text" name="shipping_phone" class="form-control" style="font-size: 13px; padding: 6px 10px;" placeholder="Ví dụ: 0905123456">
                                        </div>
                                    </div>
                                </div>

                                <!-- Lựa chọn Phương thức thanh toán -->
                                <div style="background: #fffbeb; border: 1.5px solid #fde68a; padding: 14px 16px; border-radius: 14px; margin-bottom: 14px;">
                                    <label class="form-label" style="font-size: 13.5px; font-weight: 800; color: #92400e; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                                        <span>💳</span> Phương thức thanh toán:
                                    </label>
                                    
                                    <div style="display: flex; flex-direction: column; gap: 10px;">
                                        <!-- Lựa chọn 1: Ví Ký Quỹ Escrow -->
                                        <label style="display: flex; align-items: flex-start; gap: 10px; background: #ffffff; padding: 10px 14px; border-radius: 10px; border: 1.5px solid #fde68a; cursor: pointer;">
                                            <input type="radio" name="payment_method" value="wallet" checked style="margin-top: 3px;">
                                            <div>
                                                <div style="font-weight: 800; color: #92400e; font-size: 13.5px;">
                                                    🛡️ Ví Ký Quỹ Escrow (Khuyên dùng - Bảo vệ tiền 100%)
                                                </div>
                                                <div style="font-size: 12px; color: #78350f;">
                                                    Số dư hiện có: <strong><?php echo format_money($user['balance']); ?></strong>. Tiền được tạm giữ an toàn, chỉ giải ngân cho quán sau khi bạn nhận và bấm hài lòng.
                                                </div>
                                            </div>
                                        </label>

                                        <!-- Lựa chọn 2: Tiền mặt khi nhận -->
                                        <label style="display: flex; align-items: flex-start; gap: 10px; background: #ffffff; padding: 10px 14px; border-radius: 10px; border: 1.5px solid #cbd5e1; cursor: pointer;">
                                            <input type="radio" name="payment_method" value="cash" style="margin-top: 3px;">
                                            <div>
                                                <div style="font-weight: 800; color: #1e293b; font-size: 13.5px;">
                                                    💵 Tiền mặt khi nhận hàng (Tại quầy quán hoặc Shipper)
                                                </div>
                                                <div style="font-size: 12px; color: #64748b;">
                                                    Thanh toán trực tiếp bằng tiền mặt khi bạn đến nhận túi hoặc khi shipper giao tới tay bạn.
                                                </div>
                                            </div>
                                        </label>

                                        <!-- Lựa chọn 3: Chuyển khoản VietQR -->
                                        <label style="display: flex; align-items: flex-start; gap: 10px; background: #ffffff; padding: 10px 14px; border-radius: 10px; border: 1.5px solid #c084fc; cursor: pointer;">
                                            <input type="radio" name="payment_method" value="bank_qr" style="margin-top: 3px;">
                                            <div style="width: 100%;">
                                                <div style="font-weight: 800; color: #6b21a8; font-size: 13.5px; display: flex; align-items: center; justify-content: space-between;">
                                                    <span>📲 Chuyển khoản Ngân Hàng qua Mã QR (VietQR)</span>
                                                    <span class="badge badge-purple" style="font-size: 10.5px;">MB Bank</span>
                                                </div>
                                                <div style="font-size: 12px; color: #7e22ce; margin-top: 2px;">
                                                    Quét mã QR qua app ngân hàng bất kỳ (MB, Vietcombank, BIDV, Momo...) - Tự động điền số tiền & nội dung!
                                                </div>

                                                <!-- Preview VietQR trực quan -->
                                                <div style="margin-top: 10px; background: #faf5ff; border: 1px dashed #d8b4fe; border-radius: 10px; padding: 12px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                                                    <img src="https://img.vietqr.io/image/MB-0345678999-compact2.png?amount=<?php echo (int)$bag['web_price']; ?>&addInfo=GIAICUU+TUI<?php echo $bag['id']; ?>&accountName=QUY+GIAI+CUU+THUC+PHAM+DA+NANG" alt="VietQR MB Bank" style="width: 110px; height: 110px; object-fit: contain; border-radius: 8px; background: white; border: 1px solid #e9d5ff; padding: 4px;">
                                                    <div style="font-size: 12px; line-height: 1.6; color: #581c87;">
                                                        <div>🏦 Ngân hàng: <strong>MB Bank (Ngân Hàng Quân Đội)</strong></div>
                                                        <div>💳 Số tài khoản: <strong style="font-family: monospace; font-size: 14px; color: #1e1b4b; background: white; padding: 1px 6px; border-radius: 4px;">0345678999</strong></div>
                                                        <div>👤 Chủ tài khoản: <strong>QUY GIAI CUU THUC PHAM DA NANG</strong></div>
                                                        <div>📝 Nội dung: <code style="font-weight: bold; background: white; padding: 2px 6px; border-radius: 4px;">GIAICUU TUI<?php echo $bag['id']; ?></code></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div style="background: #f0fdf4; border: 1px dashed #86efac; padding: 12px 16px; border-radius: 14px; margin-bottom: 14px;">
                                    <label class="form-label" style="font-size: 13px; margin-bottom: 6px; color: #047857;">
                                        🎟️ Nhập mã giảm giá / Voucher trợ giá (nếu có):
                                    </label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" name="voucher_code" class="form-control" style="font-weight: 800; text-transform: uppercase; font-size: 14px; padding: 8px 12px;" placeholder="Ví dụ: VKU2026, DANANGXANH">
                                    </div>
                                    <div style="font-size: 11.5px; color: #166534; margin-top: 6px;">
                                        Mã gợi ý: <code style="background: white; padding: 2px 6px; border-radius: 4px; font-weight: bold;">VKU2026</code> (-10k), <code style="background: white; padding: 2px 6px; border-radius: 4px; font-weight: bold;">DANANGXANH</code> (-15k)
                                    </div>
                                </div>

                                <button type="submit" name="action_order" value="1" class="btn btn-primary btn-block btn-lg" style="font-size: 16px; font-weight: 800; padding: 14px;">
                                    ⚡ XÁC NHẬN ĐẶT TÚI NÀY (<?php echo format_money($bag['web_price']); ?>) &rarr;
                                </button>
                            </form>
                            <small class="form-text" style="text-align: center; display: block; margin-top: 10px;">
                                🛡️ Tiền sẽ được tạm giữ an toàn trong hệ thống cho đến khi bạn kiểm tra và nhận túi.
                            </small>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
