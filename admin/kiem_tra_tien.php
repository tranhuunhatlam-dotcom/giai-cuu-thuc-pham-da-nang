<?php
// FILE: admin/kiem_tra_tien.php
// Mục đích: Kiểm toán cân bằng quỹ tiền toàn hệ thống:
// Quy tắc kế toán: Tổng số dư ví của toàn bộ Users + Tổng tiền đang tạm giữ trong các đơn chưa hoàn thành = Tổng tiền đã nạp vào hệ thống
// Cảnh báo ngay nếu có sự sai lệch

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('admin');

// 1. Tổng số dư ví hiện tại của tất cả người dùng (users.balance)
$sql_bal = "SELECT COALESCE(SUM(balance), 0) AS total_bal FROM users";
$total_balance = (float)mysqli_fetch_assoc(mysqli_query($conn, $sql_bal))['total_bal'];

// 2. Tổng tiền đang tạm giữ trong các đơn ví Ký Quỹ Escrow chưa giải ngân (orders: cho_nhan, da_nhan, tranh_chap)
$sql_holding = "SELECT COALESCE(SUM(price), 0) AS total_hold 
                FROM orders 
                WHERE status IN ('cho_nhan', 'da_nhan', 'tranh_chap')
                AND payment_method = 'wallet'";
$total_holding = (float)mysqli_fetch_assoc(mysqli_query($conn, $sql_holding))['total_hold'];

// Thống kê bổ sung: Doanh số Tiền mặt và Chuyển khoản QR
$sql_other_payments = "SELECT 
    COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN price ELSE 0 END), 0) AS total_cash,
    COALESCE(SUM(CASE WHEN payment_method = 'bank_qr' THEN price ELSE 0 END), 0) AS total_bank_qr
    FROM orders WHERE status != 'huy_boi_khach'";
$other_payments = mysqli_fetch_assoc(mysqli_query($conn, $sql_other_payments));

// Tổng tiền thực tế đang lưu hành trong hệ thống Escrow
$total_assets = $total_balance + $total_holding;

// 3. Tổng tiền đã nạp vào hệ thống từ trước đến nay (wallet_logs lý do nạp tiền)
$sql_dep = "SELECT COALESCE(SUM(amount), 0) AS total_dep 
            FROM wallet_logs 
            WHERE amount > 0 AND (reason LIKE '%Nạp tiền demo%' OR reason LIKE '%Số dư khởi tạo%')";
$total_deposited = (float)mysqli_fetch_assoc(mysqli_query($conn, $sql_dep))['total_dep'];

// Độ lệch (nếu có)
$diff = abs($total_assets - $total_deposited);
$is_balanced = ($diff < 0.01);

// Lấy chi tiết các đơn hàng đang giữ tiền
$sql_held_orders = "SELECT o.*, b.name AS bag_name, s.name AS shop_name, u.name AS student_name 
                    FROM orders o
                    JOIN bags b ON o.bag_id = b.id
                    JOIN shops s ON o.shop_id = s.id
                    JOIN users u ON o.user_id = u.id
                    WHERE o.status IN ('cho_nhan', 'da_nhan', 'tranh_chap')
                    ORDER BY o.id DESC";
$res_held = mysqli_query($conn, $sql_held_orders);
$held_orders = [];
while ($r = mysqli_fetch_assoc($res_held)) {
    $held_orders[] = $r;
}

// Lấy danh sách số dư người dùng
$sql_user_bal = "SELECT id, name, email, role, balance FROM users WHERE balance > 0 ORDER BY balance DESC";
$res_user_bal = mysqli_query($conn, $sql_user_bal);
$users_with_money = [];
while ($r = mysqli_fetch_assoc($res_user_bal)) {
    $users_with_money[] = $r;
}

$page_title = 'Kiểm toán quỹ tiền';
require_once __DIR__ . '/../header.php';
?>

<div class="card">
    <h2 class="card-title">KIỂM TOÁN CÂN BẰNG TỔNG QUỸ TIỀN TOÀN HỆ THỐNG</h2>

    <p style="margin-bottom: 20px; font-size: 14px; color: var(--text-muted);">
        Trang này kiểm tra nguyên lý bảo toàn quỹ tiền theo thời gian thực: Mọi đồng tiền trong hệ thống chỉ sinh ra từ thao tác nạp tiền demo, sau đó luân chuyển giữa số dư người dùng (sinh viên, chủ quán) và quỹ tạm giữ của các đơn hàng.
    </p>

    <!-- Kết luận cân bằng -->
    <?php if ($is_balanced): ?>
        <div class="alert alert-success" style="font-size: 16px; padding: 18px;">
            <strong style="font-size: 18px;">✅ QUỸ TIỀN TOÀN HỆ THỐNG CÂN BẰNG TUYỆT ĐỐI!</strong><br>
            Tổng tài sản hiện có trong hệ thống (<strong><?php echo format_money($total_assets); ?></strong>) khớp hoàn toàn 100% với Tổng tiền đã nạp vào (<strong><?php echo format_money($total_deposited); ?></strong>). Không có thất thoát hay chênh lệch tiền ảo.
        </div>
    <?php else: ?>
        <div class="alert alert-danger" style="font-size: 16px; padding: 18px;">
            <strong style="font-size: 18px;">⚠️ CẢNH BÁO: PHÁT HIỆN LỆCH QUỸ TIỀN!</strong><br>
            Tổng tài sản hiện tại: <strong><?php echo format_money($total_assets); ?></strong><br>
            Tổng tiền đã nạp theo sổ nhật ký: <strong><?php echo format_money($total_deposited); ?></strong><br>
            Mức chênh lệch: <strong style="color: red;"><?php echo format_money($diff); ?></strong>. Vui lòng kiểm tra lại các bản ghi nhật ký.
        </div>
    <?php endif; ?>

    <!-- Khối so sánh 3 thành phần chính -->
    <div class="stats-grid" style="margin-top: 20px;">
        <div class="stat-card" style="border-top: 4px solid #0277bd;">
            <div style="font-size: 13px; color: var(--text-muted);">(1) Tổng số dư ví tất cả người dùng:</div>
            <div class="stat-value" style="color: #0277bd;"><?php echo format_money($total_balance); ?></div>
            <small style="color: var(--text-muted);">Sinh viên & Quán đang giữ trong ví</small>
        </div>

        <div class="stat-card" style="border-top: 4px solid #f57f17;">
            <div style="font-size: 13px; color: var(--text-muted);">(2) Tiền đang tạm giữ trong các đơn:</div>
            <div class="stat-value" style="color: #f57f17;"><?php echo format_money($total_holding); ?></div>
            <small style="color: var(--text-muted);"><?php echo count($held_orders); ?> đơn chưa giải ngân</small>
        </div>

        <div class="stat-card" style="border-top: 4px solid #2e7d32;">
            <div style="font-size: 13px; color: var(--text-muted);">(3) Tổng tài sản hiện có (1 + 2):</div>
            <div class="stat-value" style="color: #2e7d32;"><?php echo format_money($total_assets); ?></div>
            <small style="color: #2e7d32; font-weight: bold;">Khớp tổng đã nạp: <?php echo format_money($total_deposited); ?></small>
        </div>
    </div>

    <!-- Bảng chi tiết các đơn hàng đang giữ tiền -->
    <h3 style="color: var(--primary-color); margin: 25px 0 12px 0;">
        Danh Sách Các Đơn Hàng Đang Tạm Giữ Tiền (<?php echo count($held_orders); ?> đơn)
    </h3>
    <?php if (empty($held_orders)): ?>
        <p style="color: var(--text-muted);">Hiện không có đơn nào đang giữ tiền.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Túi bất ngờ</th>
                        <th>Cửa hàng</th>
                        <th>Sinh viên</th>
                        <th>Tiền đang giữ</th>
                        <th>Trạng thái đơn</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($held_orders as $ho): ?>
                        <tr>
                            <td><strong>#<?php echo $ho['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($ho['bag_name']); ?></td>
                            <td><?php echo htmlspecialchars($ho['shop_name']); ?></td>
                            <td><?php echo htmlspecialchars($ho['student_name']); ?></td>
                            <td><strong style="color: #e65100;"><?php echo format_money($ho['price']); ?></strong></td>
                            <td><?php echo get_order_status_badge($ho['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Bảng danh sách số dư người dùng -->
    <h3 style="color: var(--primary-color); margin: 25px 0 12px 0;">
        Số Dư Ví Người Dùng Đang Có Tiền (<?php echo count($users_with_money); ?> tài khoản)
    </h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên người dùng</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Số dư ví</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users_with_money as $uw): ?>
                    <tr>
                        <td>#<?php echo $uw['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($uw['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($uw['email']); ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($uw['role']); ?></span></td>
                        <td><strong style="color: #2e7d32;"><?php echo format_money($uw['balance']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
