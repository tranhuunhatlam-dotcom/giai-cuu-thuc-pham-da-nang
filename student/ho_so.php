<?php
// FILE: student/ho_so.php
// Mục đích: Hồ sơ cá nhân sinh viên, thẻ sinh viên điện tử, cấp bậc danh hiệu Sống Xanh và cập nhật thông tin

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('student');
$user = get_logged_in_user($conn);

$notice = '';
$error = '';

// Xử lý cập nhật thông tin cá nhân hoặc đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $university = trim($_POST['university'] ?? '');

        if (empty($name)) {
            $error = 'Họ và tên không được để trống.';
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, university = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $name, $university, $user['id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $_SESSION['name'] = $name;
            $notice = 'Cập nhật thông tin hồ sơ thành công!';
            $user = get_logged_in_user($conn);
        }
    } elseif (isset($_POST['action_change_password'])) {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (!password_verify($old_pass, $user['password'])) {
            $error = 'Mật khẩu hiện tại không chính xác.';
        } elseif (strlen($new_pass) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } elseif ($new_pass !== $confirm_pass) {
            $error = 'Xác nhận mật khẩu mới không khớp.';
        } else {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $hash, $user['id']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $notice = 'Đổi mật khẩu thành công! Hãy ghi nhớ mật khẩu mới.';
            $user = get_logged_in_user($conn);
        }
    }
}

// Lấy thống kê cá nhân của sinh viên
$eco_stats = get_user_eco_stats($conn, $user['id']);

$all_universities = [
    'Đại học CNTT&TT Việt - Hàn (VKU Đà Nẵng)',
    'Đại học Bách Khoa - ĐH Đà Nẵng',
    'Đại học Kinh Tế - ĐH Đà Nẵng',
    'Đại học Sư Phạm - ĐH Đà Nẵng',
    'Đại học Ngoại Ngữ - ĐH Đà Nẵng',
    'Đại học Duy Tân Đà Nẵng',
    'Đại học FPT Đà Nẵng',
    'Đại học Kỹ Thuật Y - Dược Đà Nẵng',
    'Trường Đại học khác tại Đà Nẵng'
];

$page_title = 'Hồ sơ cá nhân & Thẻ sinh viên';
require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 860px; margin: 24px auto;">
    <?php if (!empty($notice)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- THẺ SINH VIÊN ĐIỆN TỬ (DIGITAL STUDENT CARD) -->
    <div style="background: linear-gradient(135deg, #064e3b 0%, #059669 45%, #10b981 100%); color: white; border-radius: 24px; padding: 32px 36px; margin-bottom: 26px; box-shadow: 0 16px 36px -6px rgba(5, 150, 105, 0.35); position: relative; overflow: hidden;">
        <div style="position: absolute; right: -15px; bottom: -20px; font-size: 140px; opacity: 0.1; pointer-events: none;">🎓</div>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <span class="badge" style="background: rgba(255,255,255,0.22); color: white; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 800;">
                    THẺ SINH VIÊN SỐNG XANH ĐÀ NẴNG
                </span>
                <h2 style="font-size: 26px; font-weight: 900; margin-top: 8px; letter-spacing: -0.4px;">
                    <?php echo htmlspecialchars($user['name']); ?>
                </h2>
                <div style="font-size: 14px; opacity: 0.9; margin-top: 2px;">
                    🏛️ <?php echo htmlspecialchars($user['university'] ?? 'ĐH CNTT&TT Việt - Hàn (VKU)'); ?>
                </div>
            </div>

            <!-- Cấp bậc danh hiệu -->
            <div style="text-align: right;">
                <span class="badge" style="background: #fde047; color: #713f12; font-size: 13px; font-weight: 900; padding: 6px 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <?php echo $eco_stats['rank']; ?>
                </span>
                <div style="font-size: 12px; margin-top: 6px; opacity: 0.85;">
                    Mã SV: <strong>VKU-<?php echo str_pad($user['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                </div>
            </div>
        </div>

        <!-- 3 Chỉ số cá nhân -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; border-top: 1px solid rgba(255,255,255,0.18); padding-top: 18px;">
            <div>
                <div style="font-size: 12px; text-transform: uppercase; opacity: 0.8;">Túi đã giải cứu</div>
                <div style="font-size: 24px; font-weight: 900;"><?php echo $eco_stats['count']; ?> túi</div>
            </div>
            <div>
                <div style="font-size: 12px; text-transform: uppercase; opacity: 0.8;">Tiết kiệm tích lũy</div>
                <div style="font-size: 24px; font-weight: 900;"><?php echo format_money($eco_stats['saved']); ?></div>
            </div>
            <div>
                <div style="font-size: 12px; text-transform: uppercase; opacity: 0.8;">CO2 giảm phát thải</div>
                <div style="font-size: 24px; font-weight: 900;"><?php echo $eco_stats['eco']['co2_kg']; ?> kg</div>
            </div>
        </div>
    </div>

    <div class="form-row">
        <!-- Cột 1: Cập nhật thông tin cá nhân -->
        <div class="form-col" style="min-width: 320px;">
            <div class="card" style="border-radius: 20px;">
                <h3 class="card-title" style="font-size: 18px;">👤 Thông Tin Cá Nhân</h3>
                
                <form action="<?php echo BASE_URL; ?>student/ho_so.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Họ và tên:</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Địa chỉ Email (Cố định):</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: #f1f5f9;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Trường Đại học đang theo học:</label>
                        <select name="university" class="form-control">
                            <?php foreach ($all_universities as $u): ?>
                                <option value="<?php echo htmlspecialchars($u); ?>" <?php echo (($user['university'] ?? '') === $u) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="action_update_profile" value="1" class="btn btn-primary btn-block">
                        Lưu Thay Đổi Hồ Sơ
                    </button>
                </form>
            </div>
        </div>

        <!-- Cột 2: Đổi mật khẩu tài khoản -->
        <div class="form-col" style="min-width: 320px;">
            <div class="card" style="border-radius: 20px;">
                <h3 class="card-title" style="font-size: 18px;">🔒 Đổi Mật Khẩu</h3>
                
                <form action="<?php echo BASE_URL; ?>student/ho_so.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Mật khẩu hiện tại:</label>
                        <input type="password" name="old_password" class="form-control" required placeholder="Nhập mật khẩu cũ...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mật khẩu mới:</label>
                        <input type="password" name="new_password" class="form-control" required placeholder="Tối thiểu 6 ký tự...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Xác nhận mật khẩu mới:</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="Nhập lại mật khẩu mới...">
                    </div>

                    <button type="submit" name="action_change_password" value="1" class="btn btn-outline btn-block">
                        Cập Nhật Mật Khẩu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
