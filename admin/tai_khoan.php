<?php
// FILE: admin/tai_khoan.php
// Mục đích: Quản lý danh sách tài khoản người dùng, khóa / mở khóa tài khoản và quản lý số lần cảnh cáo

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('admin');

$notice = '';

// Xử lý các hành động
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $target_id = (int)$_GET['user_id'];
    $action = $_GET['action'];

    // Không cho tự khóa tài khoản Admin đang đăng nhập
    if ($target_id == $_SESSION['user_id']) {
        $notice = 'Không thể thao tác trên chính tài khoản Quản trị đang đăng nhập.';
    } else {
        if ($action === 'lock') {
            $stmt = mysqli_prepare($conn, "UPDATE users SET is_locked = 1 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $target_id);
            if (mysqli_stmt_execute($stmt)) {
                $notice = 'Đã khóa tài khoản thành công!';
            }
            mysqli_stmt_close($stmt);
        } elseif ($action === 'unlock') {
            $stmt = mysqli_prepare($conn, "UPDATE users SET is_locked = 0 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $target_id);
            if (mysqli_stmt_execute($stmt)) {
                $notice = 'Đã mở khóa tài khoản thành công!';
            }
            mysqli_stmt_close($stmt);
        } elseif ($action === 'reset_warning') {
            $stmt = mysqli_prepare($conn, "UPDATE users SET warning_count = 0 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $target_id);
            if (mysqli_stmt_execute($stmt)) {
                $notice = 'Đã xóa và đặt lại số lần cảnh cáo về 0!';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Lấy danh sách toàn bộ người dùng
$sql = "SELECT u.*, s.name AS shop_name 
        FROM users u 
        LEFT JOIN shops s ON u.id = s.owner_id 
        ORDER BY u.id DESC";
$res = mysqli_query($conn, $sql);
$users = [];
while ($row = mysqli_fetch_assoc($res)) {
    $users[] = $row;
}

$page_title = 'Quản lý tài khoản';
require_once __DIR__ . '/../header.php';
?>

<div class="card">
    <div class="card-title">
        <span>Quản Lý Tài Khoản Người Dùng</span>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Số dư ví</th>
                    <th>Cảnh cáo</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr style="<?php echo ($u['is_locked']) ? 'background-color: #ffebee;' : ''; ?>">
                        <td>#<?php echo $u['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                            <?php if (!empty($u['shop_name'])): ?>
                                <br><small style="color: var(--primary-color);">Quán: <?php echo htmlspecialchars($u['shop_name']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge badge-purple">Admin</span>
                            <?php elseif ($u['role'] === 'shop_owner'): ?>
                                <span class="badge badge-warning">Chủ quán</span>
                            <?php else: ?>
                                <span class="badge badge-info">Sinh viên</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo format_money($u['balance']); ?></strong></td>
                        <td>
                            <strong style="color: <?php echo ($u['warning_count'] >= 3) ? '#c62828' : (($u['warning_count'] > 0) ? '#f57f17' : '#2e7d32'); ?>;">
                                <?php echo $u['warning_count']; ?> / 3
                            </strong>
                            <?php if ($u['warning_count'] > 0): ?>
                                <br><a href="<?php echo BASE_URL; ?>admin/tai_khoan.php?action=reset_warning&user_id=<?php echo $u['id']; ?>" style="font-size: 11px; color: #0277bd;">Reset về 0</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['is_locked']): ?>
                                <span class="badge badge-danger">ĐANG BỊ KHÓA</span>
                            <?php else: ?>
                                <span class="badge badge-success">Hoạt động</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['role'] !== 'admin'): ?>
                                <?php if ($u['is_locked']): ?>
                                    <a href="<?php echo BASE_URL; ?>admin/tai_khoan.php?action=unlock&user_id=<?php echo $u['id']; ?>" class="btn btn-primary btn-sm" onclick="return confirm('Mở khóa cho tài khoản này?');">
                                        Mở khóa
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>admin/tai_khoan.php?action=lock&user_id=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn KHÓA tài khoản này? Người này sẽ không thể đăng nhập.');">
                                        Khóa TK
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size: 12px; color: var(--text-muted);">Mặc định</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
