<?php
// FILE: vinh_danh.php
// Mục đích: Bảng Vinh Danh Sống Xanh (Leaderboard), Chỉ số Môi trường ESG (CO2, kg thực phẩm, nước sạch) và Tôn vinh Sinh viên & Quán đối tác

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// 1. Thống kê toàn hệ thống
$sql_total_rescued = "SELECT COUNT(*) AS total FROM orders WHERE status IN ('hoan_thanh', 'chuyen_tien_quan')";
$total_rescued = mysqli_fetch_assoc(mysqli_query($conn, $sql_total_rescued))['total'] ?? 0;

$sql_saved_money = "SELECT COALESCE(SUM(b.original_price - o.price), 0) AS total_saved 
                    FROM orders o 
                    JOIN bags b ON o.bag_id = b.id 
                    WHERE o.status IN ('hoan_thanh', 'chuyen_tien_quan')";
$total_saved = mysqli_fetch_assoc(mysqli_query($conn, $sql_saved_money))['total_saved'] ?? 0;

$eco_impact = calculate_eco_impact($total_rescued);

// 2. Lấy Top 5 sinh viên giải cứu nhiều nhất
$top_students = get_leaderboard_students($conn, 5);

// 3. Lấy Top 5 quán giải cứu nhiều nhất
$top_shops = get_leaderboard_shops($conn, 5);

$page_title = 'Bảng Vinh Danh Sống Xanh & Tác Động Môi Trường';
require_once __DIR__ . '/header.php';
?>

<!-- Banner vinh danh sống xanh -->
<div class="hero-wrapper" style="background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.3), transparent 60%), linear-gradient(135deg, #064e3b 0%, #065f46 40%, #047857 100%); margin-bottom: 30px;">
    <div style="max-width: 820px;">
        <span class="badge" style="background: rgba(255,255,255,0.2); color: white; font-size: 13px; padding: 6px 16px; border-radius: 20px; font-weight: 800; margin-bottom: 12px;">
            🌱 PHÁT TRIỂN BỀN VỮNG - MỤC TIÊU SDG 12
        </span>
        <h1 style="font-size: 36px; font-weight: 900; line-height: 1.25; margin-bottom: 14px; letter-spacing: -0.8px;">
            BẢNG VINH DANH SỐNG XANH<br>
            <span class="hero-highlight">TÁC ĐỘNG MÔI TRƯỜNG TẠI ĐÀ NẴNG</span>
        </h1>
        <p style="font-size: 16px; line-height: 1.65; color: #e2e8f0; opacity: 0.95;">
            Mỗi túi bất ngờ được giải cứu không chỉ giúp sinh viên tiết kiệm chi phí sinh hoạt, mà còn trực tiếp ngăn chặn thực phẩm thừa bị phân hủy tạo ra khí mê-tan gây hiệu ứng nhà kính. Cùng nhìn lại những con số biết nói!
        </p>
    </div>
</div>

<!-- LƯỚI CHỈ SỐ MÔI TRƯỜNG ESG -->
<div style="margin-bottom: 40px;">
    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-dark); margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
        <span>🌍</span> Chỉ Số Tác Động Sinh Thái Tích Lũy
    </h2>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
        <!-- Thẻ 1: Khí CO2 tránh phát thải -->
        <div class="card" style="padding: 24px; text-align: center; border-radius: 22px; border-top: 5px solid #10b981; margin-bottom: 0;">
            <div style="font-size: 40px; margin-bottom: 6px;">🍃</div>
            <div style="font-size: 32px; font-weight: 900; color: #047857; letter-spacing: -0.5px;">
                <?php echo number_format($eco_impact['co2_kg'], 1, ',', '.'); ?> kg
            </div>
            <h4 style="font-size: 15px; color: var(--text-dark); margin: 4px 0 6px 0;">Khí CO2 Giảm Thiểu</h4>
            <small style="color: var(--text-muted); font-size: 12.5px;">Tránh hiệu ứng nhà kính từ rác thải thực phẩm</small>
        </div>

        <!-- Thẻ 2: Thực phẩm sạch đã cứu -->
        <div class="card" style="padding: 24px; text-align: center; border-radius: 22px; border-top: 5px solid #f59e0b; margin-bottom: 0;">
            <div style="font-size: 40px; margin-bottom: 6px;">🥖</div>
            <div style="font-size: 32px; font-weight: 900; color: #b45309; letter-spacing: -0.5px;">
                <?php echo number_format($eco_impact['food_kg'], 1, ',', '.'); ?> kg
            </div>
            <h4 style="font-size: 15px; color: var(--text-dark); margin: 4px 0 6px 0;">Thực Phẩm Đã Cứu Sạch</h4>
            <small style="color: var(--text-muted); font-size: 12.5px;">Tương đương <?php echo $eco_impact['bags']; ?> bữa ăn ngon cho sinh viên</small>
        </div>

        <!-- Thẻ 3: Nước sạch nông nghiệp bảo tồn -->
        <div class="card" style="padding: 24px; text-align: center; border-radius: 22px; border-top: 5px solid #0ea5e9; margin-bottom: 0;">
            <div style="font-size: 40px; margin-bottom: 6px;">💧</div>
            <div style="font-size: 32px; font-weight: 900; color: #0284c7; letter-spacing: -0.5px;">
                <?php echo number_format($eco_impact['water_liters'], 0, ',', '.'); ?> Lít
            </div>
            <h4 style="font-size: 15px; color: var(--text-dark); margin: 4px 0 6px 0;">Nước Sạch Bảo Tồn</h4>
            <small style="color: var(--text-muted); font-size: 12.5px;">Nước ảo dùng để sản xuất nông nghiệp</small>
        </div>

        <!-- Thẻ 4: Tiết kiệm tài chính sinh viên -->
        <div class="card" style="padding: 24px; text-align: center; border-radius: 22px; border-top: 5px solid #ea580c; margin-bottom: 0;">
            <div style="font-size: 40px; margin-bottom: 6px;">💰</div>
            <div style="font-size: 32px; font-weight: 900; color: #ea580c; letter-spacing: -0.5px;">
                <?php echo format_money($total_saved); ?>
            </div>
            <h4 style="font-size: 15px; color: var(--text-dark); margin: 4px 0 6px 0;">Tiết Kiệm Cho Sinh Viên</h4>
            <small style="color: var(--text-muted); font-size: 12.5px;">Chênh lệch giá mua trực tiếp so với giá gốc</small>
        </div>
    </div>
</div>

<!-- 2 CỘT BẢNG VINH DANH TOP SINH VIÊN VÀ QUÁN -->
<div class="form-row" style="margin-bottom: 40px;">
    <!-- Cột trái: Top sinh viên sống xanh -->
    <div class="form-col" style="min-width: 320px;">
        <div class="card" style="border-radius: 24px; height: 100%;">
            <div class="card-title" style="color: var(--primary-dark);">
                <span>🏆 Top Sinh Viên Giải Cứu</span>
                <span class="badge badge-success">Tháng này</span>
            </div>

            <?php if (empty($top_students)): ?>
                <p style="text-align: center; color: var(--text-muted); padding: 30px 0;">Chưa có dữ liệu bảng xếp hạng.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php 
                    $medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                    $idx = 0;
                    foreach ($top_students as $st): 
                        $medal = $medals[$idx] ?? '⭐';
                        $idx++;
                    ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid var(--border-color); padding: 14px 18px; border-radius: 16px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span style="font-size: 26px;"><?php echo $medal; ?></span>
                                <div>
                                    <strong style="color: var(--text-dark); font-size: 15px; display: block;"><?php echo htmlspecialchars($st['name']); ?></strong>
                                    <small style="color: var(--text-muted); font-size: 12px;"><?php echo htmlspecialchars($st['university'] ?? 'ĐH Đà Nẵng'); ?></small>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span class="badge badge-success" style="font-size: 13px; font-weight: 800;">
                                    <?php echo $st['rescued_count']; ?> túi
                                </span>
                                <div style="font-size: 11.5px; color: #047857; margin-top: 3px; font-weight: 700;">
                                    Tiết kiệm <?php echo format_money($st['total_saved']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cột phải: Top cửa hàng xanh giảm lãng phí -->
    <div class="form-col" style="min-width: 320px;">
        <div class="card" style="border-radius: 24px; height: 100%;">
            <div class="card-title" style="color: var(--secondary-color);">
                <span>🏪 Top Quán Đối Tác Xanh</span>
                <span class="badge badge-warning">TP. Đà Nẵng</span>
            </div>

            <?php if (empty($top_shops)): ?>
                <p style="text-align: center; color: var(--text-muted); padding: 30px 0;">Chưa có dữ liệu bảng xếp hạng.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php 
                    $medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];
                    $idx = 0;
                    foreach ($top_shops as $sh): 
                        $medal = $medals[$idx] ?? '⭐';
                        $idx++;
                    ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #fff7ed; border: 1px solid #fed7aa; padding: 14px 18px; border-radius: 16px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span style="font-size: 26px;"><?php echo $medal; ?></span>
                                <div>
                                    <a href="<?php echo BASE_URL; ?>quan_chi_tiet.php?id=<?php echo $sh['id']; ?>" style="color: var(--text-dark); font-size: 15px; font-weight: 800; text-decoration: none;">
                                        <?php echo htmlspecialchars($sh['name']); ?> &rarr;
                                    </a>
                                    <small style="color: #9a3412; font-size: 12px; display: block;">Q. <?php echo htmlspecialchars($sh['district']); ?> • <?php echo htmlspecialchars($sh['type']); ?></small>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span class="badge badge-secondary" style="font-size: 13px; font-weight: 800;">
                                    <?php echo $sh['rescued_count']; ?> túi
                                </span>
                                <div style="font-size: 11.5px; color: #c2410c; margin-top: 3px; font-weight: 700;">
                                    Thu về <?php echo format_money($sh['total_revenue']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Lời kêu gọi hành động -->
<div class="card" style="background: #f8fafc; border: 1px solid var(--border-color); text-align: center; padding: 36px 20px; border-radius: 24px;">
    <h3 style="color: var(--text-dark); font-size: 22px; font-weight: 800; margin-bottom: 8px;">
        Bạn Đã Sẵn Sàng Trở Thành "Dũng Sĩ Sống Xanh"?
    </h3>
    <p style="color: var(--text-muted); font-size: 14.5px; max-width: 600px; margin: 0 auto 20px auto;">
        Hãy cùng chúng mình săn những túi đồ ăn tươi ngon cuối ngày tại các quán cafe, tiệm bánh xung quanh trường của bạn!
    </p>
    <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-primary btn-lg" style="box-shadow: 0 6px 20px var(--primary-glow);">
        Săn Túi Bất Ngờ Ngay Bây Giờ &rarr;
    </a>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
