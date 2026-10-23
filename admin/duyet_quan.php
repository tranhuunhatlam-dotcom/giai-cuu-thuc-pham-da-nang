<?php
// FILE: admin/duyet_quan.php
// Mục đích: Quản trị viên duyệt hồ sơ quán đối tác tại Đà Nẵng

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('admin');

$notice = '';

// Xử lý duyệt quán hoặc hủy duyệt
if (isset($_GET['action']) && isset($_GET['shop_id'])) {
    $shop_id = (int)$_GET['shop_id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $stmt = mysqli_prepare($conn, "UPDATE shops SET status = 'da_duyet' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $shop_id);
        if (mysqli_stmt_execute($stmt)) {
            // Lấy owner_id để gửi thông báo
            $s_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, owner_id FROM shops WHERE id = $shop_id"));
            if ($s_info) {
                add_notification($conn, $s_info['owner_id'], "Chúc mừng! Cửa hàng '" . $s_info['name'] . "' của bạn đã được Admin phê duyệt. Bạn có thể bắt đầu tạo túi bất ngờ để đăng bán.");
            }
            $notice = 'Đã phê duyệt cửa hàng thành công!';
        }
        mysqli_stmt_close($stmt);
    } elseif ($action === 'unapprove') {
        $stmt = mysqli_prepare($conn, "UPDATE shops SET status = 'cho_duyet' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $shop_id);
        if (mysqli_stmt_execute($stmt)) {
            $notice = 'Đã chuyển trạng thái quán về Chờ duyệt.';
        }
        mysqli_stmt_close($stmt);
    }
}

// Lấy danh sách toàn bộ quán
$sql = "SELECT s.*, u.name AS owner_name, u.email AS owner_email,
               (SELECT COUNT(*) FROM bags WHERE shop_id = s.id) AS total_bags,
               (SELECT COUNT(*) FROM orders WHERE shop_id = s.id) AS total_orders
        FROM shops s
        JOIN users u ON s.owner_id = u.id
        ORDER BY (s.status = 'cho_duyet') DESC, s.id DESC";
$res = mysqli_query($conn, $sql);
$shops = [];
while ($row = mysqli_fetch_assoc($res)) {
    $shops[] = $row;
}

$page_title = 'Duyệt cửa hàng';
require_once __DIR__ . '/../header.php';
?>

<div class="card">
    <div class="card-title">
        <span>Quản Lý Phê Duyệt Cửa Hàng Đối Tác</span>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div>
    <?php endif; ?>

    <?php if (empty($shops)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 30px 0;">Chưa có cửa hàng nào đăng ký.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Tên quán & Địa chỉ</th>
                        <th>Chủ sở hữu</th>
                        <th>Loại quán & Quận</th>
                        <th>Vi phạm</th>
                        <th>Túi / Đơn</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shops as $s): ?>
                        <tr style="<?php echo ($s['status'] === 'cho_duyet') ? 'background-color: #fffde7;' : ''; ?>">
                            <td style="width: 70px;">
                                <?php if (!empty($s['image'])): ?>
                                    <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($s['image']); ?>" alt="Ảnh quán" style="width: 60px; height: 45px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div style="width: 60px; height: 45px; background: #eee; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #888;">No pic</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($s['name']); ?></strong><br>
                                <small style="color: var(--text-muted);"><?php echo htmlspecialchars($s['address']); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($s['owner_name']); ?></strong><br>
                                <small style="color: var(--text-muted);"><?php echo htmlspecialchars($s['owner_email']); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($s['type']); ?><br>
                                <span class="badge badge-info"><?php echo htmlspecialchars($s['district']); ?></span>
                            </td>
                            <td>
                                <strong style="color: <?php echo $s['violation_count'] > 0 ? '#c62828' : '#2e7d32'; ?>;">
                                    <?php echo $s['violation_count']; ?> lần
                                </strong>
                            </td>
                            <td>
                                <?php echo $s['total_bags']; ?> túi / <?php echo $s['total_orders']; ?> đơn
                            </td>
                            <td>
                                <?php if ($s['status'] === 'da_duyet'): ?>
                                    <span class="badge badge-success">Đã duyệt</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Chờ duyệt</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['status'] === 'cho_duyet'): ?>
                                    <a href="<?php echo BASE_URL; ?>admin/duyet_quan.php?action=approve&shop_id=<?php echo $s['id']; ?>" class="btn btn-primary btn-sm" onclick="return confirm('Bạn có chắc muốn phê duyệt quán này?');">
                                        Duyệt ngay
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>admin/duyet_quan.php?action=unapprove&shop_id=<?php echo $s['id']; ?>" class="btn btn-outline btn-sm" onclick="return confirm('Hủy duyệt quán này? Quán sẽ không thể đăng thêm túi mới.');">
                                        Hủy duyệt
                                    </a>
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
