<?php
// FILE: dang_ky.php
// Mục đích: Đăng ký tài khoản cho Sinh viên (student) hoặc Chủ quán (shop_owner)

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$errors = [];
$success = '';

// Nếu đã đăng nhập rồi thì chuyển về trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role = $_POST['role'] ?? 'student';

    // 1. Kiểm tra hợp lệ dữ liệu cơ bản
    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = 'Vui lòng điền đầy đủ họ tên, email và mật khẩu.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Địa chỉ email không đúng định dạng.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    } elseif (!in_array($role, ['student', 'shop_owner'])) {
        $errors[] = 'Vai trò đăng ký không hợp lệ.';
    }

    // 2. Nếu là chủ quán, kiểm tra thêm thông tin quán
    $shop_name = trim($_POST['shop_name'] ?? '');
    $shop_address = trim($_POST['shop_address'] ?? '');
    $shop_district = trim($_POST['shop_district'] ?? 'Hải Châu');
    $shop_type = trim($_POST['shop_type'] ?? 'Bánh ngọt');

    if ($role === 'shop_owner') {
        if (empty($shop_name) || empty($shop_address)) {
            $errors[] = 'Vui lòng nhập tên cửa hàng và địa chỉ tại Đà Nẵng.';
        }
    }

    // 3. Kiểm tra xem email đã có ai dùng chưa
    if (empty($errors)) {
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        $res_check = mysqli_stmt_get_result($stmt_check);
        if (mysqli_fetch_assoc($res_check)) {
            $errors[] = 'Email này đã được sử dụng. Vui lòng chọn email khác hoặc đăng nhập.';
        }
        mysqli_stmt_close($stmt_check);
    }

    // 4. Nếu không có lỗi, tiến hành lưu vào CSDL (bằng Transaction)
    if (empty($errors)) {
        mysqli_begin_transaction($conn);

        $password_hashed = password_hash($password, PASSWORD_DEFAULT);
        $balance = 0.00; // Sinh viên mới hoặc quán mới có số dư ban đầu là 0 đ

        $stmt_user = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, balance, warning_count, is_locked, created_at) VALUES (?, ?, ?, ?, ?, 0, 0, NOW())");
        mysqli_stmt_bind_param($stmt_user, "ssssd", $name, $email, $password_hashed, $role, $balance);
        $ok_user = mysqli_stmt_execute($stmt_user);
        $new_user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_user);

        $ok_shop = true;
        // Nếu là chủ quán, tạo thêm hồ sơ quán ở trạng thái 'cho_duyet'
        if ($ok_user && $role === 'shop_owner') {
            $shop_image = null;
            if (isset($_FILES['shop_image']) && $_FILES['shop_image']['error'] === UPLOAD_ERR_OK) {
                $upload_res = upload_image_file($_FILES['shop_image'], UPLOAD_DIR);
                if ($upload_res['success']) {
                    $shop_image = $upload_res['filename'];
                }
            }

            $stmt_s = mysqli_prepare($conn, "INSERT INTO shops (owner_id, name, address, district, type, image, violation_count, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, 'cho_duyet', NOW())");
            mysqli_stmt_bind_param($stmt_s, "isssss", $new_user_id, $shop_name, $shop_address, $shop_district, $shop_type, $shop_image);
            $ok_shop = mysqli_stmt_execute($stmt_s);
            mysqli_stmt_close($stmt_s);
        }

        if ($ok_user && $ok_shop) {
            mysqli_commit($conn);

            // Thêm thông báo chào mừng
            if ($role === 'shop_owner') {
                add_notification($conn, $new_user_id, "Chào mừng bạn! Quán '" . $shop_name . "' đang ở trạng thái chờ Admin duyệt trước khi có thể đăng bán túi.");
            } else {
                add_notification($conn, $new_user_id, "Chào mừng bạn đến với Giải Cứu Thực Phẩm! Hãy nạp tiền demo vào ví để bắt đầu giải cứu các túi thức ăn ngon.");
            }

            header("Location: " . BASE_URL . "dang_nhap.php?registered=1");
            exit;
        } else {
            mysqli_rollback($conn);
            $errors[] = 'Đăng ký thất bại do lỗi hệ thống, vui lòng thử lại.';
        }
    }
}

$page_title = 'Đăng ký tài khoản';
require_once __DIR__ . '/header.php';
?>

<div class="auth-wrapper" style="max-width: 620px;">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo-badge">🌱</div>
            <h1 style="font-size: 24px; font-weight: 900; color: var(--text-dark); letter-spacing: -0.5px;">ĐĂNG KÝ TÀI KHOẢN MỚI</h1>
            <p style="font-size: 13.5px; color: var(--text-muted); margin-top: 4px;">Tham gia mạng lưới Giải Cứu Thực Phẩm Đà Nẵng</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin-left: 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>dang_ky.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Chọn vai trò của bạn:</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 6px;">
                    <label style="cursor: pointer; background: #f8fafc; border: 2px solid #e2e8f0; padding: 14px; border-radius: 14px; display: flex; align-items: center; gap: 10px; transition: border-color 0.2s;">
                        <input type="radio" name="role" value="student" <?php echo (!isset($_POST['role']) || $_POST['role'] === 'student') ? 'checked' : ''; ?>>
                        <div>
                            <strong style="color: var(--text-dark); font-size: 14px; display: block;">🎓 Sinh viên</strong>
                            <small style="color: var(--text-muted); font-size: 11.5px;">Săn túi giá giảm 30-50%</small>
                        </div>
                    </label>

                    <label style="cursor: pointer; background: #f8fafc; border: 2px solid #e2e8f0; padding: 14px; border-radius: 14px; display: flex; align-items: center; gap: 10px; transition: border-color 0.2s;">
                        <input type="radio" name="role" value="shop_owner" <?php echo (isset($_POST['role']) && $_POST['role'] === 'shop_owner') ? 'checked' : ''; ?>>
                        <div>
                            <strong style="color: var(--text-dark); font-size: 14px; display: block;">🏪 Chủ quán</strong>
                            <small style="color: var(--text-muted); font-size: 11.5px;">Bán túi cứu đồ cuối ngày</small>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">👤 Họ và tên của bạn:</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required placeholder="Ví dụ: Nguyễn Văn An">
            </div>

            <div class="form-group">
                <label class="form-label">✉️ Địa chỉ Email:</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="email@gmail.com">
            </div>

            <div class="form-row">
                <div class="form-col form-group">
                    <label class="form-label">🔒 Mật khẩu:</label>
                    <input type="password" name="password" class="form-control" required placeholder="Tối thiểu 6 ký tự">
                </div>
                <div class="form-col form-group">
                    <label class="form-label">🔒 Xác nhận mật khẩu:</label>
                    <input type="password" name="password_confirm" class="form-control" required placeholder="Nhập lại mật khẩu">
                </div>
            </div>

            <!-- Khối thông tin bổ sung nếu đăng ký là Chủ quán -->
            <div id="shop_fields" style="background: #f0fdf4; border: 1.5px dashed #86efac; padding: 20px; border-radius: 18px; margin-bottom: 22px;">
                <h4 style="color: #047857; margin-bottom: 6px; font-weight: 800; font-size: 15.5px;">🏪 Thông tin Quán ăn / Tiệm bánh tại Đà Nẵng:</h4>
                <p class="form-text" style="margin-bottom: 14px;">(Bắt buộc nếu bạn chọn vai trò Chủ quán để hệ thống duyệt)</p>
                
                <div class="form-group">
                    <label class="form-label">Tên cửa hàng / thương hiệu:</label>
                    <input type="text" name="shop_name" class="form-control" value="<?php echo htmlspecialchars($_POST['shop_name'] ?? ''); ?>" placeholder="Ví dụ: Tiệm Bánh BonBon Bakery">
                </div>

                <div class="form-row">
                    <div class="form-col form-group">
                        <label class="form-label">Quận tại Đà Nẵng:</label>
                        <select name="shop_district" class="form-control">
                            <?php
                            $districts = ['Hải Châu', 'Thanh Khê', 'Sơn Trà', 'Ngũ Hành Sơn', 'Liên Chiểu', 'Cẩm Lệ', 'Hòa Vang'];
                            foreach ($districts as $d) {
                                $sel = (isset($_POST['shop_district']) && $_POST['shop_district'] === $d) ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($d) . "\" $sel>" . htmlspecialchars($d) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-col form-group">
                        <label class="form-label">Loại quán:</label>
                        <select name="shop_type" class="form-control">
                            <?php
                            $types = ['Bánh ngọt & Bánh mì', 'Cơm & Bún phở', 'Trà sữa & Cafe', 'Thức ăn nhanh', 'Đồ chay', 'Khác'];
                            foreach ($types as $t) {
                                $sel = (isset($_POST['shop_type']) && $_POST['shop_type'] === $t) ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($t) . "\" $sel>" . htmlspecialchars($t) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Địa chỉ cụ thể:</label>
                    <input type="text" name="shop_address" class="form-control" value="<?php echo htmlspecialchars($_POST['shop_address'] ?? ''); ?>" placeholder="Ví dụ: 123 Lê Duẩn, P. Thạch Thang">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Ảnh đại diện quán (tùy chọn, JPG/PNG):</label>
                    <input type="file" name="shop_image" class="form-control" accept="image/png, image/jpeg, image/webp">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 6px;">
                ĐĂNG KÝ TÀI KHOẢN NGAY &rarr;
            </button>
        </form>

        <p style="text-align: center; margin-top: 22px; font-size: 14px; color: var(--text-muted);">
            Đã có tài khoản? <a href="<?php echo BASE_URL; ?>dang_nhap.php" style="color: var(--secondary-color); font-weight: 800; text-decoration: none;">Đăng nhập tại đây &rarr;</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
