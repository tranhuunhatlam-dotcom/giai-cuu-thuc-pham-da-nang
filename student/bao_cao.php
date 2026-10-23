<?php
// FILE: student/bao_cao.php
// Mục đích: Sinh viên báo cáo gian lận, thực phẩm ôi thiu, hỏng mốc hoặc sai mô tả
// Yêu cầu: Bắt buộc chọn lý do, upload ít nhất 1 ảnh bằng chứng và kiểm tra giới hạn tranh chấp trong tháng

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('student');
$user = get_logged_in_user($conn);

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    header("Location: " . BASE_URL . "student/don_hang.php");
    exit;
}

// Lấy thông tin đơn hàng và quán
$sql = "SELECT o.*, b.name AS bag_name, s.name AS shop_name, s.owner_id
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
    die("Lỗi: Không tìm thấy đơn hàng.");
}

// Kiểm tra điều kiện trạng thái: Chỉ cho_nhan hoặc da_nhan (khi còn trong hold_until)
$now = time();
$is_valid_status = false;

if ($order['status'] === 'cho_nhan') {
    $is_valid_status = true;
} elseif ($order['status'] === 'da_nhan' && !empty($order['hold_until'])) {
    if (strtotime($order['hold_until']) > $now) {
        $is_valid_status = true;
    }
}

if (!$is_valid_status) {
    die("Lỗi: Đơn hàng này không còn trong thời gian được phép khiếu nại (Đơn đã hoàn thành hoặc đã quá thời hạn giữ tiền).");
}

$errors = [];
$max_disputes = (int)get_setting($conn, 'max_disputes_per_month', 3);

// Kiểm tra số lần khiếu nại của sinh viên trong tháng hiện tại
$sql_count = "SELECT COUNT(*) AS total 
              FROM disputes d 
              JOIN orders o ON d.order_id = o.id 
              WHERE o.user_id = ? 
              AND MONTH(d.created_at) = MONTH(CURDATE()) 
              AND YEAR(d.created_at) = YEAR(CURDATE())";
$stmt_c = mysqli_prepare($conn, $sql_count);
mysqli_stmt_bind_param($stmt_c, "i", $user['id']);
mysqli_stmt_execute($stmt_c);
$res_c = mysqli_stmt_get_result($stmt_c);
$dispute_count_month = (int)mysqli_fetch_assoc($res_c)['total'];
mysqli_stmt_close($stmt_c);

if ($dispute_count_month >= $max_disputes) {
    $errors[] = "Bạn đã đạt giới hạn tối đa {$max_disputes} lần khiếu nại trong tháng này. Hãy liên hệ trực tiếp Admin để được hỗ trợ đặc biệt.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $reason = trim($_POST['reason'] ?? '');
    $student_note = trim($_POST['student_note'] ?? '');

    if (empty($reason)) {
        $errors[] = 'Vui lòng chọn lý do khiếu nại / gian lận.';
    }

    // Bắt buộc upload >= 1 ảnh làm bằng chứng
    $student_image = null;
    if (!isset($_FILES['student_image']) || $_FILES['student_image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Bắt buộc phải tải lên ít nhất 1 hình ảnh chụp thực tế đồ ăn hoặc sự cố làm bằng chứng.';
    } else {
        $up = upload_image_file($_FILES['student_image'], UPLOAD_DIR);
        if ($up['success']) {
            $student_image = $up['filename'];
        } else {
            $errors[] = $up['error'];
        }
    }

    if (empty($errors)) {
        // BẮT ĐẦU TRANSACTION CHUYỂN TRẠNG THÁI VÀ TẠO BẢN GHI TRANH CHẤP
        mysqli_begin_transaction($conn);

        // 1. Cập nhật trạng thái đơn sang 'tranh_chap' kèm điều kiện trạng thái cũ hợp lệ
        $stmt_up = mysqli_prepare($conn, "UPDATE orders SET status = 'tranh_chap' WHERE id = ? AND user_id = ? AND (status = 'cho_nhan' OR (status = 'da_nhan' AND hold_until > NOW()))");
        mysqli_stmt_bind_param($stmt_up, "ii", $order['id'], $user['id']);
        mysqli_stmt_execute($stmt_up);
        $aff_up = mysqli_stmt_affected_rows($stmt_up);
        mysqli_stmt_close($stmt_up);

        if ($aff_up > 0) {
            // 2. Thêm bản ghi khiếu nại vào bảng disputes
            $stmt_disp = mysqli_prepare($conn, "INSERT INTO disputes (order_id, reason, student_note, student_image, status, created_at) VALUES (?, ?, ?, ?, 'cho_shop_phan_hoi', NOW())");
            mysqli_stmt_bind_param($stmt_disp, "isss", $order['id'], $reason, $student_note, $student_image);
            mysqli_stmt_execute($stmt_disp);
            mysqli_stmt_close($stmt_disp);

            // Tiền vẫn được giữ nguyên trong đơn, không hoàn tự động và không chuyển quán
            mysqli_commit($conn);

            // Gửi thông báo cho chủ quán và sinh viên
            $dispute_hours = (int)get_setting($conn, 'dispute_response_hours', 48);
            add_notification($conn, $order['owner_id'], "Đơn hàng #" . $order['id'] . " có khiếu nại từ khách hàng: '{$reason}'. Quán có {$dispute_hours} giờ để gửi ảnh/ghi chú phản đối.");
            add_notification($conn, $user['id'], "Khiếu nại cho đơn #" . $order['id'] . " đã được gửi thành công. Tiền đang được đóng băng để bảo vệ bạn.");

            header("Location: " . BASE_URL . "student/don_chi_tiet.php?id=" . $order['id'] . "&reported=1");
            exit;
        } else {
            mysqli_rollback($conn);
            $errors[] = 'Không thể gửi khiếu nại do trạng thái đơn hàng đã bị thay đổi hoặc đã hết thời gian giữ tiền.';
        }
    }
}

$page_title = 'Báo cáo gian lận đơn #' . $order['id'];
require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 650px; margin: 20px auto;">
    <div style="margin-bottom: 15px;">
        <a href="<?php echo BASE_URL; ?>student/don_chi_tiet.php?id=<?php echo $order['id']; ?>" style="text-decoration: none; color: var(--primary-color); font-weight: 600;">
            &larr; Quay lại đơn hàng
        </a>
    </div>

    <div class="card">
        <h2 class="card-title" style="color: var(--danger-color);">Báo Cáo Gian Lận / Khiếu Nại Đơn #<?php echo $order['id']; ?></h2>

        <div class="alert alert-warning">
            <strong>Cơ chế bảo vệ sinh viên:</strong><br>
            Khi gửi báo cáo này, đơn hàng sẽ chuyển sang trạng thái <strong>Tranh chấp</strong>. Tiền tạm giữ sẽ được <strong>đóng băng</strong> (không chuyển cho quán). Chủ quán có 48 giờ để phản hồi, sau đó Admin sẽ trực tiếp phân xử công minh.
        </div>

        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">
            Số khiếu nại của bạn trong tháng này: <strong><?php echo $dispute_count_month; ?> / <?php echo $max_disputes; ?> lần</strong>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin-left: 20px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($dispute_count_month < $max_disputes): ?>
            <form action="<?php echo BASE_URL; ?>student/bao_cao.php?order_id=<?php echo $order['id']; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Lý do khiếu nại chính (bắt buộc):</label>
                    <select name="reason" class="form-control" required>
                        <option value="">-- Chọn lý do --</option>
                        <option value="Đồ ăn ôi thiu / nấm mốc / bốc mùi" <?php echo (($_POST['reason'] ?? '') === 'Đồ ăn ôi thiu / nấm mốc / bốc mùi') ? 'selected' : ''; ?>>Đồ ăn ôi thiu / nấm mốc / bốc mùi</option>
                        <option value="Giá trị thực tế quá thấp so với mô tả" <?php echo (($_POST['reason'] ?? '') === 'Giá trị thực tế quá thấp so với mô tả') ? 'selected' : ''; ?>>Giá trị thực tế quá thấp so với mô tả</option>
                        <option value="Quán đóng cửa sớm / không có ai giao túi" <?php echo (($_POST['reason'] ?? '') === 'Quán đóng cửa sớm / không có ai giao túi') ? 'selected' : ''; ?>>Quán đóng cửa sớm / không có ai giao túi</option>
                        <option value="Giao sai loại thực phẩm hoặc túi rỗng" <?php echo (($_POST['reason'] ?? '') === 'Giao sai loại thực phẩm hoặc túi rỗng') ? 'selected' : ''; ?>>Giao sai loại thực phẩm hoặc túi rỗng</option>
                        <option value="Lý do khác" <?php echo (($_POST['reason'] ?? '') === 'Lý do khác') ? 'selected' : ''; ?>>Lý do khác</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả cụ thể sự việc:</label>
                    <textarea name="student_note" class="form-control" rows="4" placeholder="Mô tả chi tiết tình trạng túi thực phẩm bạn nhận được..."><?php echo htmlspecialchars($_POST['student_note'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Hình ảnh chụp thực tế bằng chứng (Bắt buộc, tối đa 2MB, JPG/PNG/WEBP):</label>
                    <input type="file" name="student_image" class="form-control" accept="image/png, image/jpeg, image/webp" required>
                    <small class="form-text">Vui lòng chụp rõ món ăn bị hỏng hoặc bao bì túi để làm căn cứ cho Admin phân xử.</small>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-danger" style="padding: 12px 24px;">GỬI BÁO CÁO GIAN LẬN</button>
                    <a href="<?php echo BASE_URL; ?>student/don_chi_tiet.php?id=<?php echo $order['id']; ?>" class="btn btn-outline">Hủy bỏ</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
