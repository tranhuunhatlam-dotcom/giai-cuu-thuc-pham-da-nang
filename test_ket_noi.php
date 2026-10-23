<?php
// FILE: test_ket_noi.php
// Mục đích: Kiểm tra kết nối CSDL, hiển thị bảng cài đặt và trạng thái hệ thống

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Gọi hàm check_orders() để kiểm tra hoạt động
check_orders($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm Tra Kết Nối Hệ Thống - Giải Cứu Thực Phẩm</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
</head>
<body>
    <div class="container" style="max-width: 800px; margin-top: 40px;">
        <div class="card">
            <h1 style="color: #2e7d32;">Kiểm Tra Kết Nối CSDL & Hệ Thống</h1>
            <p>Hệ thống: <strong>Giải cứu thực phẩm cuối ngày (Đà Nẵng)</strong></p>
            <p>Múi giờ hiện tại: <strong><?php echo date_default_timezone_get(); ?></strong> (Thời gian server: <strong><?php echo date('H:i:s d/m/Y'); ?></strong>)</p>
            
            <hr>
            
            <h3>1. Trạng thái kết nối MySQL</h3>
            <?php if ($conn): ?>
                <div class="alert alert-success">
                    Kết nối MySQL thành công tới database <strong><?php echo htmlspecialchars($db_name); ?></strong>!
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    Kết nối thất bại: <?php echo mysqli_connect_error(); ?>
                </div>
            <?php endif; ?>

            <h3>2. Cấu hình hệ thống (Bảng settings)</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Tên cấu hình</th>
                        <th>Giá trị</th>
                        <th>Ý nghĩa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = mysqli_query($conn, "SELECT * FROM settings ORDER BY name ASC");
                    $meanings = [
                        'discount_min' => 'Giảm giá tối thiểu (%) so với giá gốc',
                        'discount_max' => 'Giảm giá tối đa (%) so với giá gốc',
                        'cancel_before_close_minutes' => 'Thời hạn hủy đơn trước giờ đóng cửa (phút)',
                        'hold_minutes' => 'Thời gian giữ tiền sau khi nhận túi (phút)',
                        'dispute_response_hours' => 'Thời hạn quán phản hồi khiếu nại (giờ)',
                        'max_bags_per_user' => 'Số lượng túi tối đa 1 người mua trong ngày',
                        'max_disputes_per_month' => 'Số khiếu nại tối đa của 1 sinh viên trong tháng'
                    ];
                    while ($row = mysqli_fetch_assoc($res)) {
                        $k = $row['name'];
                        $v = $row['value'];
                        $m = $meanings[$k] ?? 'Cấu hình chung';
                        echo "<tr>";
                        echo "<td><code>" . htmlspecialchars($k) . "</code></td>";
                        echo "<td><strong>" . htmlspecialchars($v) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($m) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>

            <h3>3. Thống kê nhanh dữ liệu</h3>
            <ul>
                <?php
                $u_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"))['c'];
                $s_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM shops"))['c'];
                $b_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM bags"))['c'];
                $o_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders"))['c'];
                ?>
                <li>Người dùng: <strong><?php echo $u_count; ?></strong></li>
                <li>Cửa hàng: <strong><?php echo $s_count; ?></strong></li>
                <li>Túi cứu trợ: <strong><?php echo $b_count; ?></strong></li>
                <li>Đơn hàng: <strong><?php echo $o_count; ?></strong></li>
            </ul>

            <div style="margin-top: 25px;">
                <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-primary">Về Trang Chủ</a>
                <a href="<?php echo BASE_URL; ?>tao_du_lieu_mau.php" class="btn btn-secondary">Tạo Dữ Liệu Mẫu Demo</a>
            </div>
        </div>
    </div>
</body>
</html>
