<?php
// FILE: gioi_thieu.php
// Mục đích: Trang giới thiệu chi tiết mô hình Túi Bất Ngờ và Cơ chế trọng tài chống gian lận 2 chiều

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$page_title = 'Giới thiệu mô hình Túi Bất Ngờ & Cơ chế Trọng Tài';
require_once __DIR__ . '/header.php';
?>

<div style="max-width: 1000px; margin: 20px auto;">
    <!-- Banner Tiêu Đề -->
    <div class="hero-banner" style="background: linear-gradient(135deg, #1b5e20, #2e7d32, #388e3c); padding: 40px 30px; text-align: center;">
        <span class="badge" style="background: #fff3e0; color: #e65100; font-size: 13px; margin-bottom: 12px; padding: 6px 14px; border-radius: 20px;">
            🌱 GIẢI PHÁP TIÊN PHONG TẠI ĐÀ NẴNG
        </span>
        <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 12px;">MÔ HÌNH "TÚI BẤT NGỜ" LÀ GÌ?</h1>
        <p style="font-size: 16px; max-width: 750px; margin: 0 auto; opacity: 0.95;">
            Nền tảng kết nối các tiệm bánh, không gian cafe học tập và quán ăn tại TP. Đà Nẵng có thực phẩm dư sạch cuối ngày với sinh viên cần mua đồ ăn chất lượng với giá siêu tiết kiệm.
        </p>
    </div>

    <!-- 1. Vấn đề thực tiễn & Giải pháp -->
    <div class="card" style="margin-top: 25px;">
        <h2 style="color: var(--primary-color); margin-bottom: 15px; font-size: 22px;">
            1. Vấn Đề Thực Tiễn & Giá Trị Đem Lại
        </h2>
        <div class="form-row">
            <div class="form-col" style="background: #f1f8e9; padding: 20px; border-radius: 10px; border-left: 4px solid #2e7d32;">
                <h3 style="color: #2e7d32; font-size: 18px; margin-bottom: 8px;">🏪 Đối với Cửa Hàng & Tiệm Bánh</h3>
                <ul style="margin-left: 20px; font-size: 14px; line-height: 1.8; color: #37474f;">
                    <li>Mỗi cuối ngày, nhiều tiệm bánh tươi, quán ăn tại Đà Nẵng phải tiêu hủy lượng đồ ăn vẫn còn thơm ngon, sạch sẽ chỉ vì tiêu chuẩn bảo quản qua đêm.</li>
                    <li>Mô hình Túi Bất Ngờ giúp quán <strong>thu hồi vốn nguyên liệu</strong> thay vì vứt bỏ lãng phí.</li>
                    <li>Quảng bá thương hiệu quán đến hàng chục nghìn sinh viên các trường ĐH tại Đà Nẵng (Bách Khoa, Kinh Tế, Sư Phạm, VKU...).</li>
                </ul>
            </div>

            <div class="form-col" style="background: #fff3e0; padding: 20px; border-radius: 10px; border-left: 4px solid #e65100;">
                <h3 style="color: #e65100; font-size: 18px; margin-bottom: 8px;">🎓 Đối với Học Sinh, Sinh Viên</h3>
                <ul style="margin-left: 20px; font-size: 14px; line-height: 1.8; color: #37474f;">
                    <li>Được thưởng thức bánh ngọt cao cấp, cà phê, cơm suất tươi ngon với mức <strong>giảm giá sâu từ 30% đến 50%</strong>.</li>
                    <li>Chung tay bảo vệ môi trường, hình thành nếp sống văn minh: không lãng phí thức ăn.</li>
                    <li>Trải nghiệm cảm giác hào hứng khi "mở túi mù" ẩm thực mỗi buổi tối sau giờ học căng thẳng.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 2. TRỌNG TÂM: CƠ CHẾ BẢO VỆ 2 CHIỀU & TRỌNG TÀI ADMIN -->
    <div class="card" style="border: 2px solid #81c784; background: #ffffff;">
        <div style="text-align: center; margin-bottom: 25px;">
            <span class="badge badge-success" style="font-size: 13px; padding: 6px 14px;">BÀI TOÁN CỐT LÕI ĐƯỢC GIẢI QUYẾT</span>
            <h2 style="color: #1b5e20; font-size: 24px; margin-top: 8px;">
                CƠ CHẾ TẠM GIỮ (ESCROW) & BẢO VỆ 2 CHIỀU
            </h2>
            <p style="color: var(--text-muted); font-size: 14px; max-width: 700px; margin: 6px auto 0 auto;">
                Làm thế nào để vừa chống quán "treo đầu dê bán thịt chó", vừa chống sinh viên gian dối giả mạo bằng chứng?
            </p>
        </div>

        <div class="form-row" style="gap: 20px;">
            <!-- Chiều 1: Chống quán gian dối -->
            <div class="form-col" style="border: 1px solid #ffcdd2; background: #fffaf0; border-radius: 10px; padding: 20px;">
                <div style="font-size: 28px; margin-bottom: 8px;">🛡️</div>
                <h3 style="color: #c62828; font-size: 18px; margin-bottom: 10px;">
                    Chiều 1: Chống Cửa Hàng Gian Dối
                </h3>
                <p style="font-size: 13px; color: #d32f2f; font-weight: 600; margin-bottom: 8px;">
                    "Lỡ quán hứa hàng ngon nhưng giao đồ ôi thiu, nấm mốc hoặc chỉ có 1 cái bánh rẻ tiền thì sao?"
                </p>
                <div style="font-size: 14px; line-height: 1.7; color: #424242;">
                    <strong>Giải pháp hệ thống:</strong>
                    <ol style="margin-left: 20px; margin-top: 6px;">
                        <li><strong>Tiền bị tạm giữ:</strong> Tiền sinh viên trả không về tay chủ quán ngay mà được đóng băng trong Ví Trung Gian (Escrow).</li>
                        <li><strong>45 phút kiểm tra:</strong> Sau khi nhận túi tại quầy, sinh viên có thời gian ân hạn 45 phút mang về thưởng thức và kiểm tra chất lượng.</li>
                        <li><strong>Nút Báo cáo gian lận:</strong> Nếu phát hiện đồ ôi thiu, ẩm mốc, sinh viên bấm báo cáo và <strong>bắt buộc upload ảnh chụp bằng chứng thực tế</strong>.</li>
                        <li><strong>Khóa giao dịch:</strong> Toàn bộ số tiền bị phong tỏa. Nếu khiếu nại đúng, Admin hoàn 100% tiền cho sinh viên và quán bị phạt 1 điểm vi phạm chất lượng.</li>
                    </ol>
                </div>
            </div>

            <!-- Chiều 2: Chống sinh viên gian dối -->
            <div class="form-col" style="border: 1px solid #bbdefb; background: #f0f7ff; border-radius: 10px; padding: 20px;">
                <div style="font-size: 28px; margin-bottom: 8px;">⚖️</div>
                <h3 style="color: #0d47a1; font-size: 18px; margin-bottom: 10px;">
                    Chiều 2: Chống Sinh Viên Trục Lợi
                </h3>
                <p style="font-size: 13px; color: #1565c0; font-weight: 600; margin-bottom: 8px;">
                    "Lỡ quán chuẩn bị đồ rất tốt nhưng sinh viên cố tình dàn cảnh để ăn quỵt thì sao?"
                </p>
                <div style="font-size: 14px; line-height: 1.7; color: #424242;">
                    <strong>Giải pháp hệ thống:</strong>
                    <ol style="margin-left: 20px; margin-top: 6px;">
                        <li><strong>Không tự động hoàn tiền:</strong> Khi sinh viên bấm khiếu nại, hệ thống không tự ý trả tiền cho sinh viên ngay.</li>
                        <li><strong>Quyền phản đối 48h của quán:</strong> Chủ quán lập tức nhận thông báo kèm ảnh khiếu nại, có 48 giờ để gửi ghi chú giải trình và ảnh đối chứng tại quầy.</li>
                        <li><strong>Trọng tài Admin phân xử:</strong> Quản trị viên đóng vai trò trọng tài độc lập, xem xét kỹ lưỡng ảnh từ cả 2 phía trước khi phán quyết.</li>
                        <li><strong>Chế tài cảnh cáo & Khóa tài khoản:</strong> Nếu sinh viên vu khống dàn cảnh, tiền sẽ chuyển cho quán, sinh viên bị tính 1 lần cảnh cáo (đủ 3 lần cảnh cáo hệ thống sẽ tự động khóa tài khoản vĩnh viễn). Mỗi sinh viên chỉ được khiếu nại tối đa 3 lần/tháng.</li>
                    </ol>
                </div>
            </div>
        </div>

        <div style="margin-top: 25px; background: #e8f5e9; padding: 16px; border-radius: 8px; text-align: center;">
            <strong style="color: #2e7d32; font-size: 15px;">
                💡 Kết quả: Cả 2 bên đều được bảo vệ công bằng, không ai có thể đơn phương trục lợi!
            </strong>
        </div>
    </div>

    <!-- 3. Quy trình O2O 4 bước đơn giản -->
    <div class="card">
        <h2 style="color: var(--primary-color); margin-bottom: 20px; font-size: 22px; text-align: center;">
            3. Quy Trình Vận Hành 4 Bước Online-to-Offline (O2O)
        </h2>

        <div class="form-row" style="text-align: center;">
            <div class="form-col" style="padding: 15px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #2e7d32; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; margin: 0 auto 12px auto;">1</div>
                <h4 style="color: #2e7d32; margin-bottom: 6px;">Quán Đăng Túi</h4>
                <p style="font-size: 13px; color: var(--text-muted);">Cuối ngày, quán gom đồ dư sạch, hẹn giờ mở bán với mức giá giảm độc quyền 30% - 50%.</p>
            </div>

            <div class="form-col" style="padding: 15px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #e65100; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; margin: 0 auto 12px auto;">2</div>
                <h4 style="color: #e65100; margin-bottom: 6px;">Sinh Viên Chốt Đơn</h4>
                <p style="font-size: 13px; color: var(--text-muted);">Đến giờ mở bán, sinh viên đặt túi. Hệ thống trừ kho tức thì, tạm giữ tiền và cấp mã nhận hàng 6 ký tự.</p>
            </div>

            <div class="form-col" style="padding: 15px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #0277bd; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; margin: 0 auto 12px auto;">3</div>
                <h4 style="color: #0277bd; margin-bottom: 6px;">Nhận Hàng Tại Quán</h4>
                <p style="font-size: 13px; color: var(--text-muted);">Sinh viên đưa mã cho quán nhập xác nhận. Không trừ kho thêm, tiền tiếp tục giữ 45 phút để khách kiểm tra.</p>
            </div>

            <div class="form-col" style="padding: 15px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: #6a1b9a; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; margin: 0 auto 12px auto;">4</div>
                <h4 style="color: #6a1b9a; margin-bottom: 6px;">Hoàn Tất & Nhả Tiền</h4>
                <p style="font-size: 13px; color: var(--text-muted);">Sinh viên bấm "Tôi hài lòng" hoặc hết 45 phút không khiếu nại, tiền tự động chuyển vào ví của chủ quán.</p>
            </div>
        </div>
    </div>

    <!-- Nút hành động kêu gọi -->
    <div style="text-align: center; margin: 35px 0;">
        <a href="<?php echo BASE_URL; ?>index.php" class="btn btn-primary" style="padding: 14px 32px; font-size: 16px;">
            🚀 KHÁM PHÁ CÁC TÚI BẤT NGỜ ĐANG BÁN NGAY
        </a>
        <a href="<?php echo BASE_URL; ?>doi_tac.php" class="btn btn-outline" style="padding: 14px 28px; font-size: 16px; margin-left: 10px;">
            Xem danh sách quán đối tác
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
