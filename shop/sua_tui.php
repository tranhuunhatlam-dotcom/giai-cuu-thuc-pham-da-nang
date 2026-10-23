<?php
// FILE: shop/sua_tui.php
// Mục đích: Chỉnh sửa thông tin túi bất ngờ, bảo vệ không cho đổi giá khi đã có sinh viên đặt hàng

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

// Lấy thông tin túi và kiểm tra xem có thuộc quán này không
$sql = "SELECT * FROM bags WHERE id = ? AND shop_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $bag_id, $shop['id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$bag = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$bag) {
    die("Lỗi: Không tìm thấy túi hoặc bạn không có quyền sửa túi này.");
}

// Kiểm tra xem túi đã có người đặt hay chưa
$sql_ord = "SELECT COUNT(*) AS total_orders FROM orders WHERE bag_id = ? AND status != 'huy_boi_khach'";
$stmt_ord = mysqli_prepare($conn, $sql_ord);
mysqli_stmt_bind_param($stmt_ord, "i", $bag_id);
mysqli_stmt_execute($stmt_ord);
$res_ord = mysqli_stmt_get_result($stmt_ord);
$ord_row = mysqli_fetch_assoc($res_ord);
$has_orders = ($ord_row['total_orders'] > 0);
mysqli_stmt_close($stmt_ord);

$errors = [];
$discount_min = (float)get_setting($conn, 'discount_min', 30);
$discount_max = (float)get_setting($conn, 'discount_max', 50);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    $open_at = trim($_POST['open_at'] ?? '');
    $pickup_start = trim($_POST['pickup_start'] ?? '');
    $pickup_end = trim($_POST['pickup_end'] ?? '');
    $close_at = trim($_POST['close_at'] ?? '');

    // Nếu chưa có ai đặt thì lấy giá từ form, nếu đã có người đặt thì giữ nguyên giá cũ
    if (!$has_orders) {
        $original_price = (float)($_POST['original_price'] ?? 0);
        $web_price = (float)($_POST['web_price'] ?? 0);
    } else {
        $original_price = (float)$bag['original_price'];
        $web_price = (float)$bag['web_price'];
    }

    if (empty($name) || $stock < 0) {
        $errors[] = 'Vui lòng nhập tên túi và số lượng tồn kho (>= 0).';
    }

    if (!$has_orders) {
        if ($original_price <= 0 || $web_price <= 0) {
            $errors[] = 'Giá gốc và giá web phải lớn hơn 0.';
        } else {
            $percent = (1 - ($web_price / $original_price)) * 100;
            $percent_round = round($percent, 1);
            if ($percent_round < $discount_min || $percent_round > $discount_max) {
                $errors[] = "Giá bán trên web phải giảm từ {$discount_min}% đến {$discount_max}% so với giá gốc. Hiện tại là {$percent_round}%.";
            }
        }
    }

    // Kiểm tra thứ tự thời gian
    $t_open = strtotime($open_at);
    $t_pick_start = strtotime($pickup_start);
    $t_pick_end = strtotime($pickup_end);
    $t_close = strtotime($close_at);
    if (!($t_open < $t_pick_start && $t_pick_start < $t_pick_end && $t_pick_end <= $t_close)) {
        $errors[] = 'Thời gian không hợp lệ! Bắt buộc: Giờ mở bán < Bắt đầu nhận < Kết thúc nhận <= Giờ đóng cửa.';
    }

    // Ảnh đại diện
    $image_filename = $bag['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $up = upload_image_file($_FILES['image'], UPLOAD_DIR);
        if ($up['success']) {
            $image_filename = $up['filename'];
        } else {
            $errors[] = $up['error'];
        }
    }

    if (empty($errors)) {
        $sql_up = "UPDATE bags SET name = ?, description = ?, image = ?, original_price = ?, web_price = ?, stock = ?, open_at = ?, pickup_start = ?, pickup_end = ?, close_at = ? WHERE id = ? AND shop_id = ?";
        $stmt_up = mysqli_prepare($conn, $sql_up);
        mysqli_stmt_bind_param($stmt_up, "sssddissssii", $name, $description, $image_filename, $original_price, $web_price, $stock, $open_at, $pickup_start, $pickup_end, $close_at, $bag_id, $shop['id']);
        
        if (mysqli_stmt_execute($stmt_up)) {
            mysqli_stmt_close($stmt_up);
            header("Location: " . BASE_URL . "shop/danh_sach_tui.php?updated=1");
            exit;
        } else {
            $errors[] = 'Lỗi cập nhật CSDL.';
            mysqli_stmt_close($stmt_up);
        }
    }
}

$page_title = 'Sửa túi bất ngờ';
require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 750px; margin: 20px auto;">
    <div class="card">
        <h2 class="card-title">Chỉnh Sửa Túi Bất Ngờ #<?php echo $bag['id']; ?></h2>

        <?php if ($has_orders): ?>
            <div class="alert alert-warning">
                <strong>Lưu ý:</strong> Túi này <strong>đã có đơn hàng</strong> từ khách, hệ thống <strong>khóa trường giá</strong> (giá gốc & giá web) để đảm bảo tính minh bạch cho khách đã mua.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin-left: 20px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>shop/sua_tui.php?id=<?php echo $bag['id']; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Tên túi bất ngờ:</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? $bag['name']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Mô tả túi:</label>
                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? $bag['description']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-col form-group">
                    <label class="form-label">Giá gốc tại quán (VNĐ):</label>
                    <input type="number" name="original_price" class="form-control" value="<?php echo htmlspecialchars($_POST['original_price'] ?? $bag['original_price']); ?>" <?php echo $has_orders ? 'readonly style="background:#eee;"' : ''; ?> required>
                </div>
                <div class="form-col form-group">
                    <label class="form-label">Giá bán trên web (VNĐ):</label>
                    <input type="number" name="web_price" class="form-control" value="<?php echo htmlspecialchars($_POST['web_price'] ?? $bag['web_price']); ?>" <?php echo $has_orders ? 'readonly style="background:#eee;"' : ''; ?> required>
                </div>
                <div class="form-col form-group">
                    <label class="form-label">Số lượng còn lại trong kho:</label>
                    <input type="number" name="stock" class="form-control" value="<?php echo htmlspecialchars($_POST['stock'] ?? $bag['stock']); ?>" min="0" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col form-group">
                    <label class="form-label">1. Giờ mở bán online:</label>
                    <input type="datetime-local" name="open_at" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($_POST['open_at'] ?? $bag['open_at'])); ?>" required>
                </div>
                <div class="form-col form-group">
                    <label class="form-label">2. Giờ bắt đầu nhận túi:</label>
                    <input type="datetime-local" name="pickup_start" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($_POST['pickup_start'] ?? $bag['pickup_start'])); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col form-group">
                    <label class="form-label">3. Giờ kết thúc nhận túi:</label>
                    <input type="datetime-local" name="pickup_end" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($_POST['pickup_end'] ?? $bag['pickup_end'])); ?>" required>
                </div>
                <div class="form-col form-group">
                    <label class="form-label">4. Giờ đóng cửa quán:</label>
                    <input type="datetime-local" name="close_at" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($_POST['close_at'] ?? $bag['close_at'])); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Hình ảnh hiện tại:</label>
                <?php if (!empty($bag['image'])): ?>
                    <div style="margin-bottom: 8px;">
                        <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($bag['image']); ?>" alt="Ảnh túi" style="max-width: 160px; height: auto; border-radius: 4px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/png, image/jpeg, image/webp">
                <small class="form-text">Chọn ảnh mới nếu muốn thay thế ảnh hiện tại.</small>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">LƯU CẬP NHẬT</button>
                <a href="<?php echo BASE_URL; ?>shop/danh_sach_tui.php" class="btn btn-outline">Quay lại</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
