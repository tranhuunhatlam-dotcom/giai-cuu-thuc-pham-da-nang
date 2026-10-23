<?php
// FILE: faq.php
// Mục đích: Cẩm nang hỏi đáp thường gặp (FAQ) cho Sinh viên & Quán đối tác, giải quyết triệt để các thắc mắc nghiệp vụ

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$page_title = 'Cẩm Nang & Câu Hỏi Thường Gặp (FAQ)';
require_once __DIR__ . '/header.php';
?>

<div style="max-width: 920px; margin: 20px auto;">
    <div style="text-align: center; margin-bottom: 36px;">
        <span class="badge badge-success" style="font-size: 13px; padding: 6px 16px; margin-bottom: 10px;">
            📚 HỖ TRỢ NGƯỜI DÙNG & GIẢI ĐÁP NGHIỆP VỤ
        </span>
        <h1 style="font-size: 32px; font-weight: 900; color: var(--text-dark); letter-spacing: -0.6px; margin-top: 8px;">
            CẨM NANG SĂN TÚI & CÂU HỎI THƯỜNG GẶP
        </h1>
        <p style="font-size: 15px; color: var(--text-muted); max-width: 680px; margin: 8px auto 0 auto; line-height: 1.6;">
            Tất cả những điều bạn cần biết về mô hình Túi Bất Ngờ, chính sách bảo vệ 2 chiều, mã nhận hàng và quy định hoàn tiền tại Đà Nẵng.
        </p>
    </div>

    <!-- KHỐI ACCORDION CÂU HỎI THƯỜNG GẶP (100% HTML5 PURE DETAILS/SUMMARY) -->
    <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 40px;">
        <!-- Câu 1 -->
        <details class="card" style="padding: 22px 26px; border-radius: 18px; margin-bottom: 0; cursor: pointer;">
            <summary style="font-size: 16.5px; font-weight: 800; color: var(--primary-dark); list-style: none; display: flex; justify-content: space-between; align-items: center;">
                <span>🥖 1. "Túi bất ngờ" là gì? Có phải đồ ăn thừa khách ăn dở không?</span>
                <span style="font-size: 20px; color: var(--primary-color);">▼</span>
            </summary>
            <div style="margin-top: 14px; font-size: 14.5px; color: #475569; line-height: 1.7; border-top: 1px dashed var(--border-color); padding-top: 12px; cursor: default;">
                <strong>Hoàn toàn KHÔNG!</strong> Đây là thực phẩm tươi sạch được nấu hoặc nướng mới 100% trong ngày của các tiệm bánh, nhà hàng, quán ăn chất lượng tại Đà Nẵng. Do quán luôn phải dự phòng nguyên liệu để bán hết ca, cuối ngày vẫn còn thừa những phần ăn thơm ngon nguyên vẹn. Thay vì vứt bỏ gây lãng phí, quán gom lại thành các "Túi bất ngờ" để bán với giá ưu đãi cho sinh viên.
            </div>
        </details>

        <!-- Câu 2 -->
        <details class="card" style="padding: 22px 26px; border-radius: 18px; margin-bottom: 0; cursor: pointer;">
            <summary style="font-size: 16.5px; font-weight: 800; color: var(--secondary-color); list-style: none; display: flex; justify-content: space-between; align-items: center;">
                <span>🎯 2. Vì sao tôi không được tự chọn món mà phải nhận túi ngẫu nhiên?</span>
                <span style="font-size: 20px; color: var(--secondary-color);">▼</span>
            </summary>
            <div style="margin-top: 14px; font-size: 14.5px; color: #475569; line-height: 1.7; border-top: 1px dashed var(--border-color); padding-top: 12px; cursor: default;">
                Đây chính là <strong>nguyên lý cốt lõi của mô hình Surprise Bag</strong>: Khách mua offline trực tiếp tại quán vẫn phải thanh toán 100% giá gốc để được quyền tự do chọn món theo ý thích. Mức giảm sâu <strong>30% - 50%</strong> trên website là ưu đãi độc quyền dành riêng cho bạn vì bạn chấp nhận chia sẻ rủi ro nhận món ngẫu nhiên, giúp quán giải cứu triệt để lượng thực phẩm dư cuối ngày!
            </div>
        </details>

        <!-- Câu 3 -->
        <details class="card" style="padding: 22px 26px; border-radius: 18px; margin-bottom: 0; cursor: pointer;">
            <summary style="font-size: 16.5px; font-weight: 800; color: var(--primary-dark); list-style: none; display: flex; justify-content: space-between; align-items: center;">
                <span>🛡️ 3. Cơ chế tạm giữ tiền Escrow 45 phút bảo vệ sinh viên như thế nào?</span>
                <span style="font-size: 20px; color: var(--primary-color);">▼</span>
            </summary>
            <div style="margin-top: 14px; font-size: 14.5px; color: #475569; line-height: 1.7; border-top: 1px dashed var(--border-color); padding-top: 12px; cursor: default;">
                Khi bạn đến quán đưa mã và nhận túi, tiền thanh toán của bạn <strong>chưa chuyển ngay cho quán</strong>. Hệ thống tự động đóng băng số tiền đó trong <strong>45 phút</strong>. Trong thời gian này, bạn mang túi về kiểm tra:
                <ul style="margin: 8px 0 8px 24px;">
                    <li>Nếu đồ ăn tươi ngon: Bạn có thể bấm <strong>"Tôi hài lòng"</strong> để giải ngân tiền ngay cho quán.</li>
                    <li>Nếu đồ ăn bị chua hỏng, ôi thiu hoặc không đúng cam kết: Bạn bấm <strong>"Báo cáo khiếu nại"</strong> kèm ảnh chụp. Tiền sẽ được giữ lại để Admin phân xử hoàn 100%.</li>
                </ul>
            </div>
        </details>

        <!-- Câu 4 -->
        <details class="card" style="padding: 22px 26px; border-radius: 18px; margin-bottom: 0; cursor: pointer;">
            <summary style="font-size: 16.5px; font-weight: 800; color: #0284c7; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                <span>⏱️ 4. Quy định hủy đơn hàng và hoàn tiền 100% là gì?</span>
                <span style="font-size: 20px; color: #0284c7;">▼</span>
            </summary>
            <div style="margin-top: 14px; font-size: 14.5px; color: #475569; line-height: 1.7; border-top: 1px dashed var(--border-color); padding-top: 12px; cursor: default;">
                Bạn có quyền tự do hủy đơn và nhận lại <strong>100% tiền vào ví ngay lập tức</strong> nếu thời điểm hủy còn cách giờ quán đóng cửa ít nhất <strong>90 phút</strong>. Khi đó, suất túi sẽ tự động được trả lại kho cho sinh viên khác đặt. Nếu còn ít hơn 90 phút trước giờ đóng cửa, chức năng hủy sẽ bị khóa vì quán đã tiến hành đóng gói đồ cho bạn.
            </div>
        </details>

        <!-- Câu 5 -->
        <details class="card" style="padding: 22px 26px; border-radius: 18px; margin-bottom: 0; cursor: pointer;">
            <summary style="font-size: 16.5px; font-weight: 800; color: #dc2626; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                <span>⚠️ 5. Chuyện gì xảy ra nếu tôi đặt đơn nhưng quên đến quán lấy?</span>
                <span style="font-size: 20px; color: #dc2626;">▼</span>
            </summary>
            <div style="margin-top: 14px; font-size: 14.5px; color: #475569; line-height: 1.7; border-top: 1px dashed var(--border-color); padding-top: 12px; cursor: default;">
                Đúng giờ quán đóng cửa, hệ thống tự động kiểm tra và chuyển đơn sang trạng thái <strong>Hủy do vắng mặt</strong>. Tiền của đơn hàng sẽ chuyển thẳng cho chủ quán để bồi thường chi phí đóng gói và nguyên liệu (bạn không được hoàn tiền, túi cũng không hoàn kho). Vì vậy, bạn hãy sắp xếp đến quán đúng khung giờ nhé!
            </div>
        </details>

        <!-- Câu 6 -->
        <details class="card" style="padding: 22px 26px; border-radius: 18px; margin-bottom: 0; cursor: pointer;">
            <summary style="font-size: 16.5px; font-weight: 800; color: #7c3aed; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                <span>🎟️ 6. Làm sao để sử dụng mã giảm giá voucher trợ giá?</span>
                <span style="font-size: 20px; color: #7c3aed;">▼</span>
            </summary>
            <div style="margin-top: 14px; font-size: 14.5px; color: #475569; line-height: 1.7; border-top: 1px dashed var(--border-color); padding-top: 12px; cursor: default;">
                Tại trang chi tiết túi, trước khi bấm nút xác nhận đặt mua, bạn nhập mã vào ô <em>"Nhập mã voucher trợ giá"</em>. Các mã demo sẵn có gồm:
                <ul style="margin: 8px 0 8px 24px;">
                    <li><code>VKU2026</code>: Giảm ngay 10.000 đ cho đơn từ 30.000 đ.</li>
                    <li><code>DANANGXANH</code>: Quỹ Sống Xanh trợ giá 15.000 đ cho đơn từ 40.000 đ.</li>
                    <li><code>SINHVIENMOI</code>: Giảm 5.000 đ cho sinh viên mới.</li>
                </ul>
        <!-- Câu 7: Đặt Ship tận nơi -->
        <details class="card" style="padding: 22px 26px; border-radius: 18px; margin-bottom: 0; cursor: pointer;">
            <summary style="font-size: 16.5px; font-weight: 800; color: #059669; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                <span>🛵 7. Nền tảng có hỗ trợ Đặt Ship giao hàng tận nơi không?</span>
                <span style="font-size: 20px; color: #059669;">▼</span>
            </summary>
            <div style="margin-top: 14px; font-size: 14.5px; color: #475569; line-height: 1.7; border-top: 1px dashed var(--border-color); padding-top: 12px; cursor: default;">
                <strong>Có!</strong> Bên cạnh hình thức mặc định <em>Tự đến quán lấy (Pickup - 0 đ phí)</em>, sinh viên bận rộn học tập hoặc ở xa quán có thể chọn <strong>🛵 Đặt Ship tận nơi</strong> với mức phí trợ giá đồng giá chỉ <strong>12.000 đ</strong> (giao tới tận Ký túc xá VKU, KTX Bách Khoa, KTX Kinh Tế, Sư Phạm... trên toàn thành phố Đà Nẵng).
                <div style="margin-top: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 10px 14px; border-radius: 10px; font-size: 13.5px;">
                    🛡️ <strong>Đặc quyền bảo vệ Escrow:</strong> Toàn bộ tiền túi và tiền phí ship vẫn được hệ thống tạm giữ an toàn 100%. Khi shipper giao đồ tới nơi, bạn chỉ cần đọc mã nhận hàng 6 ký tự để xác nhận!
                </div>
            </div>
        </details>
    </div>

    <!-- Hỗ trợ thêm -->
    <div class="card" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 1.5px solid #a7f3d0; text-align: center; padding: 32px 20px; border-radius: 22px;">
        <h3 style="color: #047857; font-size: 20px; font-weight: 800; margin-bottom: 8px;">Bạn Vẫn Còn Câu Hỏi Khác?</h3>
        <p style="color: #334155; font-size: 14px; max-width: 540px; margin: 0 auto 18px auto;">
            Đội ngũ hỗ trợ sinh viên tại TP. Đà Nẵng luôn sẵn sàng giải đáp và tiếp nhận phản hồi của bạn.
        </p>
        <a href="<?php echo BASE_URL; ?>gioi_thieu.php" class="btn btn-primary" style="padding: 10px 22px;">
            Đọc Chi Tiết Cơ Chế Trọng Tài & Nghiệp Vụ &rarr;
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
