<?php
// FILE: functions.php
// Mục đích: Chứa toàn bộ các hàm dùng chung trong hệ thống (PHP thuần, hướng thủ tục)

// 1. Hàm lấy giá trị cấu hình từ bảng settings
function get_setting($conn, $name, $default = '') {
    $sql = "SELECT value FROM settings WHERE name = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);
            return $row['value'];
        }
        mysqli_stmt_close($stmt);
    }
    return $default;
}

// 2. Hàm lấy thông tin tài khoản đang đăng nhập
function get_logged_in_user($conn) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Nếu tài khoản bị khóa trong lúc đang dùng, bắt đăng xuất ngay
        if ($user && $user['is_locked'] == 1) {
            unset($_SESSION['user_id']);
            unset($_SESSION['role']);
            header("Location: " . BASE_URL . "dang_nhap.php?error=locked");
            exit;
        }
        return $user;
    }
    return null;
}

// 3. Hàm bắt buộc phải đăng nhập
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "dang_nhap.php?error=need_login");
        exit;
    }
}

// 4. Hàm kiểm tra quyền / vai trò (student, shop_owner, admin)
function require_role($allowed_roles) {
    require_login();
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    $current_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    if (!in_array($current_role, $allowed_roles)) {
        // Nếu không đúng vai trò, điều hướng về trang chủ
        header("Location: " . BASE_URL . "index.php?error=unauthorized");
        exit;
    }
}

// 5. Hàm lấy thông tin quán theo ID của chủ quán (shop_owner)
function get_shop_by_owner_id($conn, $owner_id) {
    $sql = "SELECT * FROM shops WHERE owner_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $owner_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $shop = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $shop;
    }
    return null;
}

// 6. Hàm gửi thông báo cho người dùng
function add_notification($conn, $user_id, $message) {
    $sql = "INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "is", $user_id, $message);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
    return false;
}

// 7. Hàm đếm số thông báo chưa đọc
function get_unread_notifications_count($conn, $user_id) {
    $sql = "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return (int)($row['total'] ?? 0);
    }
    return 0;
}

// 8. Hàm tạo mã nhận hàng ngẫu nhiên 6 ký tự không trùng lặp
function generate_pickup_code($conn) {
    // Tập ký tự loại bỏ các chữ dễ nhầm lẫn như 0, O, 1, I
    $chars = "23456789ABCDEFGHJKLMNPQRSTUVWXYZ";
    $max_len = strlen($chars) - 1;
    
    // Thử tạo cho tới khi tìm được mã chưa tồn tại trong bảng orders
    for ($attempt = 0; $attempt < 100; $attempt++) {
        $code = "";
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[rand(0, $max_len)];
        }
        
        $sql = "SELECT id FROM orders WHERE pickup_code = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $code);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $exists = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if (!$exists) {
                return $code; // Mã duy nhất hợp lệ
            }
        }
    }
    // Trường hợp phòng ngừa rất hiếm: thêm tiền tố thời gian
    return strtoupper(substr(uniqid(), -6));
}

// 9. Hàm định dạng tiền tệ Việt Nam
function format_money($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' đ';
}

// 10. Hàm định dạng ngày giờ hiển thị
function format_datetime($datetime) {
    if (!$datetime || $datetime == '0000-00-00 00:00:00') {
        return '---';
    }
    $timestamp = strtotime($datetime);
    return date('H:i d/m/Y', $timestamp);
}

// 11. Hàm tính số phút còn lại từ thời điểm hiện tại đến một mốc thời gian
function get_remaining_minutes($target_datetime) {
    $target_time = strtotime($target_datetime);
    $current_time = time();
    $diff = $target_time - $current_time;
    if ($diff <= 0) {
        return 0;
    }
    return (int)ceil($diff / 60);
}

// 12. Hàm hiển thị nhãn trạng thái đơn hàng có màu sắc CSS
function get_order_status_badge($status) {
    switch ($status) {
        case 'cho_nhan':
            return '<span class="badge badge-warning">Chờ nhận hàng</span>';
        case 'da_nhan':
            return '<span class="badge badge-info">Đã nhận (Đang giữ tiền)</span>';
        case 'hoan_thanh':
            return '<span class="badge badge-success">Hoàn thành</span>';
        case 'tranh_chap':
            return '<span class="badge badge-danger">Tranh chấp / Khiếu nại</span>';
        case 'hoan_tien_sv':
            return '<span class="badge badge-purple">Admin hoàn tiền cho sinh viên</span>';
        case 'chuyen_tien_quan':
            return '<span class="badge badge-success">Admin chuyển tiền cho quán</span>';
        case 'huy_boi_khach':
            return '<span class="badge badge-gray">Hủy bởi khách</span>';
        case 'huy_vang_mat':
            return '<span class="badge badge-secondary">Hủy vắng mặt (Quán nhận tiền)</span>';
        default:
            return '<span class="badge">' . htmlspecialchars($status) . '</span>';
    }
}

// 12.1. Hàm hiển thị nhãn phương thức thanh toán
function get_payment_method_badge($method, $status = 'da_thanh_toan') {
    switch ($method) {
        case 'cash':
            $st_text = ($status === 'da_thanh_toan') ? 'Đã thu tiền' : 'Chưa thu tiền';
            $badge_cls = ($status === 'da_thanh_toan') ? 'badge-success' : 'badge-warning';
            return '<span class="badge ' . $badge_cls . '" title="Thanh toán tiền mặt khi nhận hàng">💵 Tiền mặt (' . $st_text . ')</span>';
        case 'bank_qr':
            $st_text = ($status === 'da_thanh_toan') ? 'Đã chuyển khoản' : 'Chờ xác nhận';
            $badge_cls = ($status === 'da_thanh_toan') ? 'badge-success' : 'badge-purple';
            return '<span class="badge ' . $badge_cls . '" title="Chuyển khoản VietQR - MB Bank">📲 VietQR (' . $st_text . ')</span>';
        case 'wallet':
        default:
            return '<span class="badge badge-info" title="Đã thanh toán trừ số dư Ví Ký Quỹ Escrow">💳 Ví Ký Quỹ Escrow</span>';
    }
}

// 13. Hàm upload ảnh an toàn (chỉ jpg, png, webp, < 2MB, đổi tên file ngẫu nhiên)
function upload_image_file($file, $target_dir) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Lỗi khi tải file lên (mã lỗi: ' . ($file['error'] ?? 'không xác định') . ')'];
    }

    // Kiểm tra dung lượng (tối đa 2MB = 2 * 1024 * 1024 bytes)
    if ($file['size'] > 2097152) {
        return ['success' => false, 'error' => 'Dung lượng ảnh vượt quá 2MB. Vui lòng chọn ảnh nhẹ hơn.'];
    }

    // Kiểm tra định dạng đuôi file
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed_exts)) {
        return ['success' => false, 'error' => 'Chỉ chấp nhận file ảnh định dạng JPG, PNG hoặc WEBP.'];
    }

    // Tạo tên file ngẫu nhiên tránh trùng lặp và an toàn
    $new_filename = uniqid('img_', true) . '_' . time() . '.' . $ext;
    $destination = rtrim($target_dir, '/\\') . DIRECTORY_SEPARATOR . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'error' => 'Không thể lưu file vào thư mục uploads.'];
    }
}

// 14. HÀM QUAN TRỌNG: check_orders()
// Tự động kiểm tra và xử lý các đơn hàng đến hạn:
// - Đơn "da_nhan" quá thời gian hold_until -> chuyển "hoan_thanh" + cộng tiền cho chủ quán (nếu thanh toán qua ví)
// - Đơn "cho_nhan" quá giờ đóng cửa close_at -> chuyển "huy_vang_mat" + cộng tiền cho chủ quán bồi thường (nếu thanh toán qua ví)
// - Đơn tranh chấp "cho_shop_phan_hoi" quá thời hạn dispute_response_hours -> tự động chuyển sang "cho_admin_xu_ly"
function check_orders($conn) {
    // -------------------------------------------------------------------------
    // TRƯỜNG HỢP 1: Đơn "da_nhan" đã hết thời gian hold_until (không có khiếu nại)
    // -------------------------------------------------------------------------
    $sql1 = "SELECT o.id, o.price, o.shop_id, s.owner_id, o.pickup_code, o.payment_method 
             FROM orders o
             JOIN shops s ON o.shop_id = s.id
             WHERE o.status = 'da_nhan' AND o.hold_until <= NOW()";
    $res1 = mysqli_query($conn, $sql1);
    if ($res1) {
        while ($order = mysqli_fetch_assoc($res1)) {
            $order_id = $order['id'];
            $price = $order['price'];
            $owner_id = $order['owner_id'];
            $pickup_code = $order['pickup_code'];
            $pay_method = $order['payment_method'] ?? 'wallet';

            // Bắt đầu Transaction cho từng đơn hàng độc lập
            mysqli_begin_transaction($conn);

            // Bước 1: Đổi trạng thái kèm điều kiện trạng thái cũ là 'da_nhan'
            $stmt_up = mysqli_prepare($conn, "UPDATE orders SET status = 'hoan_thanh', payment_status = 'da_thanh_toan' WHERE id = ? AND status = 'da_nhan'");
            mysqli_stmt_bind_param($stmt_up, "i", $order_id);
            mysqli_stmt_execute($stmt_up);
            $affected = mysqli_stmt_affected_rows($stmt_up);
            mysqli_stmt_close($stmt_up);

            if ($affected > 0) {
                // Bước 2: Nếu thanh toán qua ví ký quỹ, cộng tiền vào ví chủ quán
                if ($pay_method === 'wallet') {
                    $stmt_bal = mysqli_prepare($conn, "UPDATE users SET balance = balance + ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_bal, "di", $price, $owner_id);
                    mysqli_stmt_execute($stmt_bal);
                    mysqli_stmt_close($stmt_bal);

                    // Bước 3: Ghi nhật ký ví tiền của chủ quán
                    $reason = "Nhận tiền đơn hàng hoàn thành #" . $order_id . " (Mã: " . $pickup_code . ")";
                    $stmt_log = mysqli_prepare($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                    mysqli_stmt_bind_param($stmt_log, "idsi", $owner_id, $price, $reason, $order_id);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);
                }

                // Commit giao dịch
                mysqli_commit($conn);

                // Gửi thông báo đến chủ quán
                if ($pay_method === 'wallet') {
                    add_notification($conn, $owner_id, "Đơn hàng #" . $order_id . " đã hoàn thành tự động sau thời gian giữ tiền. Bạn nhận được " . format_money($price) . " vào ví.");
                } else {
                    add_notification($conn, $owner_id, "Đơn hàng #" . $order_id . " đã hoàn tất thành công.");
                }
            } else {
                // Đơn đã bị đổi trạng thái từ trước (ví dụ sinh viên vừa bấm tranh chấp), ROLLBACK
                mysqli_rollback($conn);
            }
        }
    }

    // -------------------------------------------------------------------------
    // TRƯỜNG HỢP 2: Đơn "cho_nhan" quá giờ đóng cửa close_at (khách không đến lấy)
    // -------------------------------------------------------------------------
    $sql2 = "SELECT o.id, o.price, o.shop_id, s.owner_id, o.user_id, o.pickup_code, o.payment_method 
             FROM orders o
             JOIN shops s ON o.shop_id = s.id
             WHERE o.status = 'cho_nhan' AND o.close_at <= NOW()";
    $res2 = mysqli_query($conn, $sql2);
    if ($res2) {
        while ($order = mysqli_fetch_assoc($res2)) {
            $order_id = $order['id'];
            $price = $order['price'];
            $owner_id = $order['owner_id'];
            $student_id = $order['user_id'];
            $pickup_code = $order['pickup_code'];
            $pay_method = $order['payment_method'] ?? 'wallet';

            // Bắt đầu Transaction cho từng đơn
            mysqli_begin_transaction($conn);

            // Bước 1: Đổi trạng thái sang 'huy_vang_mat' kèm điều kiện trạng thái cũ là 'cho_nhan'
            $stmt_up = mysqli_prepare($conn, "UPDATE orders SET status = 'huy_vang_mat' WHERE id = ? AND status = 'cho_nhan'");
            mysqli_stmt_bind_param($stmt_up, "i", $order_id);
            mysqli_stmt_execute($stmt_up);
            $affected = mysqli_stmt_affected_rows($stmt_up);
            mysqli_stmt_close($stmt_up);

            if ($affected > 0) {
                // Khách vắng mặt: Nếu thanh toán ví, bồi thường cho quán
                if ($pay_method === 'wallet') {
                    $stmt_bal = mysqli_prepare($conn, "UPDATE users SET balance = balance + ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_bal, "di", $price, $owner_id);
                    mysqli_stmt_execute($stmt_bal);
                    mysqli_stmt_close($stmt_bal);

                    // Ghi nhật ký ví tiền bồi thường
                    $reason = "Tiền bồi thường do khách vắng mặt đơn #" . $order_id . " (Mã: " . $pickup_code . ")";
                    $stmt_log = mysqli_prepare($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                    mysqli_stmt_bind_param($stmt_log, "idsi", $owner_id, $price, $reason, $order_id);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);
                }

                mysqli_commit($conn);

                // Gửi thông báo cho sinh viên và chủ quán
                add_notification($conn, $student_id, "Đơn hàng #" . $order_id . " bị hủy do quá giờ lấy hàng (Vắng mặt). Tiền không được hoàn lại.");
                add_notification($conn, $owner_id, "Đơn hàng #" . $order_id . " khách vắng mặt không đến lấy. Bạn đã nhận tiền bồi thường " . format_money($price) . ".");
            } else {
                mysqli_rollback($conn);
            }
        }
    }

    // -------------------------------------------------------------------------
    // TRƯỜNG HỢP 3: Tranh chấp quá thời hạn dispute_response_hours (quán không phản đối)
    // -------------------------------------------------------------------------
    $dispute_hours = (int)get_setting($conn, 'dispute_response_hours', 48);
    $sql3 = "SELECT d.id, d.order_id 
             FROM disputes d
             WHERE d.status = 'cho_shop_phan_hoi' 
             AND d.created_at <= DATE_SUB(NOW(), INTERVAL ? HOUR)";
    $stmt3 = mysqli_prepare($conn, $sql3);
    if ($stmt3) {
        mysqli_stmt_bind_param($stmt3, "i", $dispute_hours);
        mysqli_stmt_execute($stmt3);
        $res3 = mysqli_stmt_get_result($stmt3);
        while ($disp = mysqli_fetch_assoc($res3)) {
            $disp_id = $disp['id'];
            // Chuyển sang cho admin xử lý vì quán đã hết hạn phản hồi
            $stmt_disp = mysqli_prepare($conn, "UPDATE disputes SET status = 'cho_admin_xu_ly' WHERE id = ? AND status = 'cho_shop_phan_hoi'");
            mysqli_stmt_bind_param($stmt_disp, "i", $disp_id);
            mysqli_stmt_execute($stmt_disp);
            mysqli_stmt_close($stmt_disp);
        }
        mysqli_stmt_close($stmt3);
    }
}

// -----------------------------------------------------------------------------
// CÁC HÀM BỔ SUNG MỚI CHO TÍNH NĂNG ĐÁNH GIÁ, VOUCHER, ECO-IMPACT & LEADERBOARD
// -----------------------------------------------------------------------------

// 15. Tính toán chỉ số tác động môi trường từ số lượng túi giải cứu
function calculate_eco_impact($bag_count) {
    $bag_count = max(0, (int)$bag_count);
    return [
        'bags' => $bag_count,
        'food_kg' => round($bag_count * 0.85, 1),      // Trung bình 1 túi chứa 0.85kg thức ăn
        'co2_kg' => round($bag_count * 2.12, 1),       // 1 kg thực phẩm vứt bỏ sinh ra ~ 2.5kg CO2 tương đương
        'water_liters' => round($bag_count * 165, 0),  // Lượng nước ảo tiêu thụ cho nông nghiệp
    ];
}

// 16. Lấy điểm đánh giá trung bình và số lượng review của cửa hàng
function get_shop_rating($conn, $shop_id) {
    $shop_id = (int)$shop_id;
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total, COALESCE(AVG(rating), 5.0) AS avg_rate FROM reviews WHERE shop_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $shop_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    $total = (int)($row['total'] ?? 0);
    $avg = round((float)($row['avg_rate'] ?? 5.0), 1);
    if ($total === 0) {
        $avg = 5.0; // Mặc định 5 sao cho quán mới
    }
    return [
        'total' => $total,
        'avg' => $avg
    ];
}

// 17. Lấy danh sách đánh giá gần nhất của quán kèm tên sinh viên
function get_shop_reviews($conn, $shop_id, $limit = 5) {
    $shop_id = (int)$shop_id;
    $limit = (int)$limit;
    $sql = "SELECT r.*, u.name AS student_name, u.university 
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.shop_id = ?
            ORDER BY r.id DESC LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $shop_id, $limit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $reviews = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $reviews[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $reviews;
}

// 18. Lấy bảng vinh danh Top sinh viên giải cứu nhiều nhất
function get_leaderboard_students($conn, $limit = 5) {
    $limit = (int)$limit;
    $sql = "SELECT u.id, u.name, u.email, u.university, 
                   COUNT(o.id) AS rescued_count, 
                   COALESCE(SUM(b.original_price - o.price), 0) AS total_saved
            FROM users u
            JOIN orders o ON u.id = o.user_id
            JOIN bags b ON o.bag_id = b.id
            WHERE o.status IN ('hoan_thanh', 'chuyen_tien_quan')
            GROUP BY u.id, u.name, u.email, u.university
            ORDER BY rescued_count DESC, total_saved DESC
            LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $list = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $list[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $list;
}

// 19. Lấy bảng vinh danh Top quán giải cứu nhiều nhất tại Đà Nẵng
function get_leaderboard_shops($conn, $limit = 5) {
    $limit = (int)$limit;
    $sql = "SELECT s.id, s.name, s.district, s.type, s.image,
                   COUNT(o.id) AS rescued_count,
                   COALESCE(SUM(o.price), 0) AS total_revenue
            FROM shops s
            JOIN orders o ON s.id = o.shop_id
            WHERE o.status IN ('hoan_thanh', 'chuyen_tien_quan')
            GROUP BY s.id, s.name, s.district, s.type, s.image
            ORDER BY rescued_count DESC, total_revenue DESC
            LIMIT ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $list = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $list[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $list;
}

// 20. Kiểm tra tính hợp lệ của mã giảm giá (Voucher)
function check_voucher($conn, $code, $order_amount) {
    $code = strtoupper(trim($code));
    if (empty($code)) {
        return ['valid' => false, 'error' => 'Vui lòng nhập mã voucher.'];
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM vouchers WHERE code = ? AND expires_at > NOW() LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $voucher = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$voucher) {
        return ['valid' => false, 'error' => "Mã voucher '{$code}' không tồn tại hoặc đã hết hạn sử dụng."];
    }

    if ($voucher['used_count'] >= $voucher['quantity']) {
        return ['valid' => false, 'error' => "Mã voucher '{$code}' đã hết số lượng lượt dùng."];
    }

    if ($order_amount < $voucher['min_order_price']) {
        return [
            'valid' => false, 
            'error' => "Đơn hàng tối thiểu phải từ " . format_money($voucher['min_order_price']) . " để áp dụng mã '{$code}'."
        ];
    }

    $discount = min($voucher['discount_amount'], $order_amount);
    return [
        'valid' => true,
        'voucher' => $voucher,
        'discount' => $discount
    ];
}

// 21. Thống kê cá nhân của sinh viên
function get_user_eco_stats($conn, $user_id) {
    $user_id = (int)$user_id;
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total_orders, 
                                          COALESCE(SUM(b.original_price - o.price), 0) AS total_saved 
                                   FROM orders o 
                                   JOIN bags b ON o.bag_id = b.id 
                                   WHERE o.user_id = ? AND o.status IN ('hoan_thanh', 'chuyen_tien_quan')");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    $count = (int)($data['total_orders'] ?? 0);
    $saved = (float)($data['total_saved'] ?? 0);
    $eco = calculate_eco_impact($count);

    // Xếp hạng danh hiệu Sống Xanh
    $rank_title = '🌱 Mầm Xanh Mới Bắt Đầu';
    if ($count >= 10) {
        $rank_title = '💎 Đại Sứ Sống Xanh Kim Cương';
    } elseif ($count >= 5) {
        $rank_title = '🏆 Hiệp Sĩ Cứu Trợ Vàng';
    } elseif ($count >= 2) {
        $rank_title = '⭐ Chiến Binh Sống Xanh Bạc';
    }

    return [
        'count' => $count,
        'saved' => $saved,
        'eco' => $eco,
        'rank' => $rank_title
    ];
}
?>
