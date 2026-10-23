<?php
// FILE: footer.php
// Mục đích: Chân trang web hiện đại phong cách Dark Slate, thông tin đồ án sinh viên và liên kết điều hướng nhanh
?>
</main>

<footer class="footer">
    <div class="container" style="padding-top: 0; padding-bottom: 0;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 36px; text-align: left; margin-bottom: 36px;">
            <!-- Cột 1: Thông tin thương hiệu -->
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                    <span style="font-size: 26px;">🥗</span>
                    <strong style="font-size: 19px; color: #ffffff; letter-spacing: -0.3px;">GIẢI CỨU THỰC PHẨM ĐÀ NẴNG</strong>
                </div>
                <p style="font-size: 14px; color: #94a3b8; line-height: 1.7; margin-bottom: 16px;">
                    Nền tảng thương mại điện tử O2O kết nối các tiệm bánh, cafe học tập và quán ăn tại TP. Đà Nẵng với sinh viên cần mua đồ ăn chất lượng với mức giá giảm sâu 30% - 50% theo mô hình Túi Bất Ngờ.
                </p>
                <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); padding: 6px 14px; border-radius: 20px; font-size: 12px; color: #34d399; font-weight: 700;">
                    🛡️ Tạm Giữ Escrow An Toàn 100%
                </div>
            </div>

            <!-- Cột 2: Khám phá hệ thống -->
            <div>
                <h4 style="color: #ffffff; margin-bottom: 16px; font-size: 16px; font-weight: 800; letter-spacing: -0.2px;">Khám Phá Nhanh</h4>
                <ul style="list-style: none; font-size: 14px; line-height: 2.2;">
                    <li><a href="<?php echo BASE_URL; ?>index.php">🏠 Trang chủ săn túi</a></li>
                    <li><a href="<?php echo BASE_URL; ?>vinh_danh.php">🏆 Bảng Vinh Danh Sống Xanh</a></li>
                    <li><a href="<?php echo BASE_URL; ?>doi_tac.php">🏪 Quán đối tác Đà Nẵng</a></li>
                    <li><a href="<?php echo BASE_URL; ?>faq.php">❓ Cẩm Nang Săn Túi & FAQ</a></li>
                    <li><a href="<?php echo BASE_URL; ?>quy_trinh.php">🛡️ Quy trình Escrow & Trọng tài</a></li>
                </ul>
            </div>

            <!-- Cột 3: Công cụ & Kiểm thử đồ án -->
            <div>
                <h4 style="color: #ffffff; margin-bottom: 16px; font-size: 16px; font-weight: 800; letter-spacing: -0.2px;">Kiểm Thử Đồ Án</h4>
                <ul style="list-style: none; font-size: 14px; line-height: 2.2;">
                    <li><a href="<?php echo BASE_URL; ?>test_ket_noi.php">🔌 Kiểm tra kết nối MySQL</a></li>
                    <li><a href="<?php echo BASE_URL; ?>tao_du_lieu_mau.php">🌱 Khởi tạo dữ liệu mẫu đồ án</a></li>
                    <li><a href="<?php echo BASE_URL; ?>admin/kiem_tra_tien.php">⚖️ Kiểm toán cân bằng quỹ Escrow</a></li>
                    <li><a href="<?php echo BASE_URL; ?>admin/cai_dat.php">⚙️ Cấu hình demo nhanh (1 phút)</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p style="font-weight: 700; color: #cbd5e1; font-size: 13.5px;">
                🎓 Đồ Án Sinh Viên Ngành Công Nghệ Thông Tin - Trường Đại học Công nghệ Thông tin & Truyền thông Việt - Hàn (VKU Đà Nẵng)
            </p>
            <p style="margin-top: 6px; font-size: 12.5px;">
                ⏱️ Thời gian máy chủ hệ thống: <strong style="color: #34d399;"><?php echo date('H:i:s d/m/Y'); ?></strong> (Nhấn <strong>F5</strong> để đồng bộ và tự động kích hoạt kiểm tra đơn hàng).
            </p>
        </div>
    </div>
</footer>

</body>
</html>
