<?php
// FILE: shop/danh_sach_tui.php
// Mục đích: Quản lý danh sách các túi bất ngờ của chủ quán

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('shop_owner');
$user = get_logged_in_user($conn);
$shop = get_shop_by_owner_id($conn, $user['id']);

if (!$shop) {
    die("Lỗi: Không tìm thấy quán của bạn.");
}

$notice = '';
if (isset($_GET['added'])) {
    $notice = 'Đã thêm túi bất ngờ mới thành công!';
}
if (isset($_GET['updated'])) {
    $notice = 'Đã cập nhật thông tin túi thành công!';
}
if (isset($_GET['deleted'])) {
    $notice = 'Đã xóa túi thành công!';
}
if (isset($_GET['error_delete'])) {
    $notice = 'Không thể xóa túi này vì đã có sinh viên đặt hàng!';
}

// Lấy toàn bộ túi của quán
$sql = "SELECT * FROM bags WHERE shop_id = ? ORDER BY id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $shop['id']);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$bags = [];
while ($row = mysqli_fetch_assoc($res)) {
    $bags[] = $row;
}
mysqli_stmt_close($stmt);

$page_title = 'Quản lý túi bất ngờ';
require_once __DIR__ . '/../header.php';
?>

<div class="card">
    <div class="card-title">
        <span>Quản Lý Túi Bất Ngờ (Quán: <?php echo htmlspecialchars($shop['name']); ?>)</span>
        <?php if ($shop['status'] === 'da_duyet'): ?>
            <a href="<?php echo BASE_URL; ?>shop/them_tui.php" class="btn btn-primary btn-sm">+ Thêm Túi Mới</a>
        <?php else: ?>
            <span class="badge badge-warning">Quán chưa được duyệt nên chưa thể đăng bán</span>
        <?php endif; ?>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($notice); ?></div>
    <?php endif; ?>

    <?php if ($shop['status'] !== 'da_duyet'): ?>
        <div class="alert alert-warning">
            Quán của bạn đang chờ Quản trị viên (Admin) xét duyệt hồ sơ. Sau khi duyệt xong, bạn có thể tạo túi bất ngờ cứu trợ thực phẩm!
        </div>
    <?php endif; ?>

    <?php if (empty($bags)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 30px 0;">
            Quán chưa có túi bất ngờ nào. Hãy bấm <strong>"+ Thêm Túi Mới"</strong> để bắt đầu tạo túi cứu trợ cuối ngày!
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Tên túi</th>
                        <th>Giá gốc / Giá web</th>
                        <th>% Giảm</th>
                        <th>Số lượng kho</th>
                        <th>Khung giờ (Mở bán / Nhận hàng / Đóng)</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $now = date('Y-m-d H:i:s');
                    foreach ($bags as $b): 
                        $discount_percent = round((1 - ($b['web_price'] / $b['original_price'])) * 100);
                        
                        // Xác định trạng thái thời gian
                        $status_text = '';
                        if ($now < $b['open_at']) {
                            $status_text = '<span class="badge badge-gray">Sắp mở bán</span>';
                        } elseif ($now >= $b['open_at'] && $now <= $b['close_at']) {
                            if ($b['stock'] > 0) {
                                $status_text = '<span class="badge badge-success">Đang mở bán</span>';
                            } else {
                                $status_text = '<span class="badge badge-danger">Hết túi</span>';
                            }
                        } else {
                            $status_text = '<span class="badge badge-secondary">Đã đóng cửa</span>';
                        }
                    ?>
                        <tr>
                            <td style="width: 70px;">
                                <?php if (!empty($b['image'])): ?>
                                    <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($b['image']); ?>" alt="Ảnh" style="width: 60px; height: 45px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div style="width: 60px; height: 45px; background: #eee; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #888;">No pic</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($b['name']); ?></strong><br>
                                <small style="color: var(--text-muted);"><?php echo htmlspecialchars(mb_substr($b['description'] ?? '', 0, 40)) . '...'; ?></small>
                            </td>
                            <td>
                                <span style="text-decoration: line-through; color: #999; font-size: 12px;"><?php echo format_money($b['original_price']); ?></span><br>
                                <strong style="color: var(--secondary-color);"><?php echo format_money($b['web_price']); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-warning">-<?php echo $discount_percent; ?>%</span>
                            </td>
                            <td>
                                <strong style="font-size: 15px; color: <?php echo $b['stock'] > 0 ? '#2e7d32' : '#c62828'; ?>;">
                                    <?php echo $b['stock']; ?>
                                </strong>
                            </td>
                            <td style="font-size: 12px; line-height: 1.4;">
                                <div>Mở bán: <strong><?php echo format_datetime($b['open_at']); ?></strong></div>
                                <div>Nhận hàng: <strong><?php echo date('H:i', strtotime($b['pickup_start'])); ?> - <?php echo date('H:i d/m', strtotime($b['pickup_end'])); ?></strong></div>
                                <div>Đóng cửa: <strong style="color: #c62828;"><?php echo format_datetime($b['close_at']); ?></strong></div>
                            </td>
                            <td><?php echo $status_text; ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>shop/sua_tui.php?id=<?php echo $b['id']; ?>" class="btn btn-outline btn-sm">Sửa</a>
                                <a href="<?php echo BASE_URL; ?>shop/xoa_tui.php?id=<?php echo $b['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa túi bất ngờ này?');">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
