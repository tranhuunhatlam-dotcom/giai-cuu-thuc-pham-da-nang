<?php
// FILE: header.php
// Mục đích: Phần đầu trang web, nhúng CSS, cấu hình, kiểm tra đơn hàng tự động và hiển thị menu theo vai trò

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Tự động kiểm tra và chuyển trạng thái các đơn hàng đến hạn trên mỗi lượt tải trang
check_orders($conn);

// Lấy thông tin người dùng hiện tại (nếu đã đăng nhập)
$current_user = get_logged_in_user($conn);
$unread_count = 0;
if ($current_user) {
    $unread_count = get_unread_notifications_count($conn, $current_user['id']);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Giải Cứu Thực Phẩm Đà Nẵng (Túi Bất Ngờ)</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
</head>
<body>

<header class="header-nav">
    <div class="nav-container">
        <!-- Logo thương hiệu -->
        <a href="<?php echo BASE_URL; ?>index.php" class="logo-area">
            <div class="logo-badge">🥗</div>
            <div class="logo-info">
                <div class="logo-title-row">
                    <span class="logo-text">GIẢI CỨU ĐÀ NẴNG</span>
                    <span class="logo-tag">Túi Bất Ngờ</span>
                </div>
                <small class="logo-subtitle">Nền tảng cứu trợ thực phẩm cuối ngày</small>
            </div>
        </a>

        <!-- Thanh điều hướng chính -->
        <nav class="nav-main">
            <a href="<?php echo BASE_URL; ?>index.php" class="nav-link">🏠 Trang chủ</a>
            <a href="<?php echo BASE_URL; ?>ai_tro_ly.php" class="nav-link nav-link-ai">
                🤖 AI Trợ Lý
                <span class="nav-tag-new">MỚI</span>
            </a>
            <a href="<?php echo BASE_URL; ?>doi_tac.php" class="nav-link">🏪 Quán Đối Tác</a>
            <a href="<?php echo BASE_URL; ?>vinh_danh.php" class="nav-link">🏆 Vinh Danh</a>
            <a href="<?php echo BASE_URL; ?>faq.php" class="nav-link">❓ Cẩm Nang</a>
            <a href="<?php echo BASE_URL; ?>quy_trinh.php" class="nav-link">🛡️ Escrow</a>
        </nav>

        <!-- Thao tác người dùng & Số dư ví -->
        <div class="nav-user-area">
            <?php if (!$current_user): ?>
                <a href="<?php echo BASE_URL; ?>dang_ky.php" class="nav-link">Đăng ký</a>
                <a href="<?php echo BASE_URL; ?>dang_nhap.php" class="btn btn-primary btn-sm" style="padding: 6px 16px;">Đăng nhập</a>

            <?php elseif ($current_user['role'] === 'student'): ?>
                <a href="<?php echo BASE_URL; ?>student/vi_tien.php" class="nav-balance" title="Số dư ví ký quỹ Escrow">
                    <span>💳 Ví: <strong><?php echo format_money($current_user['balance']); ?></strong></span>
                    <span class="nav-balance-plus">+</span>
                </a>
                <a href="<?php echo BASE_URL; ?>student/don_hang.php" class="nav-link nav-orders-pill">
                    🛍️ Đơn của tôi
                </a>
                <a href="<?php echo BASE_URL; ?>thong_bao.php" class="nav-icon-btn" title="Thông báo">
                    🔔
                    <?php if ($unread_count > 0): ?>
                        <span class="nav-badge-count"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo BASE_URL; ?>student/ho_so.php" class="nav-user-pill" title="Hồ sơ cá nhân">
                    <span class="user-pill-avatar">🎓</span>
                    <span class="user-pill-name"><?php echo htmlspecialchars($current_user['name']); ?></span>
                </a>
                <a href="<?php echo BASE_URL; ?>dang_xuat.php" class="nav-logout-btn" title="Đăng xuất">Thoát</a>

            <?php elseif ($current_user['role'] === 'shop_owner'): ?>
                <a href="<?php echo BASE_URL; ?>shop/nhap_ma.php" class="btn btn-secondary btn-sm" style="font-size: 12px; padding: 5px 11px;">⚡ Nhập Mã Lấy</a>
                <a href="<?php echo BASE_URL; ?>shop/ai_dinh_gia.php" class="nav-link" style="color: #4338ca; font-weight: 800;">✨ AI Định Giá</a>
                <a href="<?php echo BASE_URL; ?>shop/danh_sach_tui.php" class="nav-link">🍞 Túi quán</a>
                <a href="<?php echo BASE_URL; ?>shop/don_hang.php" class="nav-link">📋 Đơn</a>
                <a href="<?php echo BASE_URL; ?>shop/doanh_thu.php" class="nav-balance" title="Doanh thu quán">
                    💰 <strong><?php echo format_money($current_user['balance']); ?></strong>
                </a>
                <a href="<?php echo BASE_URL; ?>thong_bao.php" class="nav-icon-btn" title="Thông báo">
                    🔔
                    <?php if ($unread_count > 0): ?>
                        <span class="nav-badge-count"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo BASE_URL; ?>shop/ho_so.php" class="nav-user-pill" title="Hồ sơ quán">
                    <span class="user-pill-avatar">🏪</span>
                    <span class="user-pill-name"><?php echo htmlspecialchars($current_user['name']); ?></span>
                </a>
                <a href="<?php echo BASE_URL; ?>dang_xuat.php" class="nav-logout-btn">Thoát</a>

            <?php elseif ($current_user['role'] === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>admin/thong_ke.php" class="nav-link">📊 Thống kê</a>
                <a href="<?php echo BASE_URL; ?>admin/duyet_quan.php" class="nav-link">✅ Duyệt</a>
                <a href="<?php echo BASE_URL; ?>admin/tranh_chap.php" class="btn btn-danger btn-sm" style="font-size: 11.5px; padding: 4px 9px;">⚖️ Trọng Tài</a>
                <a href="<?php echo BASE_URL; ?>admin/tai_khoan.php" class="nav-link">👥 Tài khoản</a>
                <a href="<?php echo BASE_URL; ?>admin/cai_dat.php" class="nav-link">⚙️ Cài đặt</a>
                <a href="<?php echo BASE_URL; ?>admin/kiem_tra_tien.php" class="nav-link">🔍 Kiểm quỹ</a>
                <a href="<?php echo BASE_URL; ?>dang_xuat.php" class="nav-logout-btn">Thoát</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="container">
