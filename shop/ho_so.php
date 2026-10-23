<?php
// FILE: shop/ho_so.php
// Mục đích: Quản lý hồ sơ cửa hàng, trạng thái duyệt và số lần vi phạm

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('shop_owner');
$user = get_logged_in_user($conn);
$shop = get_shop_by_owner_id($conn, $user['id']);

$notice = '';
$error = '';

if (!$shop) {
    die("Lỗi: Không tìm thấy hồ sơ quán của bạn. Vui lòng liên hệ Admin.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $district = trim($_POST['district'] ?? 'Hải Châu');
    $type = trim($_POST['type'] ?? 'Bánh ngọt & Bánh mì');

    if (empty($name) || empty($address)) {
        $error = 'Vui lòng nhập đầy đủ tên quán và địa chỉ.';
    } else {
        $image_filename = $shop['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $up = upload_image_file($_FILES['image'], UPLOAD_DIR);
            if ($up['success']) {
                $image_filename = $up['filename'];
            } else {
                $error = $up['error'];
            }
        }

        if (empty($error)) {
            $stmt = mysqli_prepare($conn, "UPDATE shops SET name = ?, address = ?, district = ?, type = ?, image = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssssi", $name, $address, $district, $type, $image_filename, $shop['id']);
            if (mysqli_stmt_execute($stmt)) {
                $notice = 'Cập nhật thông tin cửa hàng thành công!';
                $shop = get_shop_by_owner_id($conn, $user['id']); // Lấy lại dữ liệu mới
            } else {
                $error = 'Lỗi cập nhật CSDL.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$page_title = 'Hồ sơ cửa hàng';
require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 700px; margin: 20px auto;">
    <div class="card">
        <h2 class="card-title">Hồ Sơ Cửa Hàng</h2>

        <?php if (!empty($notice)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Trạng thái phê duyệt của quán -->
        <div style="margin-bottom: 20px; padding: 14px; border-radius: 8px; background: <?php echo ($shop['status'] === 'da_duyet') ? '#e8f5e9' : '#fff3e0'; ?>; border: 1px solid <?php echo ($shop['status'] === 'da_duyet') ? '#81c784' : '#ffb74d'; ?>;">
            <div>
                Trạng thái quán: 
                <?php if ($shop['status'] === 'da_duyet'): ?>
                    <span class="badge badge-success">Đã được Admin duyệt</span> - Được phép đăng bán túi bất ngờ
                <?php else: ?>
                    <span class="badge badge-warning">Chờ Admin duyệt</span> - Quán của bạn đang chờ quản trị viên xác nhận thông tin
                <?php endif; ?>
            </div>
            <div style="margin-top: 6px;">
                Số lần bị ghi nhận vi phạm: <strong><?php echo $shop['violation_count']; ?> lần</strong>
                <?php if ($shop['violation_count'] > 0): ?>
                    <span style="color: var(--danger-color); font-size: 13px;">(Chú ý tuân thủ chất lượng túi giải cứu để tránh bị khóa quán)</span>
                <?php endif; ?>
            </div>
        </div>

        <form action="<?php echo BASE_URL; ?>shop/ho_so.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Tên cửa hàng:</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($shop['name']); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-col form-group">
                    <label class="form-label">Quận tại Đà Nẵng:</label>
                    <select name="district" class="form-control">
                        <?php
                        $districts = ['Hải Châu', 'Thanh Khê', 'Sơn Trà', 'Ngũ Hành Sơn', 'Liên Chiểu', 'Cẩm Lệ', 'Hòa Vang'];
                        foreach ($districts as $d) {
                            $sel = ($shop['district'] === $d) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($d) . "\" $sel>" . htmlspecialchars($d) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-col form-group">
                    <label class="form-label">Loại quán:</label>
                    <select name="type" class="form-control">
                        <?php
                        $types = ['Bánh ngọt & Bánh mì', 'Cơm & Bún phở', 'Trà sữa & Cafe', 'Thức ăn nhanh', 'Đồ chay', 'Khác'];
                        foreach ($types as $t) {
                            $sel = ($shop['type'] === $t) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($t) . "\" $sel>" . htmlspecialchars($t) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Địa chỉ quán:</label>
                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($shop['address']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Ảnh quán hiện tại:</label>
                <?php if (!empty($shop['image'])): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($shop['image']); ?>" alt="Ảnh quán" style="max-width: 200px; height: auto; border-radius: 6px; border: 1px solid #ccc;">
                    </div>
                <?php else: ?>
                    <p class="form-text">Chưa có ảnh đại diện.</p>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/png, image/jpeg, image/webp">
                <small class="form-text">Chọn ảnh mới nếu muốn thay đổi (tối đa 2MB, JPG/PNG/WEBP).</small>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">LƯU THAY ĐỔI</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
