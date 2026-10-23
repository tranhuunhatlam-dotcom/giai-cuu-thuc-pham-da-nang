<?php
// FILE: thong_bao.php
// Mục đích: Xem danh sách thông báo và đánh dấu đã đọc

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

require_login();
$user_id = $_SESSION['user_id'];

// Xử lý khi người dùng bấm "Đánh dấu tất cả đã đọc"
if (isset($_POST['mark_all_read'])) {
    $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: " . BASE_URL . "thong_bao.php");
    exit;
}

// Lấy danh sách thông báo của người dùng hiện tại
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$notifications = [];
while ($row = mysqli_fetch_assoc($res)) {
    $notifications[] = $row;
}
mysqli_stmt_close($stmt);

$page_title = 'Thông báo hệ thống';
require_once __DIR__ . '/header.php';
?>

<div class="card">
    <div class="card-title">
        <span>Thông Báo Của Bạn</span>
        <?php if (!empty($notifications)): ?>
            <form action="<?php echo BASE_URL; ?>thong_bao.php" method="POST" style="margin: 0;">
                <button type="submit" name="mark_all_read" value="1" class="btn btn-outline btn-sm">
                    Đánh dấu tất cả đã đọc
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <p style="color: var(--text-muted); padding: 20px 0; text-align: center;">Bạn chưa có thông báo nào.</p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($notifications as $n): ?>
                <div style="padding: 14px 16px; border-radius: 8px; border: 1px solid <?php echo $n['is_read'] ? '#e0e0e0' : '#81c784'; ?>; background-color: <?php echo $n['is_read'] ? '#ffffff' : '#f1f8e9'; ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-size: 12px; color: var(--text-muted);">
                            <?php echo format_datetime($n['created_at']); ?>
                        </span>
                        <?php if (!$n['is_read']): ?>
                            <span class="badge badge-success">Mới</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 14px; color: var(--text-dark);">
                        <?php echo nl2br(htmlspecialchars($n['message'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
