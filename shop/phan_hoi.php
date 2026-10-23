<?php
// FILE: shop/phan_hoi.php
// Mục đích: Chủ quán phản đối khiếu nại của sinh viên, đính kèm ghi chú và hình ảnh đối chứng trong 48 giờ

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('shop_owner');
$user = get_logged_in_user($conn);
$shop = get_shop_by_owner_id($conn, $user['id']);

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    header("Location: " . BASE_URL . "shop/don_hang.php");
    exit;
}

// Lấy thông tin đơn và tranh chấp
$sql = "SELECT o.*, d.id AS dispute_id, d.reason, d.student_note, d.student_image, d.shop_note, d.shop_image, d.status AS dispute_status, d.created_at AS dispute_created_at,
               u.name AS student_name, b.name AS bag_name
        FROM orders o
        JOIN disputes d ON o.id = d.order_id
        JOIN users u ON o.user_id = u.id
        JOIN bags b ON o.bag_id = b.id
        WHERE o.id = ? AND o.shop_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $shop['id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$data) {
    die("Lỗi: Không tìm thấy tranh chấp khiếu nại cho đơn hàng này.");
}

$errors = [];
$notice = '';
$dispute_hours = (int)get_setting($conn, 'dispute_response_hours', 48);
$dispute_deadline = strtotime($data['dispute_created_at']) + ($dispute_hours * 3600);
$is_expired = (time() > $dispute_deadline);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_expired) {
        $errors[] = "Đã quá thời hạn {$dispute_hours} giờ để gửi phản đối. Hồ sơ đã được chuyển thẳng cho Admin xử lý.";
    } else {
        $shop_note = trim($_POST['shop_note'] ?? '');
        if (empty($shop_note)) {
            $errors[] = 'Vui lòng nhập nội dung giải trình hoặc phản đối của quán.';
        }

        // Upload ảnh đối chứng của quán nếu có
        $shop_image = $data['shop_image'];
        if (isset($_FILES['shop_image']) && $_FILES['shop_image']['error'] === UPLOAD_ERR_OK) {
            $up = upload_image_file($_FILES['shop_image'], UPLOAD_DIR);
            if ($up['success']) {
                $shop_image = $up['filename'];
            } else {
                $errors[] = $up['error'];
            }
        }

        if (empty($errors)) {
            // Cập nhật phản hồi của quán và chuyển trạng thái dispute sang 'cho_admin_xu_ly'
            $stmt_up = mysqli_prepare($conn, "UPDATE disputes SET shop_note = ?, shop_image = ?, status = 'cho_admin_xu_ly' WHERE id = ? AND status = 'cho_shop_phan_hoi'");
            mysqli_stmt_bind_param($stmt_up, "ssi", $shop_note, $shop_image, $data['dispute_id']);
            $ok = mysqli_stmt_execute($stmt_up);
            mysqli_stmt_close($stmt_up);

            if ($ok) {
                // Thông báo cho sinh viên và Admin
                add_notification($conn, $data['user_id'], "Quán '" . $shop['name'] . "' đã phản hồi khiếu nại của bạn cho đơn #" . $data['id'] . ". Hồ sơ đã được chuyển cho Admin phân xử.");
                
                header("Location: " . BASE_URL . "shop/don_hang.php?dispute_replied=1");
                exit;
            } else {
                $errors[] = 'Không thể cập nhật phản hồi, vui lòng thử lại.';
            }
        }
    }
}

$rem_hours = ceil(($dispute_deadline - time()) / 3600);
$page_title = 'Phản đối khiếu nại đơn #' . $data['id'];
require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 700px; margin: 20px auto;">
    <div style="margin-bottom: 15px;">
        <a href="<?php echo BASE_URL; ?>shop/don_hang.php" style="text-decoration: none; color: var(--primary-color); font-weight: 600;">
            &larr; Quay lại danh sách đơn hàng
        </a>
    </div>

    <div class="card">
        <h2 class="card-title" style="color: var(--danger-color);">
            Phản Hồi Khiếu Nại Đơn #<?php echo $data['id']; ?>
        </h2>

        <!-- Khối thông tin khiếu nại của sinh viên -->
        <div style="background: #ffebee; border: 1px solid #ffcdd2; padding: 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            <h4 style="color: #c62828; margin-bottom: 8px;">Thông tin khiếu nại từ sinh viên (<?php echo htmlspecialchars($data['student_name']); ?>):</h4>
            <div><strong>Túi bất ngờ:</strong> <?php echo htmlspecialchars($data['bag_name']); ?></div>
            <div><strong>Lý do khiếu nại:</strong> <span style="color: #b71c1c; font-weight: bold;"><?php echo htmlspecialchars($data['reason']); ?></span></div>
            <div><strong>Ghi chú của sinh viên:</strong> <?php echo nl2br(htmlspecialchars($data['student_note'] ?? '')); ?></div>
            
            <?php if (!empty($data['student_image'])): ?>
                <div style="margin-top: 10px;">
                    <strong>Hình ảnh bằng chứng khách gửi:</strong><br>
                    <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($data['student_image']); ?>" alt="Ảnh chứng minh khách" style="max-width: 250px; border-radius: 6px; border: 1px solid #ccc; margin-top: 4px;">
                </div>
            <?php endif; ?>
        </div>

        <!-- Thời hạn phản hồi -->
        <div style="margin-bottom: 18px;">
            <?php if ($is_expired): ?>
                <div class="alert alert-danger">
                    Đã hết thời hạn phản đối (<?php echo $dispute_hours; ?> giờ). Đơn đã được tự động chuyển cho Quản trị viên phân xử.
                </div>
            <?php else: ?>
                <div class="time-countdown">
                    ⏱ Bạn còn khoảng <strong><?php echo max(0, $rem_hours); ?> giờ</strong> để gửi phản đối giải trình trước khi Admin phân xử.
                </div>
            <?php endif; ?>
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

        <?php if (!$is_expired && $data['dispute_status'] === 'cho_shop_phan_hoi'): ?>
            <form action="<?php echo BASE_URL; ?>shop/phan_hoi.php?order_id=<?php echo $data['id']; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Nội dung giải trình / Phản đối của quán:</label>
                    <textarea name="shop_note" class="form-control" rows="4" placeholder="Giải thích quy trình đóng gói, tình trạng thực phẩm khi bàn giao..." required><?php echo htmlspecialchars($_POST['shop_note'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Hình ảnh đối chứng (tùy chọn, tối đa 2MB, JPG/PNG/WEBP):</label>
                    <input type="file" name="shop_image" class="form-control" accept="image/png, image/jpeg, image/webp">
                    <small class="form-text">Ảnh chụp túi trước khi giao hoặc hình ảnh tại quầy.</small>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">GỬI PHẢN ĐỐI CHO ADMIN</button>
                    <a href="<?php echo BASE_URL; ?>shop/don_hang.php" class="btn btn-outline">Quay lại</a>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                <?php if (!empty($data['shop_note'])): ?>
                    <strong>Quán đã gửi phản đối:</strong><br>
                    <?php echo nl2br(htmlspecialchars($data['shop_note'])); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
