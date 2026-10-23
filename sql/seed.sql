-- Seed Data for Food Rescue O2O Platform (Mô hình Túi Bất Ngờ tại Đà Nẵng)
-- Password mặc định cho tất cả tài khoản demo là: 123456

-- 1. TẠO USERS
INSERT INTO users (id, name, email, phone, password, role, avatar, status) VALUES
(1, 'Ban Quản Trị Hệ Thống', 'admin@foodrescue.vn', '0905111222', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'admin', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=120', 'active'),
(2, 'Nguyễn Văn An (SV Bách Khoa ĐN)', 'sinhvien1@dut.udn.vn', '0935123456', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120', 'active'),
(3, 'Trần Thị Mai (SV Kinh Tế ĐN)', 'sinhvien2@due.udn.vn', '0935654321', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=120', 'active'),
(4, 'Lê Hoàng Nam (SV Sư Phạm Kỹ Thuật)', 'sinhvien3@ute.udn.vn', '0935987654', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=120', 'active'),
(5, 'Chủ Quán BonBon Bakery', 'bonbon@bakery.vn', '0905333444', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'store_owner', 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=120', 'active'),
(6, 'Chủ Quán Đồng Tiến Bakery', 'dongtien@bakery.vn', '0905555666', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'store_owner', 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=120', 'active'),
(7, 'Chủ Quán An Hội Cafe', 'anhoi@cafe.vn', '0905777888', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'store_owner', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120', 'active'),
(8, 'Chủ Quán The Cups Coffee', 'thecups@danang.vn', '0905999000', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'store_owner', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=120', 'active');

-- Thêm 15 tài khoản sinh viên giả lập phục vụ stress test mua hàng đồng thời
INSERT INTO users (id, name, email, phone, password, role, status) VALUES
(101, 'Sinh Viên Test 01', 'sim_sv01@dut.udn.vn', '0911000001', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(102, 'Sinh Viên Test 02', 'sim_sv02@dut.udn.vn', '0911000002', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(103, 'Sinh Viên Test 03', 'sim_sv03@dut.udn.vn', '0911000003', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(104, 'Sinh Viên Test 04', 'sim_sv04@dut.udn.vn', '0911000004', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(105, 'Sinh Viên Test 05', 'sim_sv05@dut.udn.vn', '0911000005', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(106, 'Sinh Viên Test 06', 'sim_sv06@dut.udn.vn', '0911000006', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(107, 'Sinh Viên Test 07', 'sim_sv07@dut.udn.vn', '0911000007', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(108, 'Sinh Viên Test 08', 'sim_sv08@dut.udn.vn', '0911000008', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(109, 'Sinh Viên Test 09', 'sim_sv09@dut.udn.vn', '0911000009', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(110, 'Sinh Viên Test 10', 'sim_sv10@dut.udn.vn', '0911000010', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(111, 'Sinh Viên Test 11', 'sim_sv11@dut.udn.vn', '0911000011', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(112, 'Sinh Viên Test 12', 'sim_sv12@dut.udn.vn', '0911000012', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(113, 'Sinh Viên Test 13', 'sim_sv13@dut.udn.vn', '0911000013', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(114, 'Sinh Viên Test 14', 'sim_sv14@dut.udn.vn', '0911000014', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active'),
(115, 'Sinh Viên Test 15', 'sim_sv15@dut.udn.vn', '0911000015', '$2y$10$e8T7O91Wk5l4JkR6V7PzXe1yQjE.GzD3O1L2kM4N5P6Q7R8S9T0U1', 'student', 'active');

-- 2. TẠO VÍ TIỀN (WALLETS)
INSERT INTO wallets (id, user_id, wallet_type, balance, pending_escrow) VALUES
(1, NULL, 'system_escrow', 0.00, 35000.00), -- Ví Escrow tổng của sàn
(2, 2, 'student', 250000.00, 35000.00), -- Nguyễn Văn An (250k sẵn sàng, 35k đang giữ ở đơn mẫu)
(3, 3, 'student', 180000.00, 0.00),     -- Trần Thị Mai
(4, 4, 'student', 95000.00, 0.00),      -- Lê Hoàng Nam
(5, 5, 'store', 850000.00, 35000.00),   -- Ví BonBon Bakery
(6, 6, 'store', 620000.00, 0.00),       -- Ví Đồng Tiến
(7, 7, 'store', 430000.00, 0.00),       -- Ví An Hội Cafe
(8, 8, 'store', 510000.00, 0.00);       -- Ví The Cups Coffee

-- Cấp vốn 200.000đ cho mỗi tài khoản test để sẵn sàng mô phỏng
INSERT INTO wallets (user_id, wallet_type, balance, pending_escrow)
SELECT id, 'student', 200000.00, 0.00 FROM users WHERE id >= 101;

-- 3. TẠO CỬA HÀNG ĐỐI TÁC TẠI ĐÀ NẴNG (STORES)
INSERT INTO stores (id, user_id, store_name, category, address, district, city, lat, lng, phone, rating, image_url, description, status) VALUES
(1, 5, 'BonBon French Bakery & Pastry', 'bakery', '124 Lê Lợi, P. Thạch Thang', 'Hải Châu', 'Đà Nẵng', 16.0754, 108.2215, '0905333444', 4.9, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600', 'Tiệm bánh Pháp thủ công, chuyên Croissant, Pain au Chocolat, Baguette truyền thống thơm lừng nướng mới trong ngày.', 'approved'),
(2, 6, 'Đồng Tiến Bakery - Chi nhánh Hải Phòng', 'bakery', '161 Hải Phòng, P. Thạch Thang', 'Hải Châu', 'Đà Nẵng', 16.0689, 108.2144, '0905555666', 4.8, 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600', 'Hương vị bánh mì truyền thống Đà Nẵng hơn 50 năm, đa dạng bánh bông lan, bánh mặn chà bông phô mai.', 'approved'),
(3, 7, 'An Hội Specialty Coffee & Tea', 'cafe', '45 Nguyễn Thái Học, P. Hải Châu 1', 'Hải Châu', 'Đà Nẵng', 16.0667, 108.2235, '0905777888', 4.7, 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600', 'Không gian cafe đậm chất phố cổ, phục vụ cold brew hảo hạng, trà ủ lạnh và bánh cookie giòn tan cuối ngày.', 'approved'),
(4, 8, 'The Cups Coffee & Pastry Lab', 'cafe', '233 Nguyễn Văn Thoại, P. An Hải Đông', 'Sơn Trà', 'Đà Nẵng', 16.0538, 108.2435, '0905999000', 4.8, 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?w=600', 'Chuỗi cà phê trẻ trung gần biển Mỹ Khê, giải cứu các loại bánh mousse, tiramisu và sandwich healthy tươi ngon.', 'approved');

-- 4. TẠO TÚI BẤT NGỜ (SURPRISE BAGS)
INSERT INTO surprise_bags (id, store_id, title, description, category, original_price, rescue_price, quantity_available, initial_quantity, pickup_start, pickup_end, sale_date, status) VALUES
(1, 1, 'Túi Bất Ngờ Bánh Pháp Cao Cấp', 'Gồm 2-3 phần bánh ngẫu nhiên: Croissant bơ Pháp AOP, Pain au Chocolat vỏ giòn xốp hoặc Tart trái cây tươi thơm ngậy. Giá trị thực tế tối thiểu 85.000đ.', 'Bánh ngọt Pháp', 85000.00, 35000.00, 5, 6, '20:00:00', '21:30:00', CURDATE(), 'available'),
(2, 1, 'Túi Bất Ngờ Bánh Mì Bữa Tối', 'Gồm 1 bánh mì gối ngũ cốc nguyên cám hoặc baguette mè đen + 1 bánh mặn xúc xích phô mai. Phù hợp sinh viên ăn tối nhanh ấm bụng.', 'Bánh mặn & Sandwich', 70000.00, 28000.00, 4, 4, '19:45:00', '21:15:00', CURDATE(), 'available'),
(3, 2, 'Túi Bất Ngờ Bông Lan & Chà Bông Đồng Tiến', 'Hộp bánh bông lan trứng muối sốt phô mai tan chảy hoặc bánh su kem thơm béo, làm tươi mới mỗi sáng.', 'Bánh truyền thống', 75000.00, 30000.00, 8, 8, '20:15:00', '22:00:00', CURDATE(), 'available'),
(4, 3, 'Combo Trà Coldbrew & Cookie Bơ Hạt', '1 chai Cold Brew trái cây ủ chậm 24h + túi 3 chiếc bánh quy hạt macca yến mạch giòn rụm.', 'Cafe & Trà', 80000.00, 32000.00, 6, 6, '20:30:00', '22:00:00', CURDATE(), 'available'),
(5, 4, 'Túi Bất Ngờ Bánh Lạnh & Mousse Ngọt Ngào', 'Tiramisu cafe Ý hoặc Mousse chanh dây thanh mát, bảo quản tủ lạnh chuẩn nhiệt độ, thơm bùi.', 'Bánh lạnh & Dessert', 90000.00, 36000.00, 4, 4, '20:00:00', '21:30:00', CURDATE(), 'available'),
(99, 1, '[THỰC NGHIỆM ĐỒ HỌA] Túi Giới Hạn Cạnh Tranh Cao (Flash Sale)', 'Túi số lượng cực ít dành riêng cho kịch bản thử nghiệm đồng thời: Chỉ có 3 túi nhưng hệ thống mô phỏng 20 sinh viên bấm mua cùng 1 phần nghìn giây để chứng minh Pessimistic Locking chống Oversell.', 'Thực nghiệm Stress Test', 100000.00, 30000.00, 3, 3, '19:30:00', '21:30:00', CURDATE(), 'available');

-- 5. TẠO ĐƠN HÀNG MẪU (ORDERS) VÀ VÍ TẠM GIỮ (ESCROW)
-- Đơn mẫu 1: Đang chờ sinh viên đến quán nhận (Mã QR & OTP đang active)
INSERT INTO orders (id, order_code, student_id, store_id, bag_id, quantity, total_amount, pickup_otp, pickup_qr_token, pickup_window_start, pickup_window_end, status, created_at) VALUES
(1, 'ODR-261013-8821', 2, 1, 1, 1, 35000.00, '682910', 'TOKEN-QR-BONBON-8821-SECRET-SAFE', CONCAT(CURDATE(), ' 20:00:00'), CONCAT(CURDATE(), ' 21:30:00'), 'escrow_held', NOW() - INTERVAL 30 MINUTE);

-- Escrow hold tương ứng cho đơn 1
INSERT INTO escrow_holds (id, order_id, student_wallet_id, store_wallet_id, amount, status, held_at, note) VALUES
(1, 1, 2, 5, 35000.00, 'holding', NOW() - INTERVAL 30 MINUTE, 'Ví tạm giữ: 35.000đ đang phong tỏa an toàn. Sẽ giải phóng cho BonBon Bakery khi sinh viên quét mã QR/OTP.');

-- Ghi log ví biến động cho đơn 1
INSERT INTO wallet_transactions (wallet_id, order_id, transaction_type, direction, amount, balance_before, balance_after, description, reference_code, created_at) VALUES
(2, 1, 'escrow_hold', 'debit', 35000.00, 285000.00, 250000.00, 'Tạm giữ tiền mua Túi Bất Ngờ tại BonBon Bakery [Đơn #ODR-261013-8821]', 'TX-ESCROW-HOLD-8821', NOW() - INTERVAL 30 MINUTE);

-- Đơn mẫu 2: Đơn đã nhận thành công hôm qua (Completed)
INSERT INTO orders (id, order_code, student_id, store_id, bag_id, quantity, total_amount, pickup_otp, pickup_qr_token, pickup_window_start, pickup_window_end, status, pickup_verified_at, created_at) VALUES
(2, 'ODR-261012-4419', 3, 2, 3, 1, 30000.00, '419205', 'TOKEN-QR-DONGTIEN-4419-DONE', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 20:15:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 22:00:00'), 'completed', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 20:45:12'), NOW() - INTERVAL 1 DAY);

INSERT INTO escrow_holds (id, order_id, student_wallet_id, store_wallet_id, amount, status, held_at, released_at, note) VALUES
(2, 2, 3, 6, 30000.00, 'released', NOW() - INTERVAL 1 DAY, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 20:45:12'), 'Đã giải phóng 30.000đ cho Đồng Tiến Bakery sau khi khách xuất trình OTP thành công.');

-- Review cho đơn 2
INSERT INTO reviews (id, order_id, student_id, store_id, rating, comment, created_at) VALUES
(1, 2, 3, 2, 5, 'Bánh bông lan phô mai siêu ngon và còn rất mới! Lấy hàng lúc 20h45 rất nhanh gọn chỉ cần đưa mã OTP.', NOW() - INTERVAL 23 HOUR);
