<?php
// FILE: quy_trinh.php
// Mục đích: Trình bày chi tiết 4 quy trình cốt lõi và chính sách vận hành của nền tảng

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$cancel_mins = (int)get_setting($conn, 'cancel_before_close_minutes', 90);
$hold_mins = (int)get_setting($conn, 'hold_minutes', 45);
$dispute_hours = (int)get_setting($conn, 'dispute_response_hours', 48);
$discount_min = (int)get_setting($conn, 'discount_min', 30);
$discount_max = (int)get_setting($conn, 'discount_max', 50);

$page_title = 'Quy trình hoạt động & Chính sách';
require_once __DIR__ . '/header.php';
?>

<div style="max-width: 950px; margin: 20px auto;">
    <div class="hero-banner" style="background: linear-gradient(135deg, #2e7d32, #00796b); padding: 35px 25px; text-align: center;">
        <h1 style="font-size: 28px; margin-bottom: 8px;">QUY TRÌNH NGHIỆP VỤ & CHÍNH SÁCH BẢO VỆ</h1>
        <p style="font-size: 15px; max-width: 700px; margin: 0 auto; opacity: 0.95;">
            Được xây dựng trên nguyên tắc minh bạch, tôn trọng quyền lợi của cả cửa hàng đối tác và sinh viên tại TP. Đà Nẵng.
        </p>
    </div>

    <!-- Mục 1: Phân định giá -->
    <div class="card" style="margin-top: 25px;">
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <div style="background: #e8f5e9; color: #2e7d32; font-size: 24px; font-weight: bold; width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                1
            </div>
            <div>
                <h3 style="color: #2e7d32; font-size: 19px; margin-bottom: 6px;">Đóng Gói & Phân Định Giá Bán (Chiết Khấu <?php echo $discount_min; ?>% - <?php echo $discount_max; ?>%)</h3>
                <p style="font-size: 14px; line-height: 1.7; color: #37474f;">
                    - Cuối ngày, các cửa hàng gom thực phẩm dư sạch thành các "Túi bất ngờ" và hẹn giờ mở bán trực tuyến trên website.<br>
                    - <strong>Quy tắc giá:</strong> Khách mua trực tiếp tại quán (offline) được chọn món theo sở thích cá nhân vẫn phải trả 100% giá gốc niêm yết. Mức giá giảm sâu độc quyền từ <strong><?php echo $discount_min; ?>% đến <?php echo $discount_max; ?>%</strong> chỉ áp dụng cho đơn đặt trên website vì người mua chấp nhận rủi ro nhận "túi mù" ngẫu nhiên.
                </p>
            </div>
        </div>
    </div>

    <!-- Mục 2: Đặt hàng & Trừ kho tức thì -->
    <div class="card">
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <div style="background: #fff3e0; color: #e65100; font-size: 24px; font-weight: bold; width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                2
            </div>
            <div>
                <h3 style="color: #e65100; font-size: 19px; margin-bottom: 6px;">Đặt Hàng & Trừ Tồn Kho Tức Thì (Triệt Tiêu Overselling)</h3>
                <p style="font-size: 14px; line-height: 1.7; color: #37474f;">
                    - Đúng giờ mở bán, sinh viên vào chốt đơn trực tiếp trên web.<br>
                    - <strong>Giao dịch nguyên tử:</strong> Hệ thống thực hiện câu lệnh trừ kho và trừ số dư ví sinh viên trong cùng một Transaction khép kín. Kho hàng được trừ ngay lập tức để giữ phần ăn đó, triệt tiêu hoàn toàn lỗi bán vượt quá số lượng.<br>
                    - Tiền thanh toán của sinh viên được <strong>tạm giữ an toàn trong Ví trung gian</strong>, đồng thời hệ thống cấp một mã nhận hàng 6 ký tự ngẫu nhiên duy nhất (UNIQUE).
                </p>
            </div>
        </div>
    </div>

    <!-- Mục 3: Bàn giao O2O -->
    <div class="card">
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <div style="background: #e1f5fe; color: #0277bd; font-size: 24px; font-weight: bold; width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                3
            </div>
            <div>
                <h3 style="color: #0277bd; font-size: 19px; margin-bottom: 6px;">Bàn Giao Online-to-Offline (O2O) & Giữ Tiền An Toàn</h3>
                <p style="font-size: 14px; line-height: 1.7; color: #37474f;">
                    - Sinh viên đến quán trước giờ đóng cửa và đưa mã nhận hàng 6 ký tự cho nhân viên.<br>
                    - Nhân viên quán gõ mã vào hệ thống để xác nhận bàn giao túi. Lúc này, <strong>kho không bị trừ thêm nữa</strong>.<br>
                    - Đơn hàng chuyển sang trạng thái <strong>Đã nhận</strong>, hệ thống giữ tiền thêm <strong><?php echo $hold_mins; ?> phút</strong> để sinh viên mang về kiểm tra chất lượng thực phẩm. Sinh viên có nút "Tôi hài lòng" để nhả tiền sớm cho quán bất cứ lúc nào.
                </p>
            </div>
        </div>
    </div>

    <!-- Mục 4: Xử lý rủi ro khách vắng mặt -->
    <div class="card">
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <div style="background: #f3e5f5; color: #6a1b9a; font-size: 24px; font-weight: bold; width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                4
            </div>
            <div>
                <h3 style="color: #6a1b9a; font-size: 19px; margin-bottom: 6px;">Chính Sách Hủy Đơn & Xử Lý "Khách Vắng Mặt" (No-Show)</h3>
                <p style="font-size: 14px; line-height: 1.7; color: #37474f;">
                    - <strong>Hủy đơn hợp lệ:</strong> Sinh viên được quyền bấm "Hủy đơn" nếu còn cách giờ quán đóng cửa ít nhất <strong><?php echo $cancel_mins; ?> phút</strong>. Hệ thống hoàn lại 100% tiền vào ví và tự động cộng lại tồn kho cho người khác mua.<br>
                    - <strong>Khách vắng mặt:</strong> Nếu sinh viên không hủy đơn mà cũng không đến quán lấy túi trước giờ đóng cửa, hệ thống sẽ tự động chuyển đơn sang <strong>Hủy vắng mặt</strong>. Tiền sẽ được <strong>chuyển cho quán để bồi thường chi phí chuẩn bị</strong>, và túi sẽ không cộng lại kho vì đồ ăn cuối ngày không thể bảo quản qua đêm.
                </p>
            </div>
        </div>
    </div>

    <!-- Mục 5: Trọng tài khiếu nại chất lượng -->
    <div class="card" style="border: 1px solid #ef5350; background: #fffaf0;">
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <div style="background: #ffebee; color: #c62828; font-size: 24px; font-weight: bold; width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                5
            </div>
            <div>
                <h3 style="color: #c62828; font-size: 19px; margin-bottom: 6px;">Trọng Tài Phân Xử Khiếu Nại Chất Lượng</h3>
                <p style="font-size: 14px; line-height: 1.7; color: #37474f;">
                    - <strong>Quyền khiếu nại của SV:</strong> Nếu túi thực phẩm ôi thiu, nấm mốc hoặc sai lệch nghiêm trọng, sinh viên gửi "Báo cáo gian lận" (bắt buộc chọn lý do và tải lên ảnh bằng chứng).<br>
                    - <strong>Quyền phản đối của Quán:</strong> Chủ quán có <strong><?php echo $dispute_hours; ?> giờ</strong> để gửi ảnh đối chứng và giải trình.<br>
                    - <strong>Phán quyết của Admin:</strong> Admin xem xét bằng chứng từ 2 phía:
                    <br>&nbsp;&nbsp;+ Nếu quán sai: Hoàn 100% tiền cho sinh viên, ghi nhận 1 vi phạm cho quán.
                    <br>&nbsp;&nbsp;+ Nếu sinh viên vu khống gian dối: Tiền chuyển cho quán, sinh viên bị tính 1 cảnh cáo (tích lũy 3 cảnh cáo sẽ bị khóa tài khoản vĩnh viễn).
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
