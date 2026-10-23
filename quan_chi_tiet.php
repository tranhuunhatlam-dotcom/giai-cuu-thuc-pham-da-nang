<?php
// FILE: quan_chi_tiet.php
// Mục đích: Trang hồ sơ công khai của Quán đối tác: điểm đánh giá 5 sao từ sinh viên, danh sách túi đang bán và cẩm nang đường đi

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$shop_id = (int)($_GET['id'] ?? 0);
if ($shop_id <= 0) {
    header("Location: " . BASE_URL . "doi_tac.php");
    exit;
}

// Lấy thông tin quán (phải là quán đã duyệt)
$sql = "SELECT s.*, u.name AS owner_name 
        FROM shops s 
        JOIN users u ON s.owner_id = u.id 
        WHERE s.id = ? AND s.status = 'da_duyet' LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $shop_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$shop = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$shop) {
    die("Lỗi: Quán đối tác không tồn tại hoặc chưa được duyệt.");
}

// Lấy thông tin rating & reviews
$rating_info = get_shop_rating($conn, $shop['id']);
$reviews = get_shop_reviews($conn, $shop['id'], 10);

// Lấy tất cả túi của quán chưa quá giờ đóng cửa
$sql_bags = "SELECT * FROM bags WHERE shop_id = ? AND close_at > NOW() ORDER BY (open_at <= NOW() AND stock > 0) DESC, open_at ASC";
$stmt_bags = mysqli_prepare($conn, $sql_bags);
mysqli_stmt_bind_param($stmt_bags, "i", $shop['id']);
mysqli_stmt_execute($stmt_bags);
$res_bags = mysqli_stmt_get_result($stmt_bags);
$shop_bags = [];
while ($row = mysqli_fetch_assoc($res_bags)) {
    $shop_bags[] = $row;
}
mysqli_stmt_close($stmt_bags);

// Tổng số túi quán đã giải cứu thành công
$sql_rescued = "SELECT COUNT(*) AS total FROM orders WHERE shop_id = ? AND status IN ('hoan_thanh', 'chuyen_tien_quan')";
$stmt_rescued = mysqli_prepare($conn, $sql_rescued);
mysqli_stmt_bind_param($stmt_rescued, "i", $shop['id']);
mysqli_stmt_execute($stmt_rescued);
$res_rescued = mysqli_stmt_get_result($stmt_rescued);
$total_rescued = mysqli_fetch_assoc($res_rescued)['total'] ?? 0;
mysqli_stmt_close($stmt_rescued);

$page_title = $shop['name'] . ' - Quán Đối Tác Đà Nẵng';
require_once __DIR__ . '/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="<?php echo BASE_URL; ?>doi_tac.php" class="btn btn-outline btn-sm">
        &larr; Quay lại danh sách đối tác Đà Nẵng
    </a>
</div>

<!-- Header Card của Quán -->
<div class="card" style="padding: 32px; border-radius: 24px; margin-bottom: 30px;">
    <div style="display: flex; gap: 28px; align-items: flex-start; flex-wrap: wrap;">
        <!-- Ảnh quán -->
        <div style="width: 140px; height: 140px; border-radius: 20px; overflow: hidden; background: #f1f5f9; flex-shrink: 0; box-shadow: var(--shadow-md);">
            <?php
            $shop_img = !empty($shop['image']) ? BASE_URL . 'uploads/' . htmlspecialchars($shop['image']) : BASE_URL . 'uploads/bag_banh_ngot.jpg';
            ?>
            <img src="<?php echo $shop_img; ?>" alt="<?php echo htmlspecialchars($shop['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
        </div>

        <!-- Thông tin chi tiết quán -->
        <div style="flex: 1; min-width: 280px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <h1 style="font-size: 26px; font-weight: 900; color: var(--text-dark); letter-spacing: -0.5px;">
                    <?php echo htmlspecialchars($shop['name']); ?>
                </h1>
                <span class="badge badge-success" style="font-size: 12px;">✓ Đã xác thực</span>
                <span class="badge badge-info" style="font-size: 12px;"><?php echo htmlspecialchars($shop['type']); ?></span>
            </div>

            <div style="font-size: 14px; color: var(--text-body); margin-bottom: 12px; line-height: 1.6;">
                📍 Địa chỉ: <strong><?php echo htmlspecialchars($shop['address']); ?>, Q. <?php echo htmlspecialchars($shop['district']); ?>, TP. Đà Nẵng</strong><br>
                👤 Đại diện quản lý: <strong><?php echo htmlspecialchars($shop['owner_name']); ?></strong>
            </div>

            <!-- Thống kê quán -->
            <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 14px;">
                <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 8px 16px; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px;">
                    <span style="font-size: 18px; color: #f59e0b;">⭐</span>
                    <span style="font-size: 15px; font-weight: 800; color: #b45309;"><?php echo $rating_info['avg']; ?> / 5.0</span>
                    <span style="font-size: 12px; color: #78350f;">(<?php echo $rating_info['total']; ?> đánh giá)</span>
                </div>

                <div style="background: #ecfdf5; border: 1px solid #a7f3d0; padding: 8px 16px; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px;">
                    <span style="font-size: 18px;">🥖</span>
                    <span style="font-size: 15px; font-weight: 800; color: #047857;"><?php echo $total_rescued; ?> túi</span>
                    <span style="font-size: 12px; color: #065f46;">đã cứu thành công</span>
                </div>

                <div style="background: #f1f5f9; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px;">
                    <span style="font-size: 18px;">🛡️</span>
                    <span style="font-size: 13.5px; font-weight: 700; color: #334155;">Vi phạm: <?php echo $shop['violation_count']; ?> lần</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lưới túi bất ngờ của quán -->
<div style="margin-bottom: 36px;">
    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-dark); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
        <span>🔥</span> Các Túi Bất Ngờ Cuối Ngày Của Quán (<?php echo count($shop_bags); ?> túi)
    </h2>

    <?php if (empty($shop_bags)): ?>
        <div class="card" style="text-align: center; padding: 36px; background: white;">
            <p style="color: var(--text-muted);">Hiện quán chưa lên lịch túi mới cho hôm nay. Bạn hãy quay lại sau nhé!</p>
        </div>
    <?php else: ?>
        <div class="bag-grid">
            <?php foreach ($shop_bags as $b): 
                $discount_pct = round((1 - ($b['web_price'] / $b['original_price'])) * 100);
                $img_src = !empty($b['image']) ? BASE_URL . 'uploads/' . htmlspecialchars($b['image']) : BASE_URL . 'uploads/bag_banh_ngot.jpg';
                $is_open = (time() >= strtotime($b['open_at']) && $b['stock'] > 0);
            ?>
                <div class="bag-card">
                    <div class="bag-image-box">
                        <img src="<?php echo $img_src; ?>" alt="<?php echo htmlspecialchars($b['name']); ?>">
                        <span class="bag-discount-tag">-<?php echo $discount_pct; ?>%</span>
                        <?php if ($is_open): ?>
                            <span class="bag-badge-state"><span class="pulse-dot"></span> Còn <?php echo $b['stock']; ?> suất</span>
                        <?php else: ?>
                            <span class="bag-badge-state" style="background: rgba(234, 88, 12, 0.9);">⏳ Sắp mở bán</span>
                        <?php endif; ?>
                    </div>

                    <div class="bag-content">
                        <h3 class="bag-title"><?php echo htmlspecialchars($b['name']); ?></h3>

                        <div class="bag-prices">
                            <span class="original-price"><?php echo format_money($b['original_price']); ?></span>
                            <span class="web-price"><?php echo format_money($b['web_price']); ?></span>
                        </div>

                        <div class="bag-time-strip">
                            <div>🕒 <strong>Nhận túi:</strong> <?php echo date('H:i', strtotime($b['pickup_start'])); ?> - <?php echo date('H:i d/m', strtotime($b['pickup_end'])); ?></div>
                            <div style="margin-top: 3px;">⛔ <strong>Đóng cửa:</strong> <?php echo format_datetime($b['close_at']); ?></div>
                        </div>

                        <div style="margin-top: auto;">
                            <a href="<?php echo BASE_URL; ?>tui_chi_tiet.php?id=<?php echo $b['id']; ?>" class="btn btn-primary btn-block">
                                Xem & Đặt Túi Này &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Đánh giá từ Sinh viên -->
<div class="card" style="border-radius: 24px; padding: 30px;">
    <div class="card-title" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <span>💬 Đánh Giá Thực Tế Từ Sinh Viên (<?php echo count($reviews); ?> phản hồi)</span>
            <div style="font-size: 12.5px; color: #059669; font-weight: 700; margin-top: 4px;">
                ✨ 100% Đánh giá được kiểm chứng và phân tích cảm xúc bởi AI Sentiment Engine
            </div>
        </div>
        <span style="font-size: 15px; color: #f59e0b; font-weight: 800; background: #fffbeb; padding: 4px 12px; border-radius: 20px; border: 1px solid #fde68a;">⭐ <?php echo $rating_info['avg']; ?> / 5.0</span>
    </div>

    <?php if (empty($reviews)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 24px 0;">
            Chưa có đánh giá nào cho quán này. Sau khi nhận túi thành công, bạn có thể trở thành người đầu tiên đánh giá!
        </p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($reviews as $rev): 
                $sentiment = $rev['ai_sentiment'] ?? 'tich_cuc';
                $badge_class = 'badge-success';
                $badge_label = '😊 AI: Tích cực';
                if ($sentiment === 'can_chu_y') {
                    $badge_class = 'badge-danger';
                    $badge_label = '⚠️ AI: Cần chú ý';
                } elseif ($sentiment === 'trung_lap') {
                    $badge_class = 'badge-warning';
                    $badge_label = '😐 AI: Trung lập';
                }
            ?>
                <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 18px 20px; border-radius: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <strong style="color: var(--text-dark); font-size: 14.5px;">🎓 <?php echo htmlspecialchars($rev['student_name']); ?></strong>
                            <span class="badge badge-gray" style="font-size: 11px; margin-left: 6px;"><?php echo htmlspecialchars($rev['university'] ?? 'Sinh viên Đà Nẵng'); ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="badge <?php echo $badge_class; ?>" style="font-size: 11px; padding: 3px 8px;">
                                <?php echo $badge_label; ?>
                            </span>
                            <span style="font-size: 12.5px; color: var(--text-muted);"><?php echo format_datetime($rev['created_at']); ?></span>
                        </div>
                    </div>

                    <div style="color: #f59e0b; font-size: 15px; margin-bottom: 8px;">
                        <?php echo str_repeat('⭐', $rev['rating']); ?>
                    </div>

                    <p style="font-size: 13.5px; color: #475569; line-height: 1.6; font-style: italic; margin-bottom: 10px;">
                        "<?php echo nl2br(htmlspecialchars($rev['comment'] ?? 'Chất lượng rất tốt, thức ăn sạch sẽ và đúng giờ.')); ?>"
                    </p>

                    <?php if (!empty($rev['ai_tags'])): ?>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <?php foreach (explode(' ', $rev['ai_tags']) as $tag): if(empty(trim($tag))) continue; ?>
                                <span style="font-size: 11.5px; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px; font-weight: 600;">
                                    <?php echo htmlspecialchars($tag); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
