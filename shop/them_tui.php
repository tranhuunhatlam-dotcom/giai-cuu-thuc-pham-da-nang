<?php
// FILE: shop/them_tui.php
// Mục đích: Thêm túi bất ngờ mới, kiểm tra giảm giá 30-50% và ràng buộc thời gian hợp lệ

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

require_role('shop_owner');
$user = get_logged_in_user($conn);
$shop = get_shop_by_owner_id($conn, $user['id']);

if (!$shop) {
    die("Lỗi: Không tìm thấy quán của bạn.");
}

// Kiểm tra trạng thái quán đã được duyệt chưa
if ($shop['status'] !== 'da_duyet') {
    die("Thông báo: Quán của bạn chưa được Quản trị viên duyệt, chưa thể đăng bán túi mới.");
}

$errors = [];

// Lấy cấu hình tỷ lệ giảm giá từ settings
$discount_min = (float)get_setting($conn, 'discount_min', 30);
$discount_max = (float)get_setting($conn, 'discount_max', 50);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $original_price = (float)($_POST['original_price'] ?? 0);
    $web_price = (float)($_POST['web_price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $open_at = trim($_POST['open_at'] ?? '');
    $pickup_start = trim($_POST['pickup_start'] ?? '');
    $pickup_end = trim($_POST['pickup_end'] ?? '');
    $close_at = trim($_POST['close_at'] ?? '');

    // 1. Kiểm tra dữ liệu bắt buộc
    if (empty($name) || $original_price <= 0 || $web_price <= 0 || $stock <= 0) {
        $errors[] = 'Vui lòng nhập tên túi, giá gốc (>0), giá web (>0) và số lượng kho (>0).';
    }

    // 2. Validate tỷ lệ giảm giá từ 30% đến 50%
    if ($original_price > 0 && $web_price > 0) {
        if ($web_price >= $original_price) {
            $errors[] = 'Giá web phải nhỏ hơn giá gốc để hỗ trợ giải cứu thực phẩm.';
        } else {
            $percent = (1 - ($web_price / $original_price)) * 100;
            // Làm tròn 1 chữ số thập phân
            $percent_round = round($percent, 1);
            if ($percent_round < $discount_min || $percent_round > $discount_max) {
                $errors[] = "Theo quy định, giá bán trên web phải giảm từ {$discount_min}% đến {$discount_max}% so với giá gốc. Hiện tại mức giảm của bạn là {$percent_round}%.";
            }
        }
    }

    // 3. Validate thứ tự thời gian: open_at < pickup_start < pickup_end <= close_at
    if (empty($open_at) || empty($pickup_start) || empty($pickup_end) || empty($close_at)) {
        $errors[] = 'Vui lòng chọn đầy đủ 4 mốc thời gian (Mở bán, Bắt đầu nhận, Kết thúc nhận, Đóng cửa quán).';
    } else {
        $t_open = strtotime($open_at);
        $t_pick_start = strtotime($pickup_start);
        $t_pick_end = strtotime($pickup_end);
        $t_close = strtotime($close_at);

        if (!($t_open < $t_pick_start && $t_pick_start < $t_pick_end && $t_pick_end <= $t_close)) {
            $errors[] = 'Thời gian không hợp lệ! Quy tắc bắt buộc: Giờ mở bán < Bắt đầu nhận < Kết thúc nhận <= Giờ đóng cửa quán.';
        }
    }

    // 4. Xử lý upload ảnh túi
    $image_filename = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $up = upload_image_file($_FILES['image'], UPLOAD_DIR);
        if ($up['success']) {
            $image_filename = $up['filename'];
        } else {
            $errors[] = $up['error'];
        }
    }

    // 5. Thêm vào CSDL nếu không có lỗi
    if (empty($errors)) {
        $sql = "INSERT INTO bags (shop_id, name, description, image, original_price, web_price, stock, open_at, pickup_start, pickup_end, close_at, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssddissss", $shop['id'], $name, $description, $image_filename, $original_price, $web_price, $stock, $open_at, $pickup_start, $pickup_end, $close_at);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: " . BASE_URL . "shop/danh_sach_tui.php?added=1");
            exit;
        } else {
            $errors[] = 'Lỗi hệ thống khi thêm túi vào CSDL.';
            mysqli_stmt_close($stmt);
        }
    }
}

// Hỗ trợ điền tự động từ công cụ AI Định Giá
$init_name = $_POST['name'] ?? ($_GET['ai_name'] ?? 'Túi Bất Ngờ Cuối Ngày');
$init_desc = $_POST['description'] ?? ($_GET['ai_desc'] ?? '');
$init_orig = $_POST['original_price'] ?? ($_GET['ai_orig'] ?? '100000');
$init_web = $_POST['web_price'] ?? ($_GET['ai_web'] ?? '55000');
$init_stock = $_POST['stock'] ?? ($_GET['ai_stock'] ?? '5');

// Thiết lập giá trị gợi ý mặc định hôm nay cho tiện lợi
$today = date('Y-m-d');
$default_open = date('Y-m-d\TH:i', strtotime("$today 17:00"));
$default_start = date('Y-m-d\TH:i', strtotime("$today 19:00"));
$default_end = date('Y-m-d\TH:i', strtotime("$today 21:00"));
$default_close = date('Y-m-d\TH:i', strtotime("$today 21:30"));

$page_title = 'Thêm túi bất ngờ mới';
require_once __DIR__ . '/../header.php';
?>

<div style="max-width: 750px; margin: 20px auto;">
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
            <h2 class="card-title" style="margin-bottom: 0;">Thêm Túi Bất Ngờ Mới</h2>
            <a href="ai_dinh_gia.php" class="btn btn-outline" style="font-size: 13px; padding: 6px 14px; border-radius: 20px; border-color: #6366f1; color: #4338ca; font-weight: 700;">
                ✨ Hỏi AI Định Giá & Tạo Mô Tả
            </a>
        </div>

        <?php if (isset($_GET['ai_name'])): ?>
            <div style="background: #eef2ff; border: 1.5px solid #818cf8; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 13px; color: #3730a3;">
                🤖 <strong>AI Assistant:</strong> Đã tự động điền các thông số giá bán, số lượng và câu mô tả tối ưu từ công cụ AI Định Giá!
            </div>
        <?php endif; ?>

        <div class="notice-banner">
            <strong>Lưu ý nghiệp vụ cốt lõi:</strong><br>
            - Giá web bắt buộc giảm từ <strong><?php echo $discount_min; ?>%</strong> đến <strong><?php echo $discount_max; ?>%</strong> so với giá gốc niêm yết tại quán.<br>
            - Thứ tự thời gian: <strong>Mở bán &lt; Bắt đầu nhận &lt; Kết thúc nhận &le; Đóng cửa quán</strong>.
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin-left: 20px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>shop/them_tui.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Tên túi bất ngờ:</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($init_name); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Mô tả tổng quan về túi:</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Ví dụ: Gồm các loại bánh ngọt ngẫu nhiên nướng trong ngày (Croissant, Muffin...), vẫn giữ trọn vị ngon, đóng túi giấy sạch sẽ."><?php echo htmlspecialchars($init_desc); ?></textarea>
                <small class="form-text">Khách mua chấp nhận túi mù ngẫu nhiên, đổi lại giá rất tốt.</small>
            </div>

            <div class="form-row">
                <div class="form-col form-group">
                    <label class="form-label">Giá gốc tại quán (VNĐ):</label>
                    <input type="number" name="original_price" class="form-control" value="<?php echo htmlspecialchars($init_orig); ?>" step="1000" min="1000" required>
                    <small class="form-text">Giá nếu khách đến mua chọn món trực tiếp.</small>
                </div>
                <div class="form-col form-group">
                    <label class="form-label">Giá bán trên web (VNĐ):</label>
                    <input type="number" name="web_price" class="form-control" value="<?php echo htmlspecialchars($init_web); ?>" step="1000" min="1000" required>
                    <small class="form-text">Giảm từ <?php echo $discount_min; ?>% đến <?php echo $discount_max; ?>% (Ví dụ: gốc 100k -> web 50k - 70k).</small>
                </div>
                <div class="form-col form-group">
                    <label class="form-label">Số lượng túi mở bán:</label>
                    <input type="number" name="stock" class="form-control" value="<?php echo htmlspecialchars($init_stock); ?>" min="1" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col form-group">
                    <label class="form-label">1. Giờ mở bán online:</label>
                    <input type="datetime-local" name="open_at" class="form-control" value="<?php echo htmlspecialchars($_POST['open_at'] ?? $default_open); ?>" required>
                </div>
                <div class="form-col form-group">
                    <label class="form-label">2. Giờ bắt đầu nhận túi:</label>
                    <input type="datetime-local" name="pickup_start" class="form-control" value="<?php echo htmlspecialchars($_POST['pickup_start'] ?? $default_start); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-col form-group">
                    <label class="form-label">3. Giờ kết thúc nhận túi:</label>
                    <input type="datetime-local" name="pickup_end" class="form-control" value="<?php echo htmlspecialchars($_POST['pickup_end'] ?? $default_end); ?>" required>
                </div>
                <div class="form-col form-group">
                    <label class="form-label">4. Giờ đóng cửa quán:</label>
                    <input type="datetime-local" name="close_at" class="form-control" value="<?php echo htmlspecialchars($_POST['close_at'] ?? $default_close); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Hình ảnh minh họa túi (tùy chọn, tối đa 2MB, JPG/PNG/WEBP):</label>
                <input type="file" name="image" class="form-control" accept="image/png, image/jpeg, image/webp">
            </div>

            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">XÁC NHẬN TẠO TÚI</button>
                <a href="<?php echo BASE_URL; ?>shop/danh_sach_tui.php" class="btn btn-outline">Hủy bỏ</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
