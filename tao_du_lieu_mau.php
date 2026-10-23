<?php
// FILE: tao_du_lieu_mau.php
// Mục đích: Khởi tạo dữ liệu mẫu quy mô lớn: 30 Quán đối tác & 105 Túi bất ngờ
// Phủ kín 7 Quận/Huyện của Đà Nẵng, đầy đủ mọi phân khúc giá (15k - 85k)
// Tích hợp tính năng Đặt Ship giao tận nơi & Bảo toàn quỹ Escrow 100% (Sai lệch 0 đ)

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_seed'])) {
    mysqli_begin_transaction($conn);

    // 1. Tạm tắt khóa ngoại để làm sạch bảng
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    mysqli_query($conn, "TRUNCATE TABLE reviews");
    mysqli_query($conn, "TRUNCATE TABLE vouchers");
    mysqli_query($conn, "TRUNCATE TABLE notifications");
    mysqli_query($conn, "TRUNCATE TABLE wallet_logs");
    mysqli_query($conn, "TRUNCATE TABLE disputes");
    mysqli_query($conn, "TRUNCATE TABLE orders");
    mysqli_query($conn, "TRUNCATE TABLE bags");
    mysqli_query($conn, "TRUNCATE TABLE shops");
    mysqli_query($conn, "TRUNCATE TABLE users");
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

    // 2. Mật khẩu mã hóa mặc định
    $pass_admin = password_hash('admin123', PASSWORD_DEFAULT);
    $pass_sv = password_hash('sv123', PASSWORD_DEFAULT);
    $pass_shop = password_hash('shop123', PASSWORD_DEFAULT);

    // 3. Tạo tài khoản Users (Admin, 6 Sinh viên, 30 Chủ quán)
    mysqli_query($conn, "INSERT INTO users (id, name, email, password, role, balance, warning_count, is_locked, university, created_at) VALUES 
        (1, 'Quản Trị Viên Đà Nẵng', 'admin@giaicuu.vn', '$pass_admin', 'admin', 0.00, 0, 0, 'Ban Quản Trị Hệ Thống', NOW()),
        (2, 'Nguyễn Văn An (VKU Đà Nẵng)', 'sv1@giaicuu.vn', '$pass_sv', 'student', 195000.00, 0, 0, 'Đại học CNTT&TT Việt - Hàn (VKU Đà Nẵng)', NOW()),
        (3, 'Trần Thị Mai (ĐH Kinh Tế ĐN)', 'sv2@giaicuu.vn', '$pass_sv', 'student', 260000.00, 0, 0, 'Đại học Kinh Tế - ĐH Đà Nẵng', NOW()),
        (4, 'Lê Hoàng Phúc (ĐH Bách Khoa ĐN)', 'sv3@giaicuu.vn', '$pass_sv', 'student', 150000.00, 1, 0, 'Đại học Bách Khoa - ĐH Đà Nẵng', NOW()),
        (18, 'Phan Minh Tuấn (ĐH Ngoại Ngữ ĐN)', 'sv4@giaicuu.vn', '$pass_sv', 'student', 188000.00, 0, 0, 'Đại học Ngoại Ngữ - ĐH Đà Nẵng', NOW()),
        (19, 'Đỗ Quỳnh Anh (ĐH Sư Phạm ĐN)', 'sv5@giaicuu.vn', '$pass_sv', 'student', 120000.00, 0, 0, 'Đại học Sư Phạm - ĐH Đà Nẵng', NOW()),
        (20, 'Hoàng Gia Huy (ĐH Duy Tân ĐN)', 'sv6@giaicuu.vn', '$pass_sv', 'student', 145000.00, 0, 0, 'Đại học Duy Tân Đà Nẵng', NOW())");

    // Tạo 30 chủ quán (User ID 5..34)
    $shop_owners_data = [
        [5, 'Võ Thị Hằng (BonBon Bakery)', 'tiembanh@giaicuu.vn', 55000.00],
        [6, 'Nguyễn Văn Hùng (Cơm Tấm Sườn Cây)', 'comtam@giaicuu.vn', 40000.00],
        [7, 'Phạm Thu Hương (Matcha Mori Cafe)', 'trasua@giaicuu.vn', 50000.00],
        [8, 'Lê Minh Trí (Ẩm Thực Chay An Lạc)', 'dochay@giaicuu.vn', 0.00],
        [9, 'Trần Thu Hà (Mì Quảng Bà Lữ)', 'miquang@giaicuu.vn', 30000.00],
        [10, 'Đặng Quốc Bảo (The Brown Coffee)', 'thebrown@giaicuu.vn', 35000.00],
        [11, 'Hoàng Thị Yến (Bếp KTX Bách Khoa)', 'bachkhoa@giaicuu.vn', 25000.00],
        [12, 'Bùi Đình Nam (Pizza Chợ Hòa Khánh)', 'pizza@giaicuu.vn', 0.00],
        [13, 'Nguyễn Thị Kim Ngân (Bếp Làng ĐH VKU)', 'vku_quan@giaicuu.vn', 0.00],
        [14, 'Mai Văn Long (Salad Biển Đông)', 'salad@giaicuu.vn', 0.00],
        [15, 'Đỗ Hữu Nghĩa (Bánh Mì Mộc Cẩm Lệ)', 'camle@giaicuu.vn', 0.00],
        [16, 'Trương Ánh Tuyết (Cơm Niêu Hương Quê)', 'comnieu@giaicuu.vn', 0.00],
        [17, 'Phan Thanh Tùng (Nông Trại Hòa Vang)', 'hoavang@giaicuu.vn', 0.00],
        [21, 'Lê Thị Thảo (Bánh Mì Chả Bò Đà Nẵng)', 'chabo@giaicuu.vn', 0.00],
        [22, 'Phạm Ngọc Sơn (Cơm Gà Xối Mỡ A Hải)', 'comga@giaicuu.vn', 0.00],
        [23, 'Trần Minh Quang (Cafe Muối Bạch Đằng)', 'cafemuoi@giaicuu.vn', 0.00],
        [24, 'Đinh Thị Thu (Bếp Chay Phúc Lộc Thọ)', 'chayhaichau@giaicuu.vn', 0.00],
        [25, 'Vũ Đình Khang (Bánh Mì Que Hải Phòng)', 'banhmique@giaicuu.vn', 0.00],
        [26, 'Ngô Thị Cúc (Bún Bò Huế Bà Diệu)', 'bunbo@giaicuu.vn', 0.00],
        [27, 'Lâm Thanh Phong (Trà Sữa Toco Săn Đêm)', 'toco@giaicuu.vn', 0.00],
        [28, 'Trịnh Hữu Bình (Cơm Thố Xá Xíu Đồng Kè)', 'comtho@giaicuu.vn', 0.00],
        [29, 'Nguyễn Thị Loan (Trà Trái Cây Sư Phạm)', 'trahoaminh@giaicuu.vn', 0.00],
        [30, 'Đào Văn Phú (Bánh Bao Nóng Ga Kim Liên)', 'banhbao@giaicuu.vn', 0.00],
        [31, 'Dương Mỹ Hạnh (Trà Sữa KTX Hòa Quý)', 'trasuaktx@giaicuu.vn', 0.00],
        [32, 'Bùi Văn Hậu (Bún Mắm Nêm Bà Mụ)', 'bunmam@giaicuu.vn', 0.00],
        [33, 'Hồ Thị Thơ (Ốc & Ăn Vặt Đêm An Thượng)', 'anvat@giaicuu.vn', 0.00],
        [34, 'Phan Đăng Khoa (Bánh Canh Ruộng Hà Thị Thân)', 'banhcanh@giaicuu.vn', 0.00],
        [35, 'Tạ Thị Kiều (Coffee Sách & Cookie Sơn Trà)', 'cookies@giaicuu.vn', 0.00],
        [36, 'Lê Quốc Việt (Bún Chả Hà Nội Cẩm Lệ)', 'buncha@giaicuu.vn', 0.00],
        [37, 'Nguyễn Hữu Dũng (Bánh Tráng Nướng Túy Loan)', 'tuyloan@giaicuu.vn', 0.00]
    ];

    foreach ($shop_owners_data as $so) {
        $stmt_so = mysqli_prepare($conn, "INSERT INTO users (id, name, email, password, role, balance, warning_count, is_locked, created_at) VALUES (?, ?, ?, ?, 'shop_owner', ?, 0, 0, NOW())");
        mysqli_stmt_bind_param($stmt_so, "isssd", $so[0], $so[1], $so[2], $pass_shop, $so[3]);
        mysqli_stmt_execute($stmt_so);
        mysqli_stmt_close($stmt_so);
    }

    // Nhật ký nạp tiền demo ban đầu cho sinh viên (Tổng nạp = 1.475.000 đ)
    mysqli_query($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES 
        (2, 305000.00, 'Nạp tiền demo khởi tạo ví', NULL, NOW()),
        (3, 340000.00, 'Nạp tiền demo khởi tạo ví', NULL, NOW()),
        (4, 250000.00, 'Nạp tiền demo khởi tạo ví', NULL, NOW()),
        (18, 250000.00, 'Nạp tiền demo khởi tạo ví', NULL, NOW()),
        (19, 150000.00, 'Nạp tiền demo khởi tạo ví', NULL, NOW()),
        (20, 180000.00, 'Nạp tiền demo khởi tạo ví', NULL, NOW())");

    // 4. Tạo 30 Cửa Hàng Phủ Kín 7 Quận Huyện Tại TP. Đà Nẵng
    $shops = [
        // Hải Châu
        [1, 5, 'Tiệm Bánh BonBon Bakery', '123 Lê Duẩn, P. Thạch Thang', 'Hải Châu', 'Bánh ngọt & Bánh mì', 'bag_banh_ngot.jpg'],
        [5, 9, 'Mì Quảng & Bún Chả Cá Bà Lữ', '32 Huỳnh Thúc Kháng, P. Nam Dương', 'Hải Châu', 'Cơm & Bún phở', 'bag_com_tam.jpg'],
        [14, 21, 'Bánh Mì Chả Bò Đà Nẵng', '04 Lê Hồng Phong, P. Phước Ninh', 'Hải Châu', 'Bánh ngọt & Bánh mì', 'bag_banh_mi.jpg'],
        [15, 22, 'Cơm Gà Xối Mỡ A Hải', '96 Phan Châu Trinh, P. Phước Ninh', 'Hải Châu', 'Cơm & Bún phở', 'bag_com_tam.jpg'],
        [16, 23, 'Cafe Muối & Trà Bạch Đằng', '28 Bạch Đằng, P. Thạch Thang', 'Hải Châu', 'Trà sữa & Cafe', 'bag_tra_sua.jpg'],
        [17, 24, 'Bếp Chay Phúc Lộc Thọ', '18 Pasteur, P. Hải Châu 1', 'Hải Châu', 'Đồ chay', 'bag_banh_mi.jpg'],

        // Thanh Khê
        [2, 6, 'Cơm Tấm Sườn Cây Đà Nẵng', '45 Nguyễn Văn Linh, P. Nam Dương', 'Thanh Khê', 'Cơm & Bún phở', 'bag_com_tam.jpg'],
        [6, 10, 'The Brown Coffee & Tea Bakery', '215 Điện Biên Phủ, P. Chính Gián', 'Thanh Khê', 'Trà sữa & Cafe', 'bag_tra_sua.jpg'],
        [18, 25, 'Bánh Mì Que & Trà Tắc Hải Phòng', '78 Hà Huy Tập, P. Xuân Hà', 'Thanh Khê', 'Thức ăn nhanh', 'bag_banh_mi.jpg'],
        [19, 26, 'Bún Bò Huế & Bún Riêu Bà Diệu', '17 Trần Cao Vân, P. Tam Thuận', 'Thanh Khê', 'Cơm & Bún phở', 'bag_com_tam.jpg'],
        [20, 27, 'Trà Sữa Toco Săn Đêm', '312 Điện Biên Phủ, P. Thanh Khê Đông', 'Thanh Khê', 'Trà sữa & Cafe', 'bag_tra_sua.jpg'],

        // Liên Chiểu
        [7, 11, 'Bếp Sinh Viên KTX Bách Khoa', '68 Ngô Thì Nhậm, P. Hòa Khánh Nam', 'Liên Chiểu', 'Cơm & Bún phở', 'bag_com_tam.jpg'],
        [8, 12, 'Tiệm Bánh Nướng & Pizza Chợ Hòa Khánh', '15 Nguyễn Lương Bằng, P. Hòa Khánh Bắc', 'Liên Chiểu', 'Bánh ngọt & Bánh mì', 'bag_banh_ngot.jpg'],
        [21, 28, 'Cơm Thố Xá Xíu Chợ Đêm', '02 Đồng Kè, P. Hòa Khánh Bắc', 'Liên Chiểu', 'Cơm & Bún phở', 'bag_com_tam.jpg'],
        [22, 29, 'Trà Trái Cây & Bánh Tráng Sư Phạm', '42 Tôn Đức Thắng, P. Hòa Minh', 'Liên Chiểu', 'Trà sữa & Cafe', 'bag_tra_sua.jpg'],
        [23, 30, 'Bánh Bao Nóng & Xôi Mặn Ga Kim Liên', '89 Nguyễn Lương Bằng, P. Hòa Hiệp Nam', 'Liên Chiểu', 'Thức ăn nhanh', 'bag_banh_mi.jpg'],

        // Ngũ Hành Sơn
        [4, 8, 'Ẩm Thực Chay An Lạc', '12 Ngũ Hành Sơn, P. Mỹ An', 'Ngũ Hành Sơn', 'Đồ chay', 'bag_banh_mi.jpg'],
        [9, 13, 'Bếp Sinh Viên Làng ĐH VKU', '470 Đường Trần Đại Nghĩa, P. Hòa Quý', 'Ngũ Hành Sơn', 'Cơm & Bún phở', 'bag_com_tam.jpg'],
        [24, 31, 'Trà Sữa & Bánh KTX Hòa Quý', '15 Nam Kỳ Khởi Nghĩa, P. Hòa Quý', 'Ngũ Hành Sơn', 'Trà sữa & Cafe', 'bag_tra_sua.jpg'],
        [25, 32, 'Bún Mắm Nêm Bà Mụ Chợ Bắc Mỹ An', '85 Châu Thị Vĩnh Tế, P. Mỹ An', 'Ngũ Hành Sơn', 'Cơm & Bún phở', 'bag_com_tam.jpg'],
        [26, 33, 'Ốc & Ăn Vặt Đêm An Thượng', '34 An Thượng 2, P. Mỹ An', 'Ngũ Hành Sơn', 'Thức ăn nhanh', 'bag_banh_ngot.jpg'],

        // Sơn Trà
        [3, 7, 'Trà Sữa & Bakery Matcha Mori', '88 Võ Nguyên Giáp, P. Phước Mỹ', 'Sơn Trà', 'Trà sữa & Cafe', 'bag_tra_sua.jpg'],
        [10, 14, 'Tiệm Salad & Cuốn Biển Đông', '142 Phạm Văn Đồng, P. An Hải Bắc', 'Sơn Trà', 'Món tươi & Salad', 'bag_com_tam.jpg'],
        [27, 34, 'Bánh Canh Ruộng & Chả Ram Tôm Đất', '54 Hà Thị Thân, P. An Hải Tây', 'Sơn Trà', 'Cơm & Bún phở', 'bag_com_tam.jpg'],
        [28, 35, 'Coffee Sách & Cookie Sơn Trà', '09 Hồ Nghinh, P. Phước Mỹ', 'Sơn Trà', 'Trà sữa & Cafe', 'bag_banh_ngot.jpg'],

        // Cẩm Lệ
        [11, 15, 'Bánh Mì & Cà Phê Mộc Cẩm Lệ', '56 Cách Mạng Tháng 8, P. Khuê Trung', 'Cẩm Lệ', 'Bánh ngọt & Bánh mì', 'bag_banh_mi.jpg'],
        [12, 16, 'Cơm Niêu Gia Đình Hương Quê', '108 Ông Ích Đường, P. Hòa Thọ Đông', 'Cẩm Lệ', 'Cơm & Bún phở', 'bag_com_tam.jpg'],
        [29, 36, 'Bún Chả Hà Nội Phố Cẩm Lệ', '22 Nguyễn Hữu Thọ, P. Khuê Trung', 'Cẩm Lệ', 'Cơm & Bún phở', 'bag_com_tam.jpg'],

        // Hòa Vang
        [13, 17, 'Nông Trại Hữu Cơ & Bánh Nông Sản Hòa Vang', 'QL14B, Xã Hòa Khương', 'Hòa Vang', 'Đồ chay', 'bag_banh_mi.jpg'],
        [30, 37, 'Bánh Tráng Nướng Túy Loan', 'Chợ Túy Loan, Xã Hòa Phong', 'Hòa Vang', 'Thức ăn nhanh', 'bag_banh_ngot.jpg']
    ];

    foreach ($shops as $s) {
        $stmt_s = mysqli_prepare($conn, "INSERT INTO shops (id, owner_id, name, address, district, type, image, violation_count, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'da_duyet', NOW())");
        mysqli_stmt_bind_param($stmt_s, "iisssss", $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6]);
        mysqli_stmt_execute($stmt_s);
        mysqli_stmt_close($stmt_s);
    }

    // 5. TẠO 105 TÚI BẤT NGỜ PHỦ KHẮP MỌI PHÂN KHÚC GIÁ (15K - 85K)
    $time_1h_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
    $time_in_1h = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $time_in_2h = date('Y-m-d H:i:s', strtotime('+2 hour'));
    $time_in_3h = date('Y-m-d H:i:s', strtotime('+3 hour'));
    $time_in_4h = date('Y-m-d H:i:s', strtotime('+4 hour'));
    $time_in_5h = date('Y-m-d H:i:s', strtotime('+5 hour'));
    $time_in_6h = date('Y-m-d H:i:s', strtotime('+6 hour'));

    // Danh sách 105 túi cụ thể
    $dishes_templates = [
        // Nhóm Bánh ngọt & Bánh mì
        ['Túi Bất Ngờ Bánh Ngọt Cuối Ngày', 'Túi gồm 3-4 bánh nướng ngẫu nhiên nướng trong ngày (Croissant, Muffin, su kem).', 100000, 55000, 'bag_banh_ngot.jpg'],
        ['Túi Bánh Mì Men Sourdough & Tart Trứng', 'Bánh mì men tự nhiên Sourdough thơm lừng kèm 2 bánh tart trứng béo ngậy.', 80000, 45000, 'bag_banh_mi.jpg'],
        ['Túi Mini Su Kem & Donut Hạnh Nhân', 'Hộp 5 bánh su kem vani mát lạnh và 1 donut phủ đường hạnh nhân giòn rụm.', 40000, 22000, 'bag_banh_ngot.jpg'],
        ['Pizza Nướng Củi Mini & Bánh Mì Bơ Tỏi', '1 pizza xúc xích nướng củi size 16cm phô mai kéo sợi và 1 bánh mì bơ tỏi.', 85000, 48000, 'bag_banh_ngot.jpg'],
        ['Túi Bánh Bao Xá Xíu Trứng Cút Nóng Hổi', '2 chiếc bánh bao vỏ xốp mịn, nhân thịt xá xíu đậm đà và trứng cút béo bùi.', 30000, 17000, 'bag_banh_mi.jpg'],
        ['Túi Pastry Croissant Phô Mai & Matcha Latte', 'Set tráng miệng gồm 2 bánh croissant kẹp phô mai chảy và 1 ly matcha latte.', 110000, 62000, 'bag_banh_ngot.jpg'],
        ['Combo Bánh Mì Chả Bò & Cà Phê Mộc', '1 ổ bánh mì kẹp chả bò Đà Nẵng và 1 ly cà phê sữa đá pha phin truyền thống.', 45000, 25000, 'bag_banh_mi.jpg'],
        ['Túi Bánh Ngọt Tráng Miệng Xế Chiều', 'Bông lan trứng muối xốt phô mai và bánh chuối nướng cốt dừa béo ngậy.', 55000, 30000, 'bag_banh_ngot.jpg'],
        ['Túi Bánh Mì Que & Trà Tắc Mát Lạnh', '3 que bánh mì pate cay nướng giòn rụm và 1 ly trà tắc hạt chia siêu tiết kiệm.', 35000, 19000, 'bag_banh_mi.jpg'],
        ['Bánh Mì Nướng Muối Ớt & Bánh Su', '2 ổ bánh mì nướng giòn rưới sốt muối ớt cay ngọt, kèm tráng miệng 2 bánh su kem.', 32000, 18000, 'bag_banh_mi.jpg'],

        // Nhóm Cơm & Bún phở
        ['Túi Cứu Trợ Cơm Tấm & Món Mặn', 'Hộp cơm sườn bì chả thơm ngon hoặc đùi gà chiên mắm chuẩn vị, kèm canh nóng.', 70000, 40000, 'bag_com_tam.jpg'],
        ['Hộp Cơm Gà Xối Mỡ Đêm Muộn', 'Cơm chiên hạt ngọc vàng óng, đùi gà xối mỡ giòn da đậm đà kèm đồ chua giải ngấy.', 65000, 38000, 'bag_com_tam.jpg'],
        ['Túi Đặc Sản Mì Quảng Cuối Ngày', 'Mì Quảng tôm thịt đậm vị kèm bánh tráng mè nướng và rau sống tươi sạch.', 60000, 35000, 'bag_com_tam.jpg'],
        ['Suất Bún Chả Cá Thác Lác & Cá Thu', 'Bát bún nóng sốt với chả cá thác lác chiên thơm lừng, nước lèo ngọt thanh.', 55000, 30000, 'bag_com_tam.jpg'],
        ['Túi Cơm Suất Cứu Trợ KTX Đêm Khuya', 'Suất cơm sườn ram mặn ngọt hoặc thịt kho trứng bùi ngậy cho sinh viên.', 45000, 25000, 'bag_com_tam.jpg'],
        ['Phần Bún Đậu Mắm Tôm Thập Cẩm', 'Mẹt bún đậu thập cẩm gồm đậu rán giòn, chả cốm nóng hổi và dưa leo mát lành.', 55000, 32000, 'bag_com_tam.jpg'],
        ['Túi Bất Ngờ Sinh Viên VKU Săn Đêm', 'Cơm chiên Dương Châu hoặc mì xào bò kèm canh súp nóng đầy đặn.', 45000, 25000, 'bag_com_tam.jpg'],
        ['Suất Cơm Niêu & Cá Bống Kho Tộ', 'Cơm niêu cháy giòn ăn kèm niêu cá bống sông Trà kho tiêu cay nồng.', 95000, 52000, 'bag_com_tam.jpg'],
        ['Bún Mắm Nêm Thịt Heo Quay Giòn Rụm', 'Tô bún mắm nêm đậm đà thơm nức mùi mắm cá cơm, topping thịt heo quay giòn bì.', 50000, 28000, 'bag_com_tam.jpg'],
        ['Tô Bánh Canh Ruộng Chả Cá & Trứng Cút', 'Tô bánh canh bột gạo nước súp ngọt tôm thịt, chả cá chiên dai thơm nức.', 40000, 22000, 'bag_com_tam.jpg'],

        // Nhóm Trà sữa & Đồ uống
        ['Combo Trà Sữa Ô Long & Bánh Tươi', '1 ly trà sữa ô long nướng trân châu và 1 phần bánh bông lan phô mai.', 90000, 50000, 'bag_tra_sua.jpg'],
        ['Combo Trà Đào Cam Sả & Bánh Tiramisu', '1 ly trà đào cam sả thanh mát size L cùng 1 hũ bánh Tiramisu cacao đậm vị.', 75000, 42000, 'bag_tra_sua.jpg'],
        ['Combo Trà Sữa Thạch Phô Mai & Bánh Tráng', '1 ly trà sữa thái xanh béo bùi kèm bịch bánh tráng bơ hành phi cay cay.', 50000, 28000, 'bag_tra_sua.jpg'],
        ['Trà Sữa Trân Châu Đường Đen Size L', '1 ly trà sữa tươi trân châu đường đen dẻo quánh tiếp thêm năng lượng học thi.', 45000, 24000, 'bag_tra_sua.jpg'],
        ['Cà Phê Muối Kem Béo & Croissant Mini', '1 ly cà phê muối xứ Huế đậm đà và 2 bánh sừng bò thơm ngậy mùi bơ.', 50000, 27000, 'bag_tra_sua.jpg'],

        // Nhóm Đồ chay & Món tươi Salad
        ['Suất Cơm Chay Thanh Đạm Cuối Ngày', 'Hộp cơm thập cẩm gồm nấm kho tiêu, đậu hũ sốt cà, chả giò chay giòn rụm.', 50000, 30000, 'bag_banh_mi.jpg'],
        ['Bún Chay Giò Nấm & Chả Giò Rán', 'Tô bún chay nước dùng hầm củ quả thanh ngọt tự nhiên, topping giò nấm.', 45000, 26000, 'bag_com_tam.jpg'],
        ['Hộp Salad Ức Gà Áp Chảo & Nước Ép', 'Salad rau bina hữu cơ, ức gà sốt mè rang thanh đạm và 1 chai nước ép ổi táo.', 85000, 48000, 'bag_com_tam.jpg'],
        ['Set Gỏi Cuốn Tôm Thịt & Bánh Bột Lọc', '4 cuốn tôm thịt chấm tương đậu phộng và đĩa bánh bột lọc trần thơm nức.', 60000, 34000, 'bag_com_tam.jpg'],
        ['Túi Rau Củ Hữu Cơ & Bánh Khoai Lang', 'Rau củ quả canh tác sạch vùng Hòa Vang kèm 3 bánh khoai lang tím hấp dẻo.', 50000, 28000, 'bag_banh_mi.jpg'],
        ['Hộp Trái Cây Tươi Cắt Sẵn Theo Mùa', 'Khay trái cây tươi gồm thanh long, xoài cát, dưa hấu mát lạnh ăn tráng miệng.', 40000, 22000, 'bag_banh_ngot.jpg']
    ];

    // Tạo đúng 105 túi phân bổ đều cho 30 quán
    $bag_id = 1;
    for ($i = 0; $i < 105; $i++) {
        $shop_info = $shops[$i % count($shops)];
        $shop_id = $shop_info[0];
        $template = $dishes_templates[$i % count($dishes_templates)];

        // Tùy biến giá và tên để tạo sự phong phú tự nhiên
        $price_variation = ($i % 5) * 2000;
        $orig_price = $template[2] + $price_variation;
        $web_price = round(($orig_price * (0.5 + (($i % 4) * 0.04))) / 1000) * 1000; // Giảm 34% - 50%
        if ($web_price >= $orig_price) $web_price = round($orig_price * 0.55 / 1000) * 1000;

        $name = $template[0];
        if ($i >= 31) {
            $name .= " (Suất " . ($i % 3 == 0 ? "Tiết Kiệm" : ($i % 3 == 1 ? "Combo Đặc Biệt" : "Đêm Khuya")) . ")";
        }

        $stock = 4 + ($i % 8);
        $open_at = ($i % 7 == 0) ? $time_in_2h : $time_1h_ago; // Phần lớn đang mở bán
        $close_at = ($i % 3 == 0) ? $time_in_5h : $time_in_4h;

        $stmt_bag = mysqli_prepare($conn, "INSERT INTO bags (id, shop_id, name, description, image, original_price, web_price, stock, open_at, pickup_start, pickup_end, close_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt_bag, "iisssddissss", $bag_id, $shop_id, $name, $template[1], $template[4], $orig_price, $web_price, $stock, $open_at, $time_in_1h, $time_in_3h, $close_at);
        mysqli_stmt_execute($stmt_bag);
        mysqli_stmt_close($stmt_bag);

        $bag_id++;
    }

    // 6. TẠO CÁC ĐƠN HÀNG MẪU (PICKUP + ĐẶT SHIP TẬN NƠI)
    // Đơn 1: 'cho_nhan', Pickup (An mua Túi 1: 55k)
    mysqli_query($conn, "INSERT INTO orders (id, user_id, bag_id, shop_id, price, status, pickup_code, delivery_type, shipping_fee, close_at, created_at) VALUES 
        (1, 2, 1, 1, 55000.00, 'cho_nhan', 'DEMO01', 'pickup', 0.00, '$time_in_4h', NOW())");
    mysqli_query($conn, "UPDATE bags SET stock = stock - 1 WHERE id = 1");
    mysqli_query($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES 
        (2, -55000.00, 'Thanh toán đơn túi bất ngờ #1 (Mã nhận: DEMO01)', 1, NOW())");

    // Đơn 2: 'da_nhan', Pickup (Mai nhận Túi 3: 40k)
    $hold_time_demo = date('Y-m-d H:i:s', strtotime('+35 minutes'));
    $picked_demo = date('Y-m-d H:i:s', strtotime('-10 minutes'));
    mysqli_query($conn, "INSERT INTO orders (id, user_id, bag_id, shop_id, price, status, pickup_code, delivery_type, shipping_fee, close_at, picked_up_at, hold_until, created_at) VALUES 
        (2, 3, 3, 2, 40000.00, 'da_nhan', 'DEMO02', 'pickup', 0.00, '$time_in_4h', '$picked_demo', '$hold_time_demo', NOW())");
    mysqli_query($conn, "UPDATE bags SET stock = stock - 1 WHERE id = 3");
    mysqli_query($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES 
        (3, -40000.00, 'Thanh toán đơn túi bất ngờ #2 (Mã nhận: DEMO02)', 2, NOW())");

    // Đơn 3: 'tranh_chap', Pickup (Phúc khiếu nại Túi 4: 50k)
    mysqli_query($conn, "INSERT INTO orders (id, user_id, bag_id, shop_id, price, status, pickup_code, delivery_type, shipping_fee, close_at, picked_up_at, hold_until, created_at) VALUES 
        (3, 4, 4, 3, 50000.00, 'tranh_chap', 'DEMO03', 'pickup', 0.00, '$time_in_4h', '$picked_demo', '$hold_time_demo', NOW())");
    mysqli_query($conn, "UPDATE bags SET stock = stock - 1 WHERE id = 4");
    mysqli_query($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES 
        (4, -50000.00, 'Thanh toán đơn túi bất ngờ #3 (Mã nhận: DEMO03)', 3, NOW())");

    mysqli_query($conn, "INSERT INTO disputes (order_id, reason, student_note, student_image, shop_note, shop_image, status, created_at) VALUES 
        (3, 'Đồ ăn ôi thiu / nấm mốc / bốc mùi', 'Em mở túi bánh tart phô mai ra thấy có mùi chua gắt và đốm mốc xanh trên bề mặt. Em xin gửi ảnh chụp đính kèm làm bằng chứng.', 'mau_banh_hong.jpg', 'Quán cam kết bánh làm từ sáng, có thể do thời tiết nắng nóng ẩm làm ảnh hưởng bao bì. Nhờ Admin xem xét.', NULL, 'cho_admin_xu_ly', NOW())");

    // Đơn 10: ĐƠN ĐẶT SHIP GIAO TẬN NƠI MẪU (Tuấn đặt túi 12 giá 25k + Phí ship 12k = 37.000 đ)
    mysqli_query($conn, "INSERT INTO orders (id, user_id, bag_id, shop_id, price, status, pickup_code, delivery_type, shipping_address, shipping_phone, shipping_fee, close_at, created_at) VALUES 
        (10, 18, 12, 7, 37000.00, 'cho_nhan', 'SHIP01', 'delivery', 'Phòng 304 KTX Bách Khoa, P. Hòa Khánh Bắc, Liên Chiểu', '0905888999', 12000.00, '$time_in_4h', NOW())");
    mysqli_query($conn, "UPDATE bags SET stock = stock - 1 WHERE id = 12");
    mysqli_query($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES 
        (18, -37000.00, 'Thanh toán đơn túi #10 (Mã nhận: SHIP01) [Giao tận nơi: +12.000 đ]', 10, NOW())");

    // Đơn 4..9: 'hoan_thanh'
    mysqli_query($conn, "INSERT INTO orders (id, user_id, bag_id, shop_id, price, status, pickup_code, delivery_type, close_at, picked_up_at, hold_until, created_at) VALUES 
        (4, 2, 1, 1, 55000.00, 'hoan_thanh', 'SUC001', 'pickup', NOW(), NOW(), NOW(), DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (5, 3, 3, 2, 40000.00, 'hoan_thanh', 'SUC002', 'pickup', NOW(), NOW(), NOW(), DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (6, 4, 4, 3, 50000.00, 'hoan_thanh', 'SUC003', 'pickup', NOW(), NOW(), NOW(), DATE_SUB(NOW(), INTERVAL 2 DAY)),
        (7, 18, 12, 7, 25000.00, 'hoan_thanh', 'SUC007', 'pickup', NOW(), NOW(), NOW(), DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (8, 19, 8, 5, 30000.00, 'hoan_thanh', 'SUC008', 'pickup', NOW(), NOW(), NOW(), DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (9, 20, 11, 6, 35000.00, 'hoan_thanh', 'SUC009', 'pickup', NOW(), NOW(), NOW(), DATE_SUB(NOW(), INTERVAL 2 DAY))");

    mysqli_query($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES 
        (2, -55000.00, 'Thanh toán đơn #4', 4, DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (5, 55000.00, 'Nhận tiền đơn #4', 4, DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (3, -40000.00, 'Thanh toán đơn #5', 5, DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (6, 40000.00, 'Nhận tiền đơn #5', 5, DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (4, -50000.00, 'Thanh toán đơn #6', 6, DATE_SUB(NOW(), INTERVAL 2 DAY)),
        (7, 50000.00, 'Nhận tiền đơn #6', 6, DATE_SUB(NOW(), INTERVAL 2 DAY)),
        (18, -25000.00, 'Thanh toán đơn #7', 7, DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (11, 25000.00, 'Nhận tiền đơn #7', 7, DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (19, -30000.00, 'Thanh toán đơn #8', 8, DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (9, 30000.00, 'Nhận tiền đơn #8', 8, DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (20, -35000.00, 'Thanh toán đơn #9', 9, DATE_SUB(NOW(), INTERVAL 2 DAY)),
        (10, 35000.00, 'Nhận tiền đơn #9', 9, DATE_SUB(NOW(), INTERVAL 2 DAY))");

    // 7. Tạo Đánh giá 5 sao kèm Phân tích cảm xúc AI (reviews)
    mysqli_query($conn, "INSERT INTO reviews (order_id, user_id, shop_id, rating, comment, ai_sentiment, ai_tags, created_at) VALUES 
        (4, 2, 1, 5, 'Bánh Croissant bơ Pháp thơm nức mũi, vỏ giòn ruộm nhiều lớp. Giá 55k được tận 4 cái to, quá rẻ so với 110k gốc!', 'tich_cuc', '#BanhNgon #ThomBoPhap #GiaRe', DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (5, 3, 2, 5, 'Cơm tấm sườn bì chả đầy ú ụ, sườn mềm ướp rất vừa miệng. Sinh viên ăn no căng bụng cho bữa tối!', 'tich_cuc', '#ComTamSuon #DayUU #NoCangBung', DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (6, 4, 3, 4, 'Trà sữa ô long thơm đậm vị, trân châu mềm dẻo. Quán đóng gói túi giấy bảo vệ môi trường rất đẹp.', 'tich_cuc', '#TraSuaOLong #TranChauDeo #TuiBaoVeMoiTruong', DATE_SUB(NOW(), INTERVAL 2 DAY)),
        (7, 18, 7, 5, 'Cơm sườn KTX Bách Khoa cứu đói thần thánh! Chỉ 25k mà sườn miếng to, canh chua nóng hổi, sinh viên cực kỳ ưng ý.', 'tich_cuc', '#ComBachKhoa #25kSieuRe #CuuDoiDemKhuya', DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (8, 19, 5, 5, 'Bún chả cá Bà Lữ chuẩn vị Đà Nẵng, nước lèo ngọt thanh không bị tanh. Quán gói mang về rất sạch sẽ!', 'tich_cuc', '#BunChaCaDaNang #NuocLeoNgot #SachSe', DATE_SUB(NOW(), INTERVAL 1 DAY)),
        (9, 20, 6, 5, 'The Brown decor đẹp, combo bánh mì que với trà tắc uống sảng khoái. Nhân viên đón khách rất thân thiện.', 'tich_cuc', '#TheBrown #BanhMiQue #NhanVienNhietTinh', DATE_SUB(NOW(), INTERVAL 2 DAY))");

    // 8. Tạo các mã voucher trợ giá sinh viên
    mysqli_query($conn, "INSERT INTO vouchers (code, discount_amount, min_order_price, quantity, used_count, description, expires_at) VALUES 
        ('VKU2026', 10000.00, 30000.00, 200, 0, 'Giảm 10.000 đ cho sinh viên VKU & đối tác Đà Nẵng', '2026-12-31 23:59:59'),
        ('DANANGXANH', 15000.00, 40000.00, 150, 0, 'Quỹ Sống Xanh trợ giá 15.000 đ cho túi bất ngờ', '2026-12-31 23:59:59'),
        ('SINHVIENMOI', 5000.00, 20000.00, 500, 0, 'Ưu đãi chào bạn mới giảm ngay 5.000 đ', '2026-12-31 23:59:59')");

    mysqli_commit($conn);
    $message = 'ĐÃ TẠO TOÀN BỘ 105 TÚI BẤT NGỜ & 30 QUÁN ĐỐI TÁC PHỦ KÍN ĐÀ NẴNG KÈM TÍNH NĂNG ĐẶT SHIP TẬN NƠI THÀNH CÔNG!';
}

$page_title = 'Tạo dữ liệu mẫu';
require_once __DIR__ . '/header.php';
?>

<div style="max-width: 960px; margin: 30px auto; padding: 0 15px;">
    <div class="card" style="border-radius: 20px; box-shadow: 0 8px 30px rgba(0,0,0,0.06);">
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 12px;">
            <span style="font-size: 32px;">🌟</span>
            <div>
                <h2 class="card-title" style="color: var(--primary-color); margin: 0;">KHỞI TẠO ĐẠI DỮ LIỆU ĐỒ ÁN (105 TÚI & 30 QUÁN ĐÀ NẴNG)</h2>
                <small style="color: var(--text-muted); font-size: 13px;">Bao trùm 7 Quận/Huyện: Hải Châu, Thanh Khê, Liên Chiểu, Ngũ Hành Sơn, Sơn Trà, Cẩm Lệ, Hòa Vang</small>
            </div>
        </div>

        <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.6;">
            Nhấn nút bên dưới để tự động tạo <strong>30 Cửa Hàng Đối Tác</strong> và <strong>105 Túi Bất Ngờ</strong> với đủ mọi mức giá: từ siêu hạt dẻ <strong>17k, 18k, 19k</strong> cho đến các suất ăn thịnh soạn <strong>50k - 62k</strong>. Đã tích hợp sẵn <strong>Phương thức Đặt Ship Giao Tận Nơi</strong> và kiểm toán bảo toàn quỹ tiền Escrow 100% (Sai lệch đúng 0 đ).
        </p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success" style="font-size: 15px; font-weight: 700; border-radius: 12px; padding: 16px;">
                🎉 <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>tao_du_lieu_mau.php" method="POST" onsubmit="return confirm('Bạn có chắc muốn nạp 105 túi và 30 quán phủ khắp toàn bộ Đà Nẵng?');">
            <button type="submit" name="action_seed" value="1" class="btn btn-secondary" style="font-size: 16px; padding: 14px 28px; width: 100%; border-radius: 12px; font-weight: 800; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.4);">
                ⚡ BẤM ĐỂ NẠP TOÀN BỘ 105 TÚI & 30 QUÁN TẠI ĐÀ NẴNG
            </button>
        </form>

        <h3 style="margin-top: 30px; margin-bottom: 15px; color: #1e293b; font-size: 18px;">
            🔑 Danh Sách Tài Khoản Thử Nghiệm Nổi Bật
        </h3>

        <div class="table-responsive">
            <table class="table" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th>Vai trò</th>
                        <th>Họ tên & Đơn vị</th>
                        <th>Email</th>
                        <th>Mật khẩu</th>
                        <th>Số dư ví</th>
                        <th>Ghi chú kịch bản</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background: #f1f5f9;">
                        <td><span class="badge badge-purple">Admin</span></td>
                        <td><strong>Quản Trị Viên Đà Nẵng</strong></td>
                        <td><code>admin@giaicuu.vn</code></td>
                        <td><code>admin123</code></td>
                        <td>0 đ</td>
                        <td>Duyệt quán, xử lý khiếu nại, xem thống kê & kiểm quỹ</td>
                    </tr>
                    <tr style="background: #e8f5e9;">
                        <td><span class="badge badge-info">Sinh viên</span></td>
                        <td><strong>Nguyễn Văn An (VKU Đà Nẵng)</strong></td>
                        <td><code>sv1@giaicuu.vn</code></td>
                        <td><code>sv123</code></td>
                        <td><strong>195.000 đ</strong></td>
                        <td>Có sẵn Đơn #1 mã nhận <code>DEMO01</code> chờ đến quán lấy</td>
                    </tr>
                    <tr style="background: #e8f5e9;">
                        <td><span class="badge badge-info">Sinh viên</span></td>
                        <td><strong>Trần Thị Mai (Kinh Tế ĐN)</strong></td>
                        <td><code>sv2@giaicuu.vn</code></td>
                        <td><code>sv123</code></td>
                        <td><strong>260.000 đ</strong></td>
                        <td>Có sẵn Đơn #2 (đã nhận) để test bấm "Tôi hài lòng"</td>
                    </tr>
                    <tr style="background: #e8f5e9;">
                        <td><span class="badge badge-info">Sinh viên</span></td>
                        <td><strong>Lê Hoàng Phúc (Bách Khoa ĐN)</strong></td>
                        <td><code>sv3@giaicuu.vn</code></td>
                        <td><code>sv123</code></td>
                        <td><strong>150.000 đ</strong></td>
                        <td>Đơn #3 đang tranh chấp để test Cố Vấn Trọng Tài AI</td>
                    </tr>
                    <tr style="background: #e8f5e9;">
                        <td><span class="badge badge-info">Sinh viên</span></td>
                        <td><strong>Phan Minh Tuấn (Ngoại Ngữ ĐN)</strong></td>
                        <td><code>sv4@giaicuu.vn</code></td>
                        <td><code>sv123</code></td>
                        <td><strong>188.000 đ</strong></td>
                        <td>Có sẵn <strong>Đơn #10 Đặt Ship tận nơi</strong> (Mã <code>SHIP01</code>) KTX Bách Khoa</td>
                    </tr>
                    <tr style="background: #fff3e0;">
                        <td><span class="badge badge-warning">Chủ quán</span></td>
                        <td><strong>Tiệm Bánh BonBon (Hải Châu)</strong></td>
                        <td><code>tiembanh@giaicuu.vn</code></td>
                        <td><code>shop123</code></td>
                        <td>55.000 đ</td>
                        <td>Bánh Pháp Lê Duẩn. Dùng để test nhập mã nhận <code>DEMO01</code></td>
                    </tr>
                    <tr style="background: #fff3e0;">
                        <td><span class="badge badge-warning">Chủ quán</span></td>
                        <td><strong>Bếp KTX Bách Khoa (Liên Chiểu)</strong></td>
                        <td><code>bachkhoa@giaicuu.vn</code></td>
                        <td><code>shop123</code></td>
                        <td>25.000 đ</td>
                        <td>Có đơn #10 Đặt Ship giao KTX để xem địa chỉ & SĐT giao hàng</td>
                    </tr>
                    <tr style="background: #fff3e0;">
                        <td><span class="badge badge-warning">Chủ quán</span></td>
                        <td><strong>Bếp Sinh Viên Làng ĐH VKU (Hòa Quý)</strong></td>
                        <td><code>vku_quan@giaicuu.vn</code></td>
                        <td><code>shop123</code></td>
                        <td>0 đ</td>
                        <td>470 Trần Đại Nghĩa, đặc quyền sinh viên VKU săn đêm</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 25px; display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="/giaicuu/index.php" class="btn btn-primary">Về Trang Chủ Khám Phá 105 Túi</a>
            <a href="/giaicuu/ai_tro_ly.php" class="btn btn-secondary">Thử Hỏi AI GreenBot Gợi Ý</a>
            <a href="/giaicuu/admin/kiem_tra_tien.php" class="btn btn-outline">Kiểm Toán Cân Bằng Quỹ Escrow</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
