<?php
// FILE: admin/cai_dat.php
// Mục đích: Cấu hình các tham số hệ thống trong bảng settings

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('admin');

$notice = '';
$error = '';

// Xử lý lưu cấu hình
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_save_settings'])) {
        $settings_to_update = [
            'discount_min' => $_POST['discount_min'] ?? '30',
            'discount_max' => $_POST['discount_max'] ?? '50',
            'cancel_before_close_minutes' => $_POST['cancel_before_close_minutes'] ?? '90',
            'hold_minutes' => $_POST['hold_minutes'] ?? '45',
            'dispute_response_hours' => $_POST['dispute_response_hours'] ?? '48',
            'max_bags_per_user' => $_POST['max_bags_per_user'] ?? '2',
            'max_disputes_per_month' => $_POST['max_disputes_per_month'] ?? '3',
            'gemini_api_key' => $_POST['gemini_api_key'] ?? '',
            'ai_assistant_mode' => $_POST['ai_assistant_mode'] ?? 'hybrid'
        ];

        foreach ($settings_to_update as $name => $val) {
            $val = trim($val);
            $stmt = mysqli_prepare($conn, "UPDATE settings SET value = ? WHERE name = ?");
            mysqli_stmt_bind_param($stmt, "ss", $val, $name);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        $notice = 'Cập nhật cấu hình hệ thống thành công!';
    } elseif (isset($_POST['action_quick_demo'])) {
        // Cấu hình chế độ DEMO NHANH: Giữ tiền 1 phút, hủy đơn 2 phút trước giờ đóng
        $demo_settings = [
            'hold_minutes' => '1',
            'cancel_before_close_minutes' => '2'
        ];
        foreach ($demo_settings as $name => $val) {
            $stmt = mysqli_prepare($conn, "UPDATE settings SET value = ? WHERE name = ?");
            mysqli_stmt_bind_param($stmt, "ss", $val, $name);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        $notice = 'ĐÃ BẬT CHẾ ĐỘ DEMO NHANH: Thời gian giữ tiền giảm xuống 1 phút, hạn hủy đơn giảm xuống 2 phút để chấm bài nhanh!';
    }
}

// Lấy danh sách settings hiện tại
$current_settings = [];
$res = mysqli_query($conn, "SELECT * FROM settings");
while ($r = mysqli_fetch_assoc($res)) {
    $current_settings[$r['name']] = $r['value'];
}

$page_title = 'Cài đặt hệ thống';
require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 750px; margin: 20px auto;">
    <div class="card">
        <div class="card-title">
            <span>CẤU HÌNH HỆ THỐNG (BẢNG SETTINGS)</span>
        </div>

        <?php if (!empty($notice)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>

        <!-- Hộp chuyển đổi nhanh sang chế độ Demo -->
        <div style="background: #fff3e0; border: 1px dashed #ff9800; padding: 14px; border-radius: 8px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <strong style="color: #e65100;">⚡ Phím tắt Demo Nhanh:</strong><br>
                    <small>Tự động chỉnh <code>hold_minutes = 1 phút</code> và <code>cancel_before_close_minutes = 2 phút</code> để giáo viên / người chấm kiểm thử ngay lập tức mà không cần đợi lâu.</small>
                </div>
                <form action="<?php echo BASE_URL; ?>admin/cai_dat.php" method="POST" style="margin: 0;">
                    <button type="submit" name="action_quick_demo" value="1" class="btn btn-secondary btn-sm" onclick="return confirm('Chuyển cấu hình sang chế độ Demo Nhanh (giữ tiền 1 phút, hủy 2 phút)?');">
                        Bật Demo Nhanh (1 - 2 Phút)
                    </button>
                </form>
            </div>
        </div>

        <form action="<?php echo BASE_URL; ?>admin/cai_dat.php" method="POST">
            <h4 style="color: var(--primary-color); margin-bottom: 12px;">1. Quy định Giảm Giá Túi Bất Ngờ (%):</h4>
            <div class="form-row">
                <div class="form-col form-group">
                    <label class="form-label">Giảm giá tối thiểu (discount_min %):</label>
                    <input type="number" name="discount_min" class="form-control" value="<?php echo htmlspecialchars($current_settings['discount_min'] ?? '30'); ?>" required>
                    <small class="form-text">Mặc định: 30%</small>
                </div>
                <div class="form-col form-group">
                    <label class="form-label">Giảm giá tối đa (discount_max %):</label>
                    <input type="number" name="discount_max" class="form-control" value="<?php echo htmlspecialchars($current_settings['discount_max'] ?? '50'); ?>" required>
                    <small class="form-text">Mặc định: 50%</small>
                </div>
            </div>

            <h4 style="color: var(--primary-color); margin-bottom: 12px; margin-top: 10px;">2. Quy định Thời Gian & Đơn Hàng:</h4>
            <div class="form-row">
                <div class="form-col form-group">
                    <label class="form-label">Hạn hủy đơn trước giờ đóng cửa (phút):</label>
                    <input type="number" name="cancel_before_close_minutes" class="form-control" value="<?php echo htmlspecialchars($current_settings['cancel_before_close_minutes'] ?? '90'); ?>" required>
                    <small class="form-text">Mặc định: 90 phút (cancel_before_close_minutes)</small>
                </div>
                <div class="form-col form-group">
                    <label class="form-label">Thời gian giữ tiền sau nhận túi (phút):</label>
                    <input type="number" name="hold_minutes" class="form-control" value="<?php echo htmlspecialchars($current_settings['hold_minutes'] ?? '45'); ?>" required>
                    <small class="form-text">Mặc định: 45 phút (hold_minutes)</small>
                </div>
            </div>

            <h4 style="color: var(--primary-color); margin-bottom: 12px; margin-top: 10px;">3. Khiếu Nại & Giới Hạn Mua:</h4>
            <div class="form-row">
                <div class="form-col form-group">
                    <label class="form-label">Thời hạn quán phản hồi khiếu nại (giờ):</label>
                    <input type="number" name="dispute_response_hours" class="form-control" value="<?php echo htmlspecialchars($current_settings['dispute_response_hours'] ?? '48'); ?>" required>
                    <small class="form-text">Mặc định: 48 giờ (dispute_response_hours)</small>
                </div>
                <div class="form-col form-group">
                    <label class="form-label">Số túi tối đa 1 người mua trong ngày:</label>
                    <input type="number" name="max_bags_per_user" class="form-control" value="<?php echo htmlspecialchars($current_settings['max_bags_per_user'] ?? '2'); ?>" required>
                    <small class="form-text">Mặc định: 2 túi (max_bags_per_user)</small>
                </div>
                <div class="form-col form-group">
                    <label class="form-label">Số lần khiếu nại tối đa / tháng của 1 SV:</label>
                    <input type="number" name="max_disputes_per_month" class="form-control" value="<?php echo htmlspecialchars($current_settings['max_disputes_per_month'] ?? '3'); ?>" required>
                    <small class="form-text">Mặc định: 3 lần (max_disputes_per_month)</small>
                </div>
            </div>

            <h4 style="color: #6366f1; margin-bottom: 12px; margin-top: 15px;">4. Cấu Hình Trí Tuệ Nhân Tạo (Hybrid AI Engine):</h4>
            <div style="background: #eef2ff; border: 1.5px solid #c7d2fe; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                <div class="form-row">
                    <div class="form-col form-group" style="flex: 2;">
                        <label class="form-label" style="font-weight: 700; color: #3730a3;">Google Gemini API Key (Tùy chọn):</label>
                        <input type="text" name="gemini_api_key" class="form-control" value="<?php echo htmlspecialchars($current_settings['gemini_api_key'] ?? ''); ?>" placeholder="AIzaSy...">
                        <small class="form-text">Khóa API Google Gemini 1.5 Flash. <em>Nếu để trống, hệ thống sẽ tự động chuyển sang Bộ máy Heuristic AI Nội Bộ không bao giờ lỗi khi chấm bài!</em></small>
                    </div>
                    <div class="form-col form-group" style="flex: 1;">
                        <label class="form-label" style="font-weight: 700; color: #3730a3;">Chế độ hoạt động AI:</label>
                        <select name="ai_assistant_mode" class="form-control">
                            <option value="hybrid" <?php echo ($current_settings['ai_assistant_mode'] ?? 'hybrid') === 'hybrid' ? 'selected' : ''; ?>>Lai (Hybrid - Tự động linh hoạt)</option>
                            <option value="local" <?php echo ($current_settings['ai_assistant_mode'] ?? '') === 'local' ? 'selected' : ''; ?>>Nội bộ thuần túy (Không dùng internet)</option>
                        </select>
                        <small class="form-text">Bảo vệ đồ án vận hành offline 100%.</small>
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" name="action_save_settings" value="1" class="btn btn-primary" style="padding: 12px 24px;">
                    LƯU CẤU HÌNH HỆ THỐNG
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
