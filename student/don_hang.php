<?php
// FILE: student/don_hang.php
// Mục đích: Danh sách toàn bộ đơn hàng của sinh viên, hiển thị mã nhận hàng và trạng thái rõ ràng

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('student');
$user = get_logged_in_user($conn);

// Lấy danh sách đơn hàng của sinh viên
$sql = "SELECT o.*, b.name AS bag_name, b.image AS bag_image, s.name AS shop_name, s.address AS shop_address, s.district AS shop_district
        FROM orders o
        JOIN bags b ON o.bag_id = b.id
        JOIN shops s ON o.shop_id = s.id
        WHERE o.user_id = ?
        ORDER BY o.id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user['id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$orders = [];
while ($row = mysqli_fetch_assoc($res)) {
    $orders[] = $row;
}
mysqli_stmt_close($stmt);

$page_title = 'Đơn hàng của tôi';
require_once __DIR__ . '/../header.php';
?>

<div class="card">
    <div class="card-title">
        <span>Đơn Hàng Của Tôi</span>
        <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-outline btn-sm">+ Đặt thêm túi khác</a>
    </div>

    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 40px 0; color: var(--text-muted);">
            <p style="font-size: 16px; margin-bottom: 12px;">Bạn chưa đặt đơn túi bất ngờ nào.</p>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-primary">Khám phá túi bất ngờ ngay</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Túi bất ngờ & Quán</th>
                        <th>Giá tiền</th>
                        <th>Mã nhận hàng</th>
                        <th>Trạng thái</th>
                        <th>Thời gian đặt</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong>#<?php echo $o['id']; ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <strong><?php echo htmlspecialchars($o['bag_name']); ?></strong>
                                    <?php if (($o['delivery_type'] ?? 'pickup') === 'delivery'): ?>
                                        <span class="badge badge-success" style="font-size: 10px; padding: 2px 6px;">🛵 Đặt Ship</span>
                                    <?php else: ?>
                                        <span class="badge badge-gray" style="font-size: 10px; padding: 2px 6px;">🏪 Tự lấy</span>
                                    <?php endif; ?>
                                </div>
                                <small style="color: var(--text-muted);">
                                    🏪 <?php echo htmlspecialchars($o['shop_name']); ?> (<?php echo htmlspecialchars($o['shop_district']); ?>)
                                </small>
                            </td>
                            <td>
                                <strong style="color: var(--secondary-color);"><?php echo format_money($o['price']); ?></strong>
                                <div style="margin-top: 4px;"><?php echo get_payment_method_badge($o['payment_method'] ?? 'wallet', $o['payment_status'] ?? 'da_thanh_toan'); ?></div>
                            </td>
                            <td>
                                <span style="font-family: monospace; font-size: 18px; font-weight: 800; color: #d84315; background: #fff3e0; padding: 3px 8px; border-radius: 4px; border: 1px dashed #ffb74d;">
                                    <?php echo htmlspecialchars($o['pickup_code']); ?>
                                </span>
                            </td>
                            <td><?php echo get_order_status_badge($o['status']); ?></td>
                            <td style="font-size: 13px; color: var(--text-muted);">
                                <?php echo format_datetime($o['created_at']); ?>
                            </td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>student/don_chi_tiet.php?id=<?php echo $o['id']; ?>" class="btn btn-primary btn-sm">
                                    Chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
