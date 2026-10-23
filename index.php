<?php
// FILE: index.php
// Mục đích: Trang chủ cao cấp toàn diện: thiết kế hiện đại, hình ảnh chất lượng cao, bộ lọc thông minh và trực quan hóa mô hình Túi Bất Ngờ

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Nhận tham số lọc từ form GET
$district = trim($_GET['district'] ?? '');
$type = trim($_GET['type'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');
$price_range = trim($_GET['price_range'] ?? '');

// Xây dựng câu truy vấn: Chỉ hiển thị túi của các quán đã được duyệt (shops.status = 'da_duyet') và chưa quá giờ đóng cửa (bags.close_at > NOW())
$sql = "SELECT b.*, s.name AS shop_name, s.address AS shop_address, s.district AS shop_district, s.type AS shop_type, s.image AS shop_image 
        FROM bags b
        JOIN shops s ON b.shop_id = s.id
        WHERE s.status = 'da_duyet' AND b.close_at > NOW()";

$params = [];
$types = '';

if (!empty($district)) {
    $sql .= " AND s.district = ?";
    $params[] = $district;
    $types .= 's';
}

if (!empty($type)) {
    $sql .= " AND s.type = ?";
    $params[] = $type;
    $types .= 's';
}

if (!empty($keyword)) {
    $sql .= " AND (b.name LIKE ? OR s.name LIKE ?)";
    $like = "%$keyword%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($price_range === 'under_25') {
    $sql .= " AND b.web_price < 25000";
} elseif ($price_range === '25_35') {
    $sql .= " AND b.web_price BETWEEN 25000 AND 35000";
} elseif ($price_range === '35_50') {
    $sql .= " AND b.web_price BETWEEN 35001 AND 50000";
} elseif ($price_range === 'above_50') {
    $sql .= " AND b.web_price > 50000";
}

// Sắp xếp: Túi đang mở bán lên trước, sau đó tới thời gian mở bán
$sql .= " ORDER BY (b.open_at <= NOW() AND b.stock > 0) DESC, b.open_at ASC, b.id DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$all_bags = [];
while ($row = mysqli_fetch_assoc($res)) {
    $all_bags[] = $row;
}
mysqli_stmt_close($stmt);

// Phân loại túi: Đang mở bán và Sắp mở bán
$now_str = date('Y-m-d H:i:s');
$bags_open = [];
$bags_upcoming = [];

foreach ($all_bags as $b) {
    if ($now_str >= $b['open_at'] && $b['stock'] > 0) {
        $bags_open[] = $b;
    } else {
        $bags_upcoming[] = $b;
    }
}

$page_title = 'Trang chủ - Giải Cứu Thực Phẩm Đà Nẵng';
require_once __DIR__ . '/header.php';
?>

<!-- =========================================================================
     MỤC 1: HERO BANNER ĐẲNG CẤP (Dual-Column Showcase & Impact Stats)
     ========================================================================= -->
<div class="hero-wrapper">
    <div class="hero-grid">
        <!-- Cột trái: Tiêu đề & Thông điệp -->
        <div>
            <div class="hero-tag-live">
                <span class="pulse-dot"></span>
                <span>NỀN TẢNG O2O GIẢI CỨU THỰC PHẨM ĐẦU TIÊN TẠI ĐÀ NẴNG</span>
            </div>

            <h1 class="hero-heading">
                TÚI BẤT NGỜ CUỐI NGÀY<br>
                <span class="hero-highlight">GIẢM SÂU 30% - 50%</span> CHO SINH VIÊN
            </h1>

            <p class="hero-subtext">
                Kết nối tiệm bánh tươi, quán cơm, trà sữa tại Hải Châu, Thanh Khê, Sơn Trà... Gom trọn thực phẩm sạch thơm ngon cuối ngày, tiết kiệm tối đa và chung tay chống lãng phí!
            </p>

            <div class="hero-stats-flex">
                <div class="hero-stat-card">
                    <span class="hero-stat-icon">🥖</span>
                    <div>
                        <div class="hero-stat-num">1.250+</div>
                        <div class="hero-stat-label">Túi Đã Cứu</div>
                    </div>
                </div>

                <div class="hero-stat-card">
                    <span class="hero-stat-icon">💰</span>
                    <div>
                        <div class="hero-stat-num">68+ Triệu</div>
                        <div class="hero-stat-label">Tiết Kiệm Cho SV</div>
                    </div>
                </div>

                <div class="hero-stat-card">
                    <span class="hero-stat-icon">🏪</span>
                    <div>
                        <div class="hero-stat-num">35+ Quán</div>
                        <div class="hero-stat-label">Đối Tác Đà Nẵng</div>
                    </div>
                </div>

                <div class="hero-stat-card">
                    <span class="hero-stat-icon">🛡️</span>
                    <div>
                        <div class="hero-stat-num">100%</div>
                        <div class="hero-stat-label">Escrow Tạm Giữ</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cột phải: Thẻ Showcase minh họa tương tác -->
        <div class="hero-showcase-card">
            <span class="hero-showcase-badge">🔥 Đang Sốt Hôm Nay</span>
            <img src="<?php echo BASE_URL; ?>uploads/bag_banh_ngot.jpg" alt="Túi Bánh Mì Bơ Tươi" class="hero-showcase-img">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <span style="font-size: 12.5px; color: var(--text-muted); font-weight: 700;">🏪 Tiệm Bánh BonBon • Q. Hải Châu</span>
                <span class="badge badge-success" style="font-size: 11px;">🟢 Còn 4 suất</span>
            </div>
            <h3 style="font-size: 17px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">Túi Bánh Ngọt Pháp & Croissant Bơ Tươi</h3>
            <div style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 12px;">
                <span class="original-price" style="font-size: 14px;">110.000 đ</span>
                <span class="web-price" style="font-size: 22px;">55.000 đ</span>
                <span class="badge badge-secondary" style="font-size: 12px; margin-left: auto;">-50% TIẾT KIỆM</span>
            </div>
            <a href="<?php echo BASE_URL; ?>tui_chi_tiet.php?id=1" class="btn btn-primary btn-block btn-sm" style="font-weight: 800;">
                Săn Túi Này Ngay &rarr;
            </a>
        </div>
    </div>
</div>

<!-- =========================================================================
     MỤC 2: BĂNG THÔNG ĐIỆP CỐT LÕI (Mô hình Túi Mù & Giá Web)
     ========================================================================= -->
<div class="notice-callout">
    <div class="notice-callout-icon">💡</div>
    <div class="notice-callout-text">
        <strong>Quy tắc Túi Bất Ngờ (Surprise Bag):</strong> Khách mua trực tiếp tại quán (offline) được tự do chọn món nhưng bắt buộc thanh toán 100% giá gốc. Mức giảm sâu độc quyền 30% - 50% chỉ dành riêng cho bạn khi đặt trước trên website, vì bạn đồng ý nhận túi ngẫu nhiên để đồng hành cùng quán giải cứu đồ ăn sạch cuối ngày!
    </div>
</div>

<!-- Gợi ý săn túi theo cụm trường Đại học Đà Nẵng & Mức giá -->
<div style="margin-bottom: 12px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
    <span style="font-size: 13px; font-weight: 800; color: var(--text-dark);">🎓 Săn túi gần trường:</span>
    <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-sm <?php echo (empty($district) && empty($price_range)) ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 12px; padding: 4px 10px;">Toàn thành phố</a>
    <a href="<?php echo BASE_URL; ?>index.php?district=Ng%C5%A9+H%C3%A0nh+S%C6%A1n" class="btn btn-sm <?php echo ($district === 'Ngũ Hành Sơn') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 12px; padding: 4px 10px;">VKU & Đô Thị ĐHĐN</a>
    <a href="<?php echo BASE_URL; ?>index.php?district=Li%C3%AAn+Chi%E1%BB%83u" class="btn btn-sm <?php echo ($district === 'Liên Chiểu') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 12px; padding: 4px 10px;">Bách Khoa - Sư Phạm</a>
    <a href="<?php echo BASE_URL; ?>index.php?district=H%E1%BA%A3i+Ch%C3%A2u" class="btn btn-sm <?php echo ($district === 'Hải Châu') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 12px; padding: 4px 10px;">Kinh Tế - Duy Tân</a>
    <a href="<?php echo BASE_URL; ?>index.php?district=Thanh+Kh%C3%AA" class="btn btn-sm <?php echo ($district === 'Thanh Khê') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 12px; padding: 4px 10px;">Thanh Khê</a>
    <a href="<?php echo BASE_URL; ?>index.php?district=S%C6%A1n+Tr%C3%A0" class="btn btn-sm <?php echo ($district === 'Sơn Trà') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 12px; padding: 4px 10px;">Sơn Trà</a>
    <a href="<?php echo BASE_URL; ?>index.php?district=C%E1%BA%A9m+L%E1%BB%87" class="btn btn-sm <?php echo ($district === 'Cẩm Lệ') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 12px; padding: 4px 10px;">Cẩm Lệ</a>
    <a href="<?php echo BASE_URL; ?>index.php?district=H%C3%B2a+Vang" class="btn btn-sm <?php echo ($district === 'Hòa Vang') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 12px; padding: 4px 10px;">Hòa Vang</a>
</div>

<div style="margin-bottom: 18px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
    <span style="font-size: 13px; font-weight: 800; color: #047857;">💰 Phân khúc giá:</span>
    <a href="<?php echo BASE_URL; ?>index.php?price_range=under_25" class="badge <?php echo ($price_range === 'under_25') ? 'badge-success' : 'badge-gray'; ?>" style="font-size: 12px; padding: 6px 12px; text-decoration: none;">💸 Dưới 25k (Siêu rẻ 17k - 22k)</a>
    <a href="<?php echo BASE_URL; ?>index.php?price_range=25_35" class="badge <?php echo ($price_range === '25_35') ? 'badge-success' : 'badge-gray'; ?>" style="font-size: 12px; padding: 6px 12px; text-decoration: none;">🍱 25k - 35k (Cơm & Mì no bụng)</a>
    <a href="<?php echo BASE_URL; ?>index.php?price_range=35_50" class="badge <?php echo ($price_range === '35_50') ? 'badge-success' : 'badge-gray'; ?>" style="font-size: 12px; padding: 6px 12px; text-decoration: none;">🥐 35k - 50k (Bánh Pháp & Trà sữa)</a>
    <a href="<?php echo BASE_URL; ?>index.php?price_range=above_50" class="badge <?php echo ($price_range === 'above_50') ? 'badge-success' : 'badge-gray'; ?>" style="font-size: 12px; padding: 6px 12px; text-decoration: none;">✨ Trên 50k (Combo thượng hạng)</a>
</div>

<!-- =========================================================================
     MỤC 3: BỘ LỌC TÌM KIẾM THÔNG MINH
     ========================================================================= -->
<div class="filter-card">
    <form action="<?php echo BASE_URL; ?>index.php" method="GET">
        <div class="filter-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">🔍 Tên món / Quán ăn:</label>
                <input type="text" name="keyword" class="form-control" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Ví dụ: Bánh Croissant, Cơm tấm...">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">📍 Quận tại Đà Nẵng:</label>
                <select name="district" class="form-control">
                    <option value="">-- Tất cả các quận --</option>
                    <?php
                    $all_districts = ['Hải Châu', 'Thanh Khê', 'Sơn Trà', 'Ngũ Hành Sơn', 'Liên Chiểu', 'Cẩm Lệ', 'Hòa Vang'];
                    foreach ($all_districts as $d) {
                        $sel = ($district === $d) ? 'selected' : '';
                        echo "<option value=\"" . htmlspecialchars($d) . "\" $sel>" . htmlspecialchars($d) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">🍰 Danh mục ẩm thực:</label>
                <select name="type" class="form-control">
                    <option value="">-- Tất cả loại hình --</option>
                    <?php
                    $all_types = ['Bánh ngọt & Bánh mì', 'Cơm & Bún phở', 'Trà sữa & Cafe', 'Món tươi & Salad', 'Đồ chay', 'Thức ăn nhanh'];
                    foreach ($all_types as $t) {
                        $sel = ($type === $t) ? 'selected' : '';
                        echo "<option value=\"" . htmlspecialchars($t) . "\" $sel>" . htmlspecialchars($t) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">💰 Mức giá mong muốn:</label>
                <select name="price_range" class="form-control">
                    <option value="">-- Mọi mức giá --</option>
                    <option value="under_25" <?php echo ($price_range === 'under_25') ? 'selected' : ''; ?>>Dưới 25.000 đ (Siêu rẻ)</option>
                    <option value="25_35" <?php echo ($price_range === '25_35') ? 'selected' : ''; ?>>25.000 đ - 35.000 đ</option>
                    <option value="35_50" <?php echo ($price_range === '35_50') ? 'selected' : ''; ?>>35.000 đ - 50.000 đ</option>
                    <option value="above_50" <?php echo ($price_range === 'above_50') ? 'selected' : ''; ?>>Trên 50.000 đ</option>
                </select>
            </div>

            <div style="display: flex; gap: 8px; align-items: flex-end;">
                <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Lọc Túi</button>
                <?php if (!empty($district) || !empty($type) || !empty($keyword)): ?>
                    <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-outline" style="padding: 12px 18px;">Xóa Lọc</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- =========================================================================
     MỤC 4: DANH SÁCH TÚI ĐANG MỞ BÁN (MUA NGAY - TRỪ KHO TỨC THÌ)
     ========================================================================= -->
<div style="margin: 36px 0 20px 0; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #a7f3d0; padding-bottom: 12px;">
    <div>
        <h2 style="color: var(--primary-dark); font-size: 23px; display: inline-flex; align-items: center; gap: 10px; font-weight: 800;">
            <span>🔥 TÚI BẤT NGỜ ĐANG MỞ BÁN</span>
            <span class="badge badge-success" style="font-size: 13px; font-weight: 800;"><?php echo count($bags_open); ?> túi sẵn sàng</span>
        </h2>
        <p style="font-size: 13.5px; color: var(--text-muted); margin-top: 4px;">
            Đã đến khung giờ mở bán! Đặt mua ngay trước khi hết hàng, kho sẽ tự động trừ ngay tức thì.
        </p>
    </div>
</div>

<?php if (empty($bags_open)): ?>
    <div class="card" style="text-align: center; padding: 40px; background: #ffffff;">
        <span style="font-size: 40px; display: block; margin-bottom: 10px;">⏳</span>
        <p style="color: var(--text-muted); font-size: 15px;">Hiện tại các quán đang chuẩn bị hàng cho ca tối. Hãy xem danh sách "Sắp mở bán" bên dưới để hẹn giờ săn túi nhé!</p>
    </div>
<?php else: ?>
    <div class="bag-grid">
        <?php foreach ($bags_open as $b): 
            $discount_pct = round((1 - ($b['web_price'] / $b['original_price'])) * 100);
            $img_src = !empty($b['image']) ? BASE_URL . 'uploads/' . htmlspecialchars($b['image']) : BASE_URL . 'uploads/bag_banh_ngot.jpg';
            $shop_rate = get_shop_rating($conn, $b['shop_id']);
        ?>
            <div class="bag-card">
                <div class="bag-image-box">
                    <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($b['name']); ?>">
                    <span class="bag-type-tag"><?php echo htmlspecialchars($b['shop_type']); ?></span>
                    <span class="bag-discount-tag">-<?php echo $discount_pct; ?>%</span>
                    <span class="bag-badge-state">
                        <span class="pulse-dot"></span> Còn <?php echo $b['stock']; ?> suất
                    </span>
                </div>

                <div class="bag-content">
                    <div class="bag-shop-info">
                        <a href="<?php echo BASE_URL; ?>quan_chi_tiet.php?id=<?php echo $b['shop_id']; ?>" class="bag-shop-name" style="text-decoration: none; color: inherit;">
                            🏪 <?php echo htmlspecialchars($b['shop_name']); ?> <span style="color: #f59e0b; font-size: 11px;">⭐<?php echo $shop_rate['avg']; ?></span>
                        </a>
                        <span class="bag-district-pill">Q. <?php echo htmlspecialchars($b['shop_district']); ?></span>
                    </div>

                    <h3 class="bag-title"><?php echo htmlspecialchars($b['name']); ?></h3>

                    <div class="bag-prices">
                        <span class="original-price"><?php echo format_money($b['original_price']); ?></span>
                        <span class="web-price"><?php echo format_money($b['web_price']); ?></span>
                    </div>

                    <div class="bag-time-strip">
                        <div>🕒 <strong>Khung giờ nhận:</strong> <?php echo date('H:i', strtotime($b['pickup_start'])); ?> - <?php echo date('H:i d/m', strtotime($b['pickup_end'])); ?></div>
                        <div style="margin-top: 3px;">⛔ <strong>Quán đóng cửa:</strong> <?php echo format_datetime($b['close_at']); ?></div>
                    </div>

                    <div style="margin-top: auto;">
                        <a href="<?php echo BASE_URL; ?>tui_chi_tiet.php?id=<?php echo $b['id']; ?>" class="btn btn-primary btn-block">
                            Chốt Đơn Ngay (<?php echo format_money($b['web_price']); ?>) &rarr;
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- =========================================================================
     MỤC 5: DANH SÁCH TÚI SẮP MỞ BÁN CUỐI NGÀY (HẸN GIỜ SĂN TÚI)
     ========================================================================= -->
<div style="margin: 46px 0 20px 0; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #fed7aa; padding-bottom: 12px;">
    <div>
        <h2 style="color: var(--secondary-color); font-size: 23px; display: inline-flex; align-items: center; gap: 10px; font-weight: 800;">
            <span>⏳ SẮP MỞ BÁN CUỐI NGÀY</span>
            <span class="badge badge-warning" style="font-size: 13px; font-weight: 800;"><?php echo count($bags_upcoming); ?> túi sắp mở</span>
        </h2>
        <p style="font-size: 13.5px; color: var(--text-muted); margin-top: 4px;">
            Các túi được lên lịch mở bán buổi tối. Bạn có thể xem trước để chuẩn bị săn túi đúng giờ!
        </p>
    </div>
</div>

<?php if (empty($bags_upcoming)): ?>
    <div class="card" style="text-align: center; padding: 35px; background: #ffffff;">
        <p style="color: var(--text-muted);">Không có túi nào đang chờ mở bán lúc này.</p>
    </div>
<?php else: ?>
    <div class="bag-grid">
        <?php foreach ($bags_upcoming as $b): 
            $discount_pct = round((1 - ($b['web_price'] / $b['original_price'])) * 100);
            $img_src = !empty($b['image']) ? BASE_URL . 'uploads/' . htmlspecialchars($b['image']) : BASE_URL . 'uploads/bag_banh_ngot.jpg';
            $rem_mins = get_remaining_minutes($b['open_at']);
        ?>
            <div class="bag-card" style="border-color: #fed7aa;">
                <div class="bag-image-box">
                    <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($b['name']); ?>" style="filter: brightness(0.94);">
                    <span class="bag-type-tag"><?php echo htmlspecialchars($b['shop_type']); ?></span>
                    <span class="bag-discount-tag">-<?php echo $discount_pct; ?>%</span>
                    <span class="bag-badge-state" style="background: rgba(234, 88, 12, 0.9);">
                        ⏳ Mở sau <?php echo $rem_mins; ?> phút
                    </span>
                </div>

                <div class="bag-content">
                    <div class="bag-shop-info">
                        <span class="bag-shop-name">
                            🏪 <?php echo htmlspecialchars($b['shop_name']); ?>
                        </span>
                        <span class="bag-district-pill">Q. <?php echo htmlspecialchars($b['shop_district']); ?></span>
                    </div>

                    <h3 class="bag-title"><?php echo htmlspecialchars($b['name']); ?></h3>

                    <div class="bag-prices">
                        <span class="original-price"><?php echo format_money($b['original_price']); ?></span>
                        <span class="web-price"><?php echo format_money($b['web_price']); ?></span>
                    </div>

                    <div class="bag-time-strip" style="background: #fffbeb; border-color: #fde68a;">
                        <div>⏱ <strong>Mở bán lúc:</strong> <?php echo format_datetime($b['open_at']); ?></div>
                        <div style="margin-top: 3px;">📦 <strong>Số lượng dự kiến:</strong> <?php echo $b['stock']; ?> suất</div>
                    </div>

                    <div style="margin-top: auto;">
                        <a href="<?php echo BASE_URL; ?>tui_chi_tiet.php?id=<?php echo $b['id']; ?>" class="btn btn-outline btn-block">
                            Xem Chi Tiết & Chờ Mở Bán &rarr;
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- =========================================================================
     MỤC 6: TRỰC QUAN HÓA CƠ CHẾ BẢO VỆ 2 CHIỀU & TRỌNG TÀI ADMIN
     ========================================================================= -->
<div class="card" style="margin-top: 48px; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%); border: 2px solid #86efac; padding: 36px 30px;">
    <div style="text-align: center; margin-bottom: 28px;">
        <span class="badge badge-success" style="font-size: 13px; padding: 6px 18px; font-weight: 800;">
            🛡️ TIÊU CHUẨN AN TOÀN NỀN TẢNG
        </span>
        <h2 style="color: #064e3b; font-size: 26px; font-weight: 900; margin-top: 10px; letter-spacing: -0.5px;">
            CƠ CHẾ TRỌNG TÀI & TẠM GIỮ TIỀN ESCROW 2 CHIỀU
        </h2>
        <p style="color: var(--text-muted); font-size: 14.5px; max-width: 740px; margin: 8px auto 0 auto; line-height: 1.6;">
            Hệ thống giải quyết triệt để nỗi lo "treo đầu dê bán thịt chó" của quán và ngăn chặn hành vi gian lận trục lợi từ sinh viên thông qua Trọng tài Admin.
        </p>
    </div>

    <div class="form-row">
        <div class="form-col" style="background: white; padding: 24px; border-radius: 18px; border: 1px solid #d1fae5; box-shadow: var(--shadow-sm);">
            <div style="font-size: 30px; margin-bottom: 12px;">🛡️</div>
            <h4 style="color: #047857; font-size: 18px; font-weight: 800; margin-bottom: 10px;">1. Bảo Vệ Sinh Viên</h4>
            <p style="font-size: 14px; line-height: 1.65; color: #475569;">
                Tiền đặt túi được <strong>tạm giữ 45 phút</strong> sau khi nhận hàng. Nếu đồ ăn có dấu hiệu ôi thiu, hỏng mốc hoặc bị độn món giá trị thấp, sinh viên bấm <strong>"Báo cáo khiếu nại"</strong> kèm ảnh bằng chứng. Admin sẽ hoàn trả 100% tiền và phạt quán vi phạm.
            </p>
        </div>

        <div class="form-col" style="background: white; padding: 24px; border-radius: 18px; border: 1px solid #fed7aa; box-shadow: var(--shadow-sm);">
            <div style="font-size: 30px; margin-bottom: 12px;">⚖️</div>
            <h4 style="color: #ea580c; font-size: 18px; font-weight: 800; margin-bottom: 10px;">2. Bảo Vệ Chủ Quán</h4>
            <p style="font-size: 14px; line-height: 1.65; color: #475569;">
                Hệ thống <strong>không tự ý hoàn tiền</strong> một phía khi có khiếu nại. Chủ quán có 48 giờ để gửi ảnh đối chứng và giải trình. Nếu sinh viên vu khống, tiền sẽ chuyển cho quán, sinh viên bị phạt cảnh cáo (đủ 3 lần cảnh cáo sẽ khóa tài khoản vĩnh viễn).
            </p>
        </div>

        <div class="form-col" style="background: white; padding: 24px; border-radius: 18px; border: 1px solid #bae6fd; box-shadow: var(--shadow-sm);">
            <div style="font-size: 30px; margin-bottom: 12px;">⏱️</div>
            <h4 style="color: #0284c7; font-size: 18px; font-weight: 800; margin-bottom: 10px;">3. Xử Lý Khách Vắng Mặt</h4>
            <p style="font-size: 14px; line-height: 1.65; color: #475569;">
                Sinh viên được hủy đơn hoàn tiền 100% trước giờ đóng cửa 90 phút. Nếu không đến lấy trước giờ đóng cửa, đơn chuyển sang <strong>Hủy vắng mặt</strong> và tiền bồi thường sẽ chuyển thẳng cho quán để bù đắp chi phí nguyên liệu.
            </p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 24px;">
        <a href="<?php echo BASE_URL; ?>gioi_thieu.php" class="btn btn-outline" style="font-size: 14px; padding: 12px 26px;">
            Xem chi tiết Đề Cương Nghiệp Vụ & Escrow &rarr;
        </a>
    </div>
</div>

<!-- =========================================================================
     MỤC 7: CÁC CỬA HÀNG ĐỐI TÁC TIÊU BIỂU TẠI ĐÀ NẴNG
     ========================================================================= -->
<div style="margin: 46px 0 24px 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="color: var(--text-dark); font-size: 22px; font-weight: 800;">🏪 CỬA HÀNG ĐỐI TÁC TIÊU BIỂU TẠI ĐÀ NẴNG</h2>
        <a href="<?php echo BASE_URL; ?>doi_tac.php" style="color: var(--primary-color); text-decoration: none; font-weight: 700; font-size: 14px;">
            Xem tất cả quán đối tác &rarr;
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
        <div class="card" style="padding: 22px; text-align: center; margin-bottom: 0;">
            <div style="font-size: 40px; margin-bottom: 10px;">🥐</div>
            <h4 style="color: var(--text-dark); font-size: 17px; font-weight: 800;">Tiệm Bánh BonBon</h4>
            <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 10px 0;">123 Lê Duẩn, Q. Hải Châu</p>
            <span class="badge badge-info">Bánh ngọt & Bánh mì</span>
        </div>

        <div class="card" style="padding: 22px; text-align: center; margin-bottom: 0;">
            <div style="font-size: 40px; margin-bottom: 10px;">🍛</div>
            <h4 style="color: var(--text-dark); font-size: 17px; font-weight: 800;">Cơm Tấm Sườn Cây</h4>
            <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 10px 0;">45 Nguyễn Văn Linh, Q. Thanh Khê</p>
            <span class="badge badge-warning">Cơm & Bún phở</span>
        </div>

        <div class="card" style="padding: 22px; text-align: center; margin-bottom: 0;">
            <div style="font-size: 40px; margin-bottom: 10px;">🧋</div>
            <h4 style="color: var(--text-dark); font-size: 17px; font-weight: 800;">Matcha Mori Bakery</h4>
            <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 10px 0;">88 Võ Nguyên Giáp, Q. Sơn Trà</p>
            <span class="badge badge-purple">Trà sữa & Cafe</span>
        </div>

        <div class="card" style="padding: 22px; text-align: center; margin-bottom: 0;">
            <div style="font-size: 40px; margin-bottom: 10px;">🥗</div>
            <h4 style="color: var(--text-dark); font-size: 17px; font-weight: 800;">Ẩm Thực Chay An Lạc</h4>
            <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 10px 0;">12 Ngũ Hành Sơn, Q. Ngũ Hành Sơn</p>
            <span class="badge badge-success">Đồ chay thanh tịnh</span>
        </div>
    </div>
</div>

<!-- =========================================================================
     MỤC 8: ĐÁNH GIÁ & CẢM NHẬN TỪ SINH VIÊN ĐÀ NẴNG
     ========================================================================= -->
<div class="card" style="margin-top: 36px; background: #ffffff;">
    <h3 style="color: var(--text-dark); margin-bottom: 22px; font-size: 20px; font-weight: 800; text-align: center;">
        💬 CẢM NHẬN CỦA SINH VIÊN TẠI CÁC TRƯỜNG ĐẠI HỌC ĐÀ NẴNG
    </h3>

    <div class="form-row">
        <div class="form-col" style="background: #f8fafc; border: 1px solid var(--border-color); padding: 20px; border-radius: 16px; font-size: 14px;">
            <div style="color: #f59e0b; margin-bottom: 6px; font-size: 15px;">★★★★★</div>
            <p style="color: #475569; font-style: italic; line-height: 1.6;">
                "Tối học xong ghé BonBon lấy túi bánh Croissant giá chỉ 55k mà được tận 4 cái to bự, ăn sáng hôm sau vẫn thơm phức. Sinh viên tiết kiệm được bao nhiêu tiền ăn!"
            </p>
            <div style="margin-top: 10px; font-weight: 800; color: #047857;">— Nguyễn Văn An (SV ĐH Bách Khoa ĐN)</div>
        </div>

        <div class="form-col" style="background: #f8fafc; border: 1px solid var(--border-color); padding: 20px; border-radius: 16px; font-size: 14px;">
            <div style="color: #f59e0b; margin-bottom: 6px; font-size: 15px;">★★★★★</div>
            <p style="color: #475569; font-style: italic; line-height: 1.6;">
                "Thích nhất là cơ chế tiền bị tạm giữ 45 phút, mang túi về kiểm tra thấy đồ tươi sạch mới yên tâm bấm 'Tôi hài lòng'. Rất công bằng và bảo vệ người dùng!"
            </p>
            <div style="margin-top: 10px; font-weight: 800; color: #047857;">— Trần Thị Mai (SV ĐH Kinh Tế ĐN)</div>
        </div>

        <div class="form-col" style="background: #f8fafc; border: 1px solid var(--border-color); padding: 20px; border-radius: 16px; font-size: 14px;">
            <div style="color: #f59e0b; margin-bottom: 6px; font-size: 15px;">★★★★★</div>
            <p style="color: #475569; font-style: italic; line-height: 1.6;">
                "Mô hình túi mù này cực hay, vừa bảo vệ môi trường chống lãng phí đồ ăn cuối ngày, vừa giải quyết được bữa tối ngon bổ rẻ cho sinh viên VKU tụi mình."
            </p>
            <div style="margin-top: 10px; font-weight: 800; color: #047857;">— Lê Hoàng Phúc (SV VKU Đà Nẵng)</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
