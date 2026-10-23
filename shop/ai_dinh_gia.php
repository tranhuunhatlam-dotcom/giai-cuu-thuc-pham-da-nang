<?php
// FILE: shop/ai_dinh_gia.php
// Mục đích: Công cụ AI Định Giá Thông Minh & Dự Báo Tiêu Thụ Hàng Tồn dành riêng cho Chủ Quán
// Giúp quán tối ưu giá bán trong khung 30-50%, xả hết 100% túi bất ngờ trong vòng 30 phút

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../services/ai_service.php';

require_role('shop_owner');
$user = get_logged_in_user($conn);
$shop = get_shop_by_owner_id($conn, $user['id']);

if (!$shop) {
    die("Lỗi: Không tìm thấy quán của bạn.");
}

$food_type = trim($_POST['food_type'] ?? ($shop['type'] ?? 'Bánh ngọt & Bánh mì'));
$original_price = (float)($_POST['original_price'] ?? 80000);
$close_hour = (int)($_POST['close_hour'] ?? 22);
$stock = (int)($_POST['stock'] ?? 4);

$ai_calc = ai_shop_pricing_advisor($food_type, $original_price, $close_hour, $stock);

$page_title = 'AI Định Giá & Dự Báo Tiêu Thụ';
require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 950px; margin: 30px auto; padding: 0 15px;">
    <!-- Tiêu đề & Giới thiệu công nghệ -->
    <div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%); border-radius: 18px; padding: 30px 28px; color: #ffffff; margin-bottom: 25px; box-shadow: 0 10px 25px rgba(67, 56, 202, 0.25);">
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 8px;">
            <span style="font-size: 32px;">📈</span>
            <div>
                <span class="badge" style="background: rgba(165, 180, 252, 0.25); color: #c7d2fe; border: 1px solid #818cf8; font-size: 11px; padding: 3px 10px; border-radius: 20px;">
                    ✨ Smart Pricing & Clearance AI Engine
                </span>
                <h1 style="font-size: 24px; font-weight: 800; margin: 4px 0 0 0;">AI Định Giá & Dự Báo Tiêu Thụ Túi</h1>
            </div>
        </div>
        <p style="font-size: 14px; color: #e0e7ff; margin: 0; line-height: 1.6;">
            Thuật toán định giá động phân tích dựa trên loại ẩm thực, giá gốc, số lượng dư và giờ đóng cửa quán để đề xuất mức giá bán tối ưu (giảm 30% - 50%), đảm bảo xả sạch 100% thức ăn dư trước giờ tắt đèn.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
        <!-- Cột 1: Form Nhập Thông Số Của Quán -->
        <div class="card" style="border-radius: 16px; border: 1.5px solid #e2e8f0;">
            <h3 style="font-size: 16px; font-weight: 800; color: #1e293b; margin-top: 0; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
                <span>⚙️</span> Thông Số Thực Phẩm Cuối Ngày
            </h3>

            <form action="ai_dinh_gia.php" method="POST">
                <div style="margin-bottom: 14px;">
                    <label style="font-size: 13px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">
                        Loại hình ẩm thực của quán:
                    </label>
                    <select name="food_type" class="form-control" style="border-radius: 8px;">
                        <option value="Bánh ngọt & Bánh mì" <?= $food_type === 'Bánh ngọt & Bánh mì' ? 'selected' : '' ?>>Bánh ngọt & Bánh mì</option>
                        <option value="Cơm & Bún phở" <?= $food_type === 'Cơm & Bún phở' ? 'selected' : '' ?>>Cơm & Bún phở</option>
                        <option value="Trà sữa & Cafe" <?= $food_type === 'Trà sữa & Cafe' ? 'selected' : '' ?>>Trà sữa & Cafe</option>
                        <option value="Món chay" <?= $food_type === 'Món chay' ? 'selected' : '' ?>>Món ẩm thực chay</option>
                    </select>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 13px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">
                        Giá trị thực tế của các món trong túi (Giá gốc VNĐ):
                    </label>
                    <input type="number" name="original_price" step="5000" min="20000" value="<?= htmlspecialchars($original_price) ?>" class="form-control" style="border-radius: 8px;" required>
                    <small style="color: #64748b; font-size: 11.5px;">Tổng giá bán lẻ ban đầu tại quầy của toàn bộ món trong túi.</small>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 13px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">
                        Giờ quán dự kiến đóng cửa:
                    </label>
                    <select name="close_hour" class="form-control" style="border-radius: 8px;">
                        <option value="20" <?= $close_hour == 20 ? 'selected' : '' ?>>20:00 tối</option>
                        <option value="21" <?= $close_hour == 21 ? 'selected' : '' ?>>21:00 tối</option>
                        <option value="22" <?= $close_hour == 22 ? 'selected' : '' ?>>22:00 tối (Phổ biến)</option>
                        <option value="23" <?= $close_hour == 23 ? 'selected' : '' ?>>23:00 đêm</option>
                    </select>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="font-size: 13px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">
                        Số lượng túi muốn đóng gói:
                    </label>
                    <input type="number" name="stock" min="1" max="50" value="<?= htmlspecialchars($stock) ?>" class="form-control" style="border-radius: 8px;" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px; font-size: 14px; font-weight: 800; border-radius: 10px; background: #4f46e5; border: none;">
                    🔄 Tính Toán & Dự Báo Lại
                </button>
            </form>
        </div>

        <!-- Cột 2: Kết Quả Phân Tích & Đề Xuất Của AI -->
        <div class="card" style="border-radius: 16px; border: 2px solid #6366f1; background: #fdfcff;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #e0e7ff; padding-bottom: 10px;">
                <h3 style="font-size: 16px; font-weight: 800; color: #3730a3; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>✨</span> Khuyến Nghị Tối Ưu Từ AI
                </h3>
                <span class="badge badge-success" style="font-size: 11px;">Tự tin: <?= $ai_calc['success_rate'] ?>%</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                <div style="background: #eef2ff; border-radius: 12px; padding: 14px; text-align: center;">
                    <div style="font-size: 12px; color: #4338ca; font-weight: 700; margin-bottom: 4px;">MỨC GIÁ BÁN KHUYẾN NGHỊ</div>
                    <div style="font-size: 22px; font-weight: 800; color: #1e1b4b;">
                        <?= number_format($ai_calc['suggested_price'], 0, ',', '.') ?> đ
                    </div>
                    <div style="font-size: 12px; color: #059669; font-weight: 700;">
                        Giảm -<?= $ai_calc['discount_percent'] ?>% (Đạt chuẩn)
                    </div>
                </div>

                <div style="background: #f0fdf4; border-radius: 12px; padding: 14px; text-align: center;">
                    <div style="font-size: 12px; color: #15803d; font-weight: 700; margin-bottom: 4px;">DỰ BÁO TIÊU THỤ HẾT</div>
                    <div style="font-size: 22px; font-weight: 800; color: #14532d;">
                        ~<?= $ai_calc['clearance_minutes'] ?> phút
                    </div>
                    <div style="font-size: 12px; color: #16a34a; font-weight: 700;">
                        Tỷ lệ bán sạch: <?= $ai_calc['success_rate'] ?>%
                    </div>
                </div>
            </div>

            <!-- Tác động môi trường của mẻ túi này -->
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; font-size: 13px; color: #065f46;">
                🌱 <strong>Tác động xanh dự kiến:</strong> Bán hết <?= $stock ?> túi này giúp ngăn chặn khoảng <strong><?= $ai_calc['co2_saved_est'] ?> kg CO₂</strong> phát thải ra môi trường Đà Nẵng!
            </div>

            <!-- Câu mô tả AI Marketing sinh tự động -->
            <div style="margin-bottom: 20px;">
                <label style="font-size: 13px; font-weight: 700; color: #334155; display: block; margin-bottom: 6px;">
                    ✍️ Câu mô tả hấp dẫn do AI tạo sẵn cho sinh viên:
                </label>
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 14px; font-size: 13px; color: #334155; line-height: 1.6;">
                    <?= htmlspecialchars($ai_calc['generated_description']) ?>
                </div>
            </div>

            <!-- Nút Áp Dụng Ngay Vào Form Đăng Túi -->
            <form action="them_tui.php" method="GET">
                <input type="hidden" name="ai_name" value="Túi Bất Ngờ <?= htmlspecialchars($food_type) ?>">
                <input type="hidden" name="ai_desc" value="<?= htmlspecialchars($ai_calc['generated_description']) ?>">
                <input type="hidden" name="ai_orig" value="<?= $original_price ?>">
                <input type="hidden" name="ai_web" value="<?= $ai_calc['suggested_price'] ?>">
                <input type="hidden" name="ai_stock" value="<?= $stock ?>">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14.5px; font-weight: 800; border-radius: 10px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);">
                    👉 Áp Dụng Ngay Sang Form Đăng Bán Túi
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
