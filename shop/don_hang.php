<?php
// FILE: shop/don_hang.php
// Mục đích: Quản lý toàn bộ danh sách đơn hàng của quán và xử lý khiếu nại nếu có

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('shop_owner');
$user = get_logged_in_user($conn);
$shop = get_shop_by_owner_id($conn, $user['id']);

if (!$shop) {
    die("Lỗi: Không tìm thấy quán.");
}

// Lấy danh sách đơn hàng của quán
$sql = "SELECT o.*, b.name AS bag_name, u.name AS student_name, u.email AS student_email,
               d.id AS dispute_id, d.status AS dispute_status
        FROM orders o
        JOIN bags b ON o.bag_id = b.id
        JOIN users u ON o.user_id = u.id
        LEFT JOIN disputes d ON o.id = d.order_id
        WHERE o.shop_id = ?
        ORDER BY o.id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $shop['id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$orders = [];
while ($row = mysqli_fetch_assoc($res)) {
    $orders[] = $row;
}
mysqli_stmt_close($stmt);

$page_title = 'Đơn hàng của quán';
require_once __DIR__ . '/../header.php';
?>

<div class="card">
    <div class="card-title">
        <span>Đơn Hàng Của Quán (<?php echo htmlspecialchars($shop['name']); ?>)</span>
        <a href="<?php echo BASE_URL; ?>shop/nhap_ma.php" class="btn btn-secondary btn-sm">Nhập Mã Nhận Hàng</a>
    </div>

    <?php if (empty($orders)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 30px 0;">
            Chưa có sinh viên nào đặt túi tại quán của bạn.
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Tên túi</th>
                        <th>Hình thức & Giao nhận</th>
                        <th>Sinh viên đặt</th>
                        <th>Số tiền</th>
                        <th>Mã nhận</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong>#<?php echo $o['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($o['bag_name']); ?></td>
                            <td style="font-size: 12.5px;">
                                <?php if (($o['delivery_type'] ?? 'pickup') === 'delivery'): ?>
                                    <span class="badge badge-success" style="font-size: 11px; margin-bottom: 4px; display: inline-block;">🛵 Giao tận nơi</span><br>
                                    <strong>Đ/c:</strong> <?php echo htmlspecialchars($o['shipping_address'] ?? ''); ?><br>
                                    <strong>SĐT:</strong> <a href="tel:<?php echo htmlspecialchars($o['shipping_phone'] ?? ''); ?>"><?php echo htmlspecialchars($o['shipping_phone'] ?? ''); ?></a>
                                <?php else: ?>
                                    <span class="badge badge-info" style="font-size: 11px;">🏪 Khách tự lấy</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($o['student_name']); ?></strong><br>
                                <small style="color: var(--text-muted);"><?php echo htmlspecialchars($o['student_email']); ?></small>
                            </td>
                            <td>
                                <strong style="color: var(--secondary-color);"><?php echo format_money($o['price']); ?></strong>
                                <div style="margin-top: 4px;"><?php echo get_payment_method_badge($o['payment_method'] ?? 'wallet', $o['payment_status'] ?? 'da_thanh_toan'); ?></div>
                            </td>
                            <td>
                                <span style="font-family: monospace; font-size: 16px; font-weight: bold; color: #d84315;">
                                    <?php echo htmlspecialchars($o['pickup_code']); ?>
                                </span>
                            </td>
                            <td><?php echo get_order_status_badge($o['status']); ?></td>
                            <td style="font-size: 12px; color: var(--text-muted); line-height: 1.4;">
                                <div>Đặt: <?php echo format_datetime($o['created_at']); ?></div>
                                <?php if (!empty($o['picked_up_at'])): ?>
                                    <div>Nhận: <?php echo format_datetime($o['picked_up_at']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($o['hold_until']) && $o['status'] === 'da_nhan'): ?>
                                    <div style="color: #0277bd;">Giữ tiền đến: <?php echo format_datetime($o['hold_until']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($o['status'] === 'tranh_chap'): ?>
                                    <a href="<?php echo BASE_URL; ?>shop/phan_hoi.php?order_id=<?php echo $o['id']; ?>" class="btn btn-danger btn-sm">
                                        Phản đối khiếu nại
                                    </a>
                                <?php elseif ($o['status'] === 'cho_nhan'): ?>
                                    <a href="<?php echo BASE_URL; ?>shop/nhap_ma.php" class="btn btn-secondary btn-sm">
                                        Nhập mã
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 12px;">Đã xử lý</span>
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
