<?php
// FILE: student/vi_tien.php
// Mục đích: Xem số dư ví tiền ảo, nạp tiền demo và xem toàn bộ lịch sử biến động số dư (wallet_logs)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('student');
$user = get_logged_in_user($conn);

$notice = '';
$error = '';

// Xử lý nạp tiền demo vào ví
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_deposit'])) {
    $amount = (float)($_POST['amount'] ?? 0);
    if ($amount <= 0) {
        $error = 'Vui lòng chọn hoặc nhập số tiền nạp lớn hơn 0 đ.';
    } elseif ($amount > 5000000) {
        $error = 'Mỗi lần nạp tối đa 5.000.000 đ.';
    } else {
        // Thực hiện nạp tiền trong Transaction
        mysqli_begin_transaction($conn);

        // Cộng tiền vào tài khoản người dùng
        $stmt_bal = mysqli_prepare($conn, "UPDATE users SET balance = balance + ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_bal, "di", $amount, $user['id']);
        mysqli_stmt_execute($stmt_bal);
        $aff = mysqli_stmt_affected_rows($stmt_bal);
        mysqli_stmt_close($stmt_bal);

        if ($aff > 0) {
            // Ghi nhật ký ví tiền
            $reason = "Nạp tiền demo vào ví sinh viên";
            $stmt_log = mysqli_prepare($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES (?, ?, ?, NULL, NOW())");
            mysqli_stmt_bind_param($stmt_log, "ids", $user['id'], $amount, $reason);
            mysqli_stmt_execute($stmt_log);
            mysqli_stmt_close($stmt_log);

            mysqli_commit($conn);
            $notice = 'Nạp thành công ' . format_money($amount) . ' vào ví!';
            $user = get_logged_in_user($conn); // Cập nhật số dư mới nhất
        } else {
            mysqli_rollback($conn);
            $error = 'Nạp tiền thất bại do lỗi hệ thống.';
        }
    }
}

// Lấy lịch sử biến động số dư (wallet_logs)
$sql_logs = "SELECT * FROM wallet_logs WHERE user_id = ? ORDER BY id DESC";
$stmt_logs = mysqli_prepare($conn, $sql_logs);
mysqli_stmt_bind_param($stmt_logs, "i", $user['id']);
mysqli_stmt_execute($stmt_logs);
$res_logs = mysqli_stmt_get_result($stmt_logs);
$logs = [];
while ($row = mysqli_fetch_assoc($res_logs)) {
    $logs[] = $row;
}
mysqli_stmt_close($stmt_logs);

$page_title = 'Ví tiền ảo';
require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 860px; margin: 24px auto;">
    <!-- Khối thẻ số dư kiểu Digital Card sang trọng -->
    <div style="background: linear-gradient(135deg, #064e3b 0%, #059669 50%, #10b981 100%); color: white; border-radius: 24px; padding: 32px 36px; margin-bottom: 26px; box-shadow: 0 16px 36px -6px rgba(5, 150, 105, 0.35); position: relative; overflow: hidden;">
        <div style="position: absolute; right: -20px; bottom: -30px; font-size: 160px; opacity: 0.08; pointer-events: none;">💳</div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 22px;">🥗</span>
                <span style="font-weight: 800; font-size: 14px; letter-spacing: 1px; opacity: 0.9;">VÍ SINH VIÊN ĐÀ NẴNG</span>
            </div>
            <span class="badge" style="background: rgba(255,255,255,0.22); color: white; font-size: 12px; padding: 4px 12px; border-radius: 20px;">
                ⚡ Sẵn sàng thanh toán
            </span>
        </div>

        <div style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.85;">Số dư khả dụng hiện tại:</div>
        <div style="font-size: 44px; font-weight: 900; margin: 6px 0 16px 0; letter-spacing: -1px; text-shadow: 0 2px 10px rgba(0,0,0,0.15);">
            <?php echo format_money($user['balance']); ?>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; font-size: 13px; opacity: 0.9; border-top: 1px solid rgba(255,255,255,0.18); padding-top: 14px;">
            <div>Chủ ví: <strong><?php echo htmlspecialchars($user['name']); ?></strong> (<?php echo htmlspecialchars($user['email']); ?>)</div>
            <div style="font-family: monospace; letter-spacing: 2px;">•••• •••• •••• <?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></div>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Khối nạp tiền demo -->
    <div class="card">
        <h3 class="card-title">
            <span>⚡ Nạp Tiền Demo Vào Ví</span>
            <span class="badge badge-info" style="font-size: 12px;">Mô phỏng 100%</span>
        </h3>
        <p class="form-text" style="margin-bottom: 18px; font-size: 13.5px;">
            Hệ thống sử dụng tiền ảo mô phỏng để giao dịch, không kết nối cổng thanh toán thật. Bạn có thể bấm chọn nhanh số tiền bên dưới để thử nghiệm đặt túi bất ngờ:
        </p>

        <form action="<?php echo BASE_URL; ?>student/vi_tien.php" method="POST">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px;">
                <button type="submit" name="amount" value="50000" class="btn btn-outline" style="border-color: #a7f3d0; color: #047857; font-weight: 800; padding: 12px;">+ 50.000 đ</button>
                <button type="submit" name="amount" value="100000" class="btn btn-outline" style="border-color: #a7f3d0; color: #047857; font-weight: 800; padding: 12px;">+ 100.000 đ</button>
                <button type="submit" name="amount" value="200000" class="btn btn-outline" style="border-color: #a7f3d0; color: #047857; font-weight: 800; padding: 12px;">+ 200.000 đ</button>
                <button type="submit" name="amount" value="500000" class="btn btn-outline" style="border-color: #a7f3d0; color: #047857; font-weight: 800; padding: 12px;">+ 500.000 đ</button>
            </div>

            <div class="form-row" style="align-items: flex-end;">
                <div class="form-col form-group" style="margin-bottom: 0;">
                    <label class="form-label">Hoặc nhập số tiền tùy ý (VNĐ):</label>
                    <input type="number" name="amount" class="form-control" placeholder="Ví dụ: 150000" min="10000" step="10000">
                </div>
                <div>
                    <button type="submit" name="action_deposit" value="1" class="btn btn-primary" style="padding: 12px 26px;">
                        Nạp Tiền Vào Ví &rarr;
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Khối lịch sử giao dịch wallet_logs -->
    <div class="card">
        <h3 class="card-title">
            <span>📋 Lịch Sử Biến Động Số Dư (Wallet Logs)</span>
            <span class="badge badge-gray" style="font-size: 12px;"><?php echo count($logs); ?> giao dịch</span>
        </h3>

        <?php if (empty($logs)): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 30px 0;">Chưa có giao dịch tiền nào phát sinh.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Số tiền</th>
                            <th>Lý do biến động</th>
                            <th>Mã đơn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td style="font-size: 13px; color: var(--text-muted); white-space: nowrap;">
                                    <?php echo format_datetime($l['created_at']); ?>
                                </td>
                                <td>
                                    <?php if ($l['amount'] > 0): ?>
                                        <span class="badge badge-success" style="font-size: 13px; font-weight: 800;">
                                            +<?php echo format_money($l['amount']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger" style="font-size: 13px; font-weight: 800;">
                                            <?php echo format_money($l['amount']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 13.5px; font-weight: 600; color: var(--text-dark);">
                                    <?php echo htmlspecialchars($l['reason']); ?>
                                </td>
                                <td>
                                    <?php if ($l['order_id']): ?>
                                        <a href="<?php echo BASE_URL; ?>student/don_chi_tiet.php?id=<?php echo $l['order_id']; ?>" class="badge badge-info" style="text-decoration: none;">
                                            Đơn #<?php echo $l['order_id']; ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">---</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
