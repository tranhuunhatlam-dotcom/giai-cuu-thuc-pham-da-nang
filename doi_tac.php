<?php
// FILE: doi_tac.php
// Mục đích: Danh sách các cửa hàng, tiệm bánh, cafe đối tác giải cứu thực phẩm tại các quận Đà Nẵng, hiển thị đánh giá sao và liên kết chi tiết

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$filter_district = trim($_GET['district'] ?? '');

$sql = "SELECT s.*, u.name AS owner_name,
               (SELECT COUNT(*) FROM bags WHERE shop_id = s.id AND close_at > NOW()) AS active_bags_count
        FROM shops s
        JOIN users u ON s.owner_id = u.id
        WHERE s.status = 'da_duyet'";

if (!empty($filter_district)) {
    $sql .= " AND s.district = '" . mysqli_real_escape_string($conn, $filter_district) . "'";
}
$sql .= " ORDER BY s.id ASC";

$res = mysqli_query($conn, $sql);
$shops = [];
while ($row = mysqli_fetch_assoc($res)) {
    $shops[] = $row;
}

$page_title = 'Cửa hàng đối tác tại Đà Nẵng';
require_once __DIR__ . '/header.php';
?>

<div style="margin-bottom: 24px;">
    <div class="hero-wrapper" style="background: radial-gradient(circle at top right, rgba(234, 88, 12, 0.25), transparent 50%), linear-gradient(135deg, #7c2d12 0%, #c2410c 45%, #ea580c 100%); padding: 36px 32px; margin-bottom: 24px;">
        <span class="badge" style="background: rgba(255,255,255,0.22); color: white; font-size: 13px; padding: 6px 14px; border-radius: 20px; font-weight: 800; margin-bottom: 12px;">
            🏪 MẠNG LƯỚI F&B ĐỒNG HÀNH SỐNG XANH
        </span>
        <h1 style="font-size: 30px; font-weight: 900; margin-top: 8px; letter-spacing: -0.6px;">
            MẠNG LƯỚI ĐỐI TÁC CỨU TRỢ THỰC PHẨM ĐÀ NẴNG
        </h1>
        <p style="font-size: 15px; color: #ffedd5; max-width: 720px; margin-top: 8px;">
            Đồng hành cùng các tiệm bánh tươi, quán ăn uy tín tại Hải Châu, Thanh Khê, Sơn Trà, Ngũ Hành Sơn, Liên Chiểu... cam kết thực phẩm sạch ngon 100%.
        </p>
    </div>

    <!-- Bộ lọc theo quận -->
    <div class="filter-card" style="padding: 16px 20px; margin-bottom: 28px;">
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <label style="font-weight: 800; font-size: 14px; color: var(--text-dark);">📍 Chọn quận Đà Nẵng:</label>
            <a href="<?php echo BASE_URL; ?>doi_tac.php" class="btn btn-sm <?php echo empty($filter_district) ? 'btn-primary' : 'btn-outline'; ?>">Tất cả các quận</a>
            <?php
            $districts = ['Hải Châu', 'Thanh Khê', 'Sơn Trà', 'Ngũ Hành Sơn', 'Liên Chiểu', 'Cẩm Lệ', 'Hòa Vang'];
            foreach ($districts as $d):
                $active = ($filter_district === $d);
            ?>
                <a href="<?php echo BASE_URL; ?>doi_tac.php?district=<?php echo urlencode($d); ?>" class="btn btn-sm <?php echo $active ? 'btn-primary' : 'btn-outline'; ?>">
                    <?php echo htmlspecialchars($d); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Danh sách cửa hàng đối tác -->
    <?php if (empty($shops)): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <p style="color: var(--text-muted);">Chưa có cửa hàng nào tại quận này.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px;">
            <?php foreach ($shops as $s): 
                $rating_data = get_shop_rating($conn, $s['id']);
                $shop_img = !empty($s['image']) ? BASE_URL . 'uploads/' . htmlspecialchars($s['image']) : BASE_URL . 'uploads/bag_banh_ngot.jpg';
            ?>
                <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; border-radius: 22px;">
                    <div style="height: 160px; background: #e0f2f1; position: relative; overflow: hidden;">
                        <img src="<?php echo $shop_img; ?>" alt="<?php echo htmlspecialchars($s['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <span class="badge" style="position: absolute; top: 12px; right: 12px; background: rgba(15, 23, 42, 0.85); color: white; font-size: 11.5px; backdrop-filter: blur(6px);">
                            <?php echo htmlspecialchars($s['type']); ?>
                        </span>
                    </div>

                    <div style="padding: 22px; flex: 1; display: flex; flex-direction: column;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span class="badge badge-info" style="font-size: 11.5px;">
                                📍 Q. <?php echo htmlspecialchars($s['district']); ?>
                            </span>
                            <span style="font-size: 13px; color: #f59e0b; font-weight: 800;">
                                ⭐ <?php echo $rating_data['avg']; ?> (<?php echo $rating_data['total']; ?>)
                            </span>
                        </div>

                        <h3 style="font-size: 19px; font-weight: 800; color: var(--text-dark); margin-bottom: 6px; letter-spacing: -0.3px;">
                            <a href="<?php echo BASE_URL; ?>quan_chi_tiet.php?id=<?php echo $s['id']; ?>" style="color: inherit; text-decoration: none;">
                                <?php echo htmlspecialchars($s['name']); ?>
                            </a>
                        </h3>
                        <p style="font-size: 13.5px; color: var(--text-muted); margin-bottom: 16px; line-height: 1.5;">
                            <?php echo htmlspecialchars($s['address']); ?>
                        </p>

                        <div style="margin-top: auto; display: flex; gap: 8px;">
                            <a href="<?php echo BASE_URL; ?>quan_chi_tiet.php?id=<?php echo $s['id']; ?>" class="btn btn-outline btn-block btn-sm" style="font-weight: 800;">
                                Xem Hồ Sơ & Đánh Giá &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
