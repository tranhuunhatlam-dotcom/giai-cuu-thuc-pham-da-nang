<?php
// FILE: admin/thong_ke.php
// Mục đích: Thống kê số liệu hệ thống bằng SQL: số túi giải cứu, tiền sinh viên tiết kiệm, số tranh chấp

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('admin');

// 1. Số túi đã giải cứu thành công
$sql_rescued = "SELECT COUNT(*) AS total FROM orders WHERE status IN ('hoan_thanh', 'chuyen_tien_quan')";
$total_rescued = mysqli_fetch_assoc(mysqli_query($conn, $sql_rescued))['total'];

// 2. Số tiền sinh viên đã tiết kiệm được (Giá gốc - Giá web)
$sql_saved = "SELECT COALESCE(SUM(b.original_price - o.price), 0) AS saved_money 
              FROM orders o 
              JOIN bags b ON o.bag_id = b.id 
              WHERE o.status IN ('hoan_thanh', 'chuyen_tien_quan')";
$total_saved_money = mysqli_fetch_assoc(mysqli_query($conn, $sql_saved))['saved_money'];

// 3. Tổng số vụ tranh chấp
$sql_disp = "SELECT COUNT(*) AS total FROM disputes";
$total_disputes = mysqli_fetch_assoc(mysqli_query($conn, $sql_disp))['total'];

// 4. Số đơn tranh chấp đang chờ xử lý
$sql_pending_disp = "SELECT COUNT(*) AS total FROM disputes WHERE status IN ('cho_shop_phan_hoi', 'cho_admin_xu_ly')";
$pending_disputes = mysqli_fetch_assoc(mysqli_query($conn, $sql_pending_disp))['total'];

// 5. Tổng số người dùng & số quán
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'student'"))['total'];
$total_shops_approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM shops WHERE status = 'da_duyet'"))['total'];
$total_shops_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM shops WHERE status = 'cho_duyet'"))['total'];

// 6. Danh sách 10 giao dịch ví gần nhất
$sql_recent_logs = "SELECT w.*, u.name AS user_name, u.role AS user_role 
                    FROM wallet_logs w 
                    JOIN users u ON w.user_id = u.id 
                    ORDER BY w.id DESC LIMIT 10";
$recent_logs = mysqli_query($conn, $sql_recent_logs);

$page_title = 'Thống kê quản trị';
require_once __DIR__ . '/../header.php';
?>

<div class="card">
    <h2 class="card-title">BẢNG ĐIỀU KHIỂN & THỐNG KÊ TOÀN DIỆN</h2>

    <div class="stats-grid">
        <div class="stat-card" style="border-top: 4px solid #2e7d32;">
            <div style="font-size: 13px; color: var(--text-muted);">Số túi đã giải cứu thành công:</div>
            <div class="stat-value"><?php echo $total_rescued; ?> túi</div>
            <small style="color: #2e7d32;">Bảo vệ môi trường, giảm lãng phí</small>
        </div>

        <div class="stat-card" style="border-top: 4px solid #e65100;">
            <div style="font-size: 13px; color: var(--text-muted);">Tiền sinh viên đã tiết kiệm:</div>
            <div class="stat-value orange"><?php echo format_money($total_saved_money); ?></div>
            <small style="color: #e65100;">Chênh lệch giữa giá gốc & giá web</small>
        </div>

        <div class="stat-card" style="border-top: 4px solid #c62828;">
            <div style="font-size: 13px; color: var(--text-muted);">Số vụ tranh chấp / khiếu nại:</div>
            <div class="stat-value red"><?php echo $total_disputes; ?> vụ</div>
            <small style="color: #c62828; font-weight: bold;">(<?php echo $pending_disputes; ?> vụ đang chờ xử lý)</small>
        </div>

        <div class="stat-card" style="border-top: 4px solid #0277bd;">
            <div style="font-size: 13px; color: var(--text-muted);">Đối tác cửa hàng:</div>
            <div class="stat-value" style="color: #0277bd;"><?php echo $total_shops_approved; ?> quán</div>
            <small style="color: var(--text-muted);">+ <?php echo $total_shops_pending; ?> quán chờ duyệt</small>
        </div>
    </div>

    <!-- Phím tắt quản trị nhanh -->
    <div style="display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;">
        <a href="<?php echo BASE_URL; ?>admin/duyet_quan.php" class="btn btn-outline">
            Duyệt Quán (<?php echo $total_shops_pending; ?> chờ)
        </a>
        <a href="<?php echo BASE_URL; ?>admin/tranh_chap.php" class="btn btn-danger">
            Xử Lý Khiếu Nại (<?php echo $pending_disputes; ?> chờ)
        </a>
        <a href="<?php echo BASE_URL; ?>admin/kiem_tra_tien.php" class="btn btn-primary">
            Kiểm Tra Cân Bằng Quỹ Tiền
        </a>
        <a href="<?php echo BASE_URL; ?>admin/cai_dat.php" class="btn btn-outline">
            Cấu Hình Hệ Thống
        </a>
    </div>

    <!-- 10 Biến động ví gần nhất -->
    <h3 style="color: var(--primary-color); margin-bottom: 12px;">10 Giao Dịch Ví Tiền Gần Nhất</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Thời gian</th>
                    <th>Người dùng</th>
                    <th>Vai trò</th>
                    <th>Số tiền</th>
                    <th>Nội dung</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($l = mysqli_fetch_assoc($recent_logs)): ?>
                    <tr>
                        <td>#<?php echo $l['id']; ?></td>
                        <td style="font-size: 13px; color: var(--text-muted);"><?php echo format_datetime($l['created_at']); ?></td>
                        <td><strong><?php echo htmlspecialchars($l['user_name']); ?></strong></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($l['user_role']); ?></span></td>
                        <td>
                            <?php if ($l['amount'] > 0): ?>
                                <strong style="color: #2e7d32;">+<?php echo format_money($l['amount']); ?></strong>
                            <?php else: ?>
                                <strong style="color: #c62828;"><?php echo format_money($l['amount']); ?></strong>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($l['reason']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
