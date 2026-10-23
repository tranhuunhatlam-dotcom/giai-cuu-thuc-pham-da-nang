<?php
// FILE: shop/doanh_thu.php
// Mục đích: Thống kê doanh thu quán: Tiền đang tạm giữ / Đã nhận vào ví / Đang tranh chấp và lịch sử ví tiền

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('shop_owner');
$user = get_logged_in_user($conn);
$shop = get_shop_by_owner_id($conn, $user['id']);

if (!$shop) {
    die("Lỗi: Không tìm thấy quán.");
}

// 1. Tiền đang tạm giữ (status = 'da_nhan')
$sql_holding = "SELECT COALESCE(SUM(price), 0) AS total FROM orders WHERE shop_id = ? AND status = 'da_nhan'";
$stmt1 = mysqli_prepare($conn, $sql_holding);
mysqli_stmt_bind_param($stmt1, "i", $shop['id']);
mysqli_stmt_execute($stmt1);
$total_holding = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt1))['total'];
mysqli_stmt_close($stmt1);

// 2. Tiền đã nhận hoàn tất (status IN ('hoan_thanh', 'huy_vang_mat', 'chuyen_tien_quan'))
$sql_received = "SELECT COALESCE(SUM(price), 0) AS total FROM orders WHERE shop_id = ? AND status IN ('hoan_thanh', 'huy_vang_mat', 'chuyen_tien_quan')";
$stmt2 = mysqli_prepare($conn, $sql_received);
mysqli_stmt_bind_param($stmt2, "i", $shop['id']);
mysqli_stmt_execute($stmt2);
$total_received = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2))['total'];
mysqli_stmt_close($stmt2);

// 3. Tiền đang tranh chấp (status = 'tranh_chap')
$sql_dispute = "SELECT COALESCE(SUM(price), 0) AS total FROM orders WHERE shop_id = ? AND status = 'tranh_chap'";
$stmt3 = mysqli_prepare($conn, $sql_dispute);
mysqli_stmt_bind_param($stmt3, "i", $shop['id']);
mysqli_stmt_execute($stmt3);
$total_dispute = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt3))['total'];
mysqli_stmt_close($stmt3);

// 4. Lịch sử ví tiền của chủ quán (wallet_logs)
$sql_logs = "SELECT * FROM wallet_logs WHERE user_id = ? ORDER BY id DESC";
$stmt_l = mysqli_prepare($conn, $sql_logs);
mysqli_stmt_bind_param($stmt_l, "i", $user['id']);
mysqli_stmt_execute($stmt_l);
$res_l = mysqli_stmt_get_result($stmt_l);
$logs = [];
while ($row = mysqli_fetch_assoc($res_l)) {
    $logs[] = $row;
}
mysqli_stmt_close($stmt_l);

$page_title = 'Báo cáo doanh thu quán';
require_once __DIR__ . '/../header.php';
?>

<div class="card">
    <h2 class="card-title">Báo Cáo Doanh Thu (Quán: <?php echo htmlspecialchars($shop['name']); ?>)</h2>

    <!-- Lưới 3 khối thống kê tiền -->
    <div class="stats-grid">
        <div class="stat-card" style="border-top: 4px solid #0277bd;">
            <div style="font-size: 14px; color: var(--text-muted);">1. Tiền đang tạm giữ (Escrow):</div>
            <div class="stat-value" style="color: #0277bd;"><?php echo format_money($total_holding); ?></div>
            <small style="color: var(--text-muted);">Đơn khách đã nhận, đang trong 45 phút giữ tiền</small>
        </div>

        <div class="stat-card" style="border-top: 4px solid #2e7d32;">
            <div style="font-size: 14px; color: var(--text-muted);">2. Tiền đã nhận vào ví:</div>
            <div class="stat-value" style="color: #2e7d32;"><?php echo format_money($total_received); ?></div>
            <small style="color: #2e7d32; font-weight: 600;">Số dư ví hiện tại: <?php echo format_money($user['balance']); ?></small>
        </div>

        <div class="stat-card" style="border-top: 4px solid #c62828;">
            <div style="font-size: 14px; color: var(--text-muted);">3. Tiền đang tranh chấp:</div>
            <div class="stat-value red"><?php echo format_money($total_dispute); ?></div>
            <small style="color: var(--text-muted);">Đơn đang có khiếu nại chờ Admin giải quyết</small>
        </div>
    </div>

    <!-- Lịch sử ví tiền -->
    <h3 style="color: var(--primary-color); margin: 24px 0 12px 0;">Lịch Sử Cộng / Trừ Ví Tiền Của Quán</h3>
    <?php if (empty($logs)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 20px 0;">Quán chưa có giao dịch cộng tiền nào phát sinh.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Số tiền</th>
                        <th>Nội dung</th>
                        <th>Đơn hàng</th>
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
                                    <strong style="color: #2e7d32;">+<?php echo format_money($l['amount']); ?></strong>
                                <?php else: ?>
                                    <strong style="color: #c62828;"><?php echo format_money($l['amount']); ?></strong>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($l['reason']); ?></td>
                            <td>
                                <?php if ($l['order_id']): ?>
                                    <strong>#<?php echo $l['order_id']; ?></strong>
                                <?php else: ?>
                                    ---
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
