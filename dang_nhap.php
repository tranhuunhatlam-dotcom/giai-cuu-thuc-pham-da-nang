<?php
// FILE: dang_nhap.php
// Mục đích: Xác thực thông tin đăng nhập, kiểm tra tài khoản bị khóa và điều hướng theo vai trò

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$error = '';
$notice = '';

if (isset($_GET['registered'])) {
    $notice = 'Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay bây giờ.';
}
if (isset($_GET['logout'])) {
    $notice = 'Bạn đã đăng xuất khỏi hệ thống an toàn.';
}
if (isset($_GET['error']) && $_GET['error'] === 'locked') {
    $error = 'Tài khoản của bạn đã bị khóa do vi phạm hoặc tích lũy đủ 3 lần cảnh cáo!';
}
if (isset($_GET['error']) && $_GET['error'] === 'need_login') {
    $error = 'Vui lòng đăng nhập để tiếp tục thực hiện thao tác này.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ Email và Mật khẩu.';
    } else {
        // Truy vấn thông tin người dùng theo email
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if ($user && password_verify($password, $user['password'])) {
                // Kiểm tra xem tài khoản có bị khóa không
                if ($user['is_locked'] == 1) {
                    $error = 'Tài khoản của bạn đã bị khóa bởi Quản trị viên (Cảnh cáo: ' . $user['warning_count'] . '). Vui lòng liên hệ Admin để được hỗ trợ.';
                } else {
                    // Đăng nhập thành công, lưu thông tin vào Session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];

                    // Điều hướng tương ứng theo vai trò
                    if ($user['role'] === 'admin') {
                        header("Location: " . BASE_URL . "admin/thong_ke.php");
                    } elseif ($user['role'] === 'shop_owner') {
                        header("Location: " . BASE_URL . "shop/danh_sach_tui.php");
                    } else {
                        header("Location: " . BASE_URL . "index.php");
                    }
                    exit;
                }
            } else {
                $error = 'Email hoặc mật khẩu không chính xác. Vui lòng kiểm tra lại.';
            }
        } else {
            $error = 'Lỗi truy vấn cơ sở dữ liệu.';
        }
    }
}

$page_title = 'Đăng nhập';
require_once __DIR__ . '/header.php';
?>

<?php
// Pre-fill email nếu click vào nút demo
$fill_email = trim($_GET['fill'] ?? '');
if (empty($_POST['email']) && !empty($fill_email)) {
    $_POST['email'] = $fill_email;
}
?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo-badge">🥗</div>
            <h1 style="font-size: 24px; font-weight: 900; color: var(--text-dark); letter-spacing: -0.5px;">ĐĂNG NHẬP HỆ THỐNG</h1>
            <p style="font-size: 13.5px; color: var(--text-muted); margin-top: 4px;">Nền tảng Giải Cứu Thực Phẩm Đà Nẵng</p>
        </div>

        <?php if (!empty($notice)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>dang_nhap.php" method="POST">
            <div class="form-group">
                <label class="form-label">✉️ Địa chỉ Email:</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="Ví dụ: sv1@giaicuu.vn...">
            </div>

            <div class="form-group">
                <label class="form-label">🔒 Mật khẩu:</label>
                <input type="password" name="password" class="form-control" required placeholder="Nhập mật khẩu của bạn...">
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
                ĐĂNG NHẬP NGAY &rarr;
            </button>
        </form>

        <p style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--text-muted);">
            Chưa có tài khoản? <a href="<?php echo BASE_URL; ?>dang_ky.php" style="color: var(--secondary-color); font-weight: 800; text-decoration: none;">Đăng ký tài khoản mới &rarr;</a>
        </p>

        <hr style="margin: 24px 0; border: none; border-top: 1px dashed var(--border-color);">
        
        <!-- Bảng tài khoản Demo nhanh cho Thầy giáo & Người chấm thi -->
        <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 16px; border-radius: 16px; font-size: 13px;">
            <strong style="color: var(--text-dark); display: block; margin-bottom: 8px;">🔑 Chọn nhanh tài khoản Demo để kiểm thử:</strong>
            
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 8px 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <div>
                        <span class="badge badge-success" style="font-size: 11px;">Sinh viên</span>
                        <code style="font-size: 12px; color: #047857; margin-left: 4px;">sv1@giaicuu.vn</code> / <code>sv123</code>
                    </div>
                    <a href="<?php echo BASE_URL; ?>dang_nhap.php?fill=sv1@giaicuu.vn" class="btn btn-outline btn-sm" style="padding: 3px 8px; font-size: 11px;">Điền email</a>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 8px 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <div>
                        <span class="badge badge-warning" style="font-size: 11px;">Chủ quán</span>
                        <code style="font-size: 12px; color: #b45309; margin-left: 4px;">tiembanh@giaicuu.vn</code> / <code>shop123</code>
                    </div>
                    <a href="<?php echo BASE_URL; ?>dang_nhap.php?fill=tiembanh@giaicuu.vn" class="btn btn-outline btn-sm" style="padding: 3px 8px; font-size: 11px;">Điền email</a>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 8px 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <div>
                        <span class="badge badge-danger" style="font-size: 11px;">Quản trị Admin</span>
                        <code style="font-size: 12px; color: #b91c1c; margin-left: 4px;">admin@giaicuu.vn</code> / <code>admin123</code>
                    </div>
                    <a href="<?php echo BASE_URL; ?>dang_nhap.php?fill=admin@giaicuu.vn" class="btn btn-outline btn-sm" style="padding: 3px 8px; font-size: 11px;">Điền email</a>
                </div>
            </div>

            <div style="text-align: center; margin-top: 10px;">
                <a href="<?php echo BASE_URL; ?>tao_du_lieu_mau.php" style="color: var(--primary-color); font-weight: 700; text-decoration: none; font-size: 12px;">
                    🌱 Khởi tạo lại toàn bộ dữ liệu mẫu ban đầu &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
