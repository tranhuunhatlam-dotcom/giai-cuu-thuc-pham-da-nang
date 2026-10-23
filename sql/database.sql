-- CƠ SỞ DỮ LIỆU "GIẢI CỨU THỰC PHẨM CUỐI NGÀY" (MÔ HÌNH TÚI BẤT NGỜ - ĐÀ NẴNG)
-- Toàn bộ bảng sử dụng Engine InnoDB để hỗ trợ Transaction (Giao dịch ACID)

CREATE DATABASE IF NOT EXISTS `giaicuu` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `giaicuu`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `wallet_logs`;
DROP TABLE IF EXISTS `disputes`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `bags`;
DROP TABLE IF EXISTS `shops`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Bảng người dùng (users)
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'shop_owner', 'admin') NOT NULL DEFAULT 'student',
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `warning_count` INT NOT NULL DEFAULT 0,
    `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng cửa hàng (shops)
CREATE TABLE `shops` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `owner_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `district` VARCHAR(50) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `violation_count` INT NOT NULL DEFAULT 0,
    `status` ENUM('cho_duyet', 'da_duyet') NOT NULL DEFAULT 'cho_duyet',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_shops_owner` FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng túi bất ngờ (bags)
CREATE TABLE `bags` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `shop_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `original_price` DECIMAL(12,2) NOT NULL,
    `web_price` DECIMAL(12,2) NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `open_at` DATETIME NOT NULL,
    `pickup_start` DATETIME NOT NULL,
    `pickup_end` DATETIME NOT NULL,
    `close_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bags_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng đơn hàng (orders)
-- Các trạng thái cho phép:
-- 'cho_nhan', 'da_nhan', 'hoan_thanh', 'tranh_chap', 'hoan_tien_sv', 'chuyen_tien_quan', 'huy_boi_khach', 'huy_vang_mat'
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `bag_id` INT NOT NULL,
    `shop_id` INT NOT NULL,
    `price` DECIMAL(12,2) NOT NULL,
    `status` ENUM('cho_nhan', 'da_nhan', 'hoan_thanh', 'tranh_chap', 'hoan_tien_sv', 'chuyen_tien_quan', 'huy_boi_khach', 'huy_vang_mat') NOT NULL DEFAULT 'cho_nhan',
    `pickup_code` VARCHAR(10) NOT NULL UNIQUE,
    `delivery_type` ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup',
    `shipping_address` VARCHAR(255) DEFAULT NULL,
    `shipping_phone` VARCHAR(20) DEFAULT NULL,
    `shipping_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `voucher_id` INT DEFAULT NULL,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_method` ENUM('wallet', 'cash', 'bank_qr') NOT NULL DEFAULT 'wallet',
    `payment_status` ENUM('da_thanh_toan', 'cho_xac_nhan', 'chua_thanh_toan') NOT NULL DEFAULT 'da_thanh_toan',
    `close_at` DATETIME NOT NULL,
    `picked_up_at` DATETIME DEFAULT NULL,
    `hold_until` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_orders_bag` FOREIGN KEY (`bag_id`) REFERENCES `bags`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_orders_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng tranh chấp / khiếu nại (disputes)
CREATE TABLE `disputes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL UNIQUE,
    `reason` VARCHAR(255) NOT NULL,
    `student_note` TEXT DEFAULT NULL,
    `student_image` VARCHAR(255) NOT NULL,
    `shop_note` TEXT DEFAULT NULL,
    `shop_image` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('cho_shop_phan_hoi', 'cho_admin_xu_ly', 'hoan_tien_sv', 'chuyen_tien_quan') NOT NULL DEFAULT 'cho_shop_phan_hoi',
    `admin_note` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_disputes_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bảng nhật ký ví tiền (wallet_logs)
CREATE TABLE `wallet_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `reason` VARCHAR(255) NOT NULL,
    `order_id` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_wallet_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Bảng thông báo (notifications)
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Bảng cấu hình hệ thống (settings)
CREATE TABLE `settings` (
    `name` VARCHAR(50) PRIMARY KEY,
    `value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu cấu hình mặc định theo đặc tả
INSERT INTO `settings` (`name`, `value`) VALUES
('discount_min', '30'),
('discount_max', '50'),
('cancel_before_close_minutes', '90'),
('hold_minutes', '45'),
('dispute_response_hours', '48'),
('max_bags_per_user', '2'),
('max_disputes_per_month', '3'),
('gemini_api_key', ''),
('ai_assistant_mode', 'hybrid');

-- 9. Bảng đánh giá 5 sao từ sinh viên (reviews)
CREATE TABLE `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `shop_id` INT NOT NULL,
    `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `comment` TEXT,
    `ai_sentiment` VARCHAR(30) DEFAULT 'tich_cuc',
    `ai_tags` VARCHAR(255) DEFAULT '#NgonRe #HaiLong',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reviews_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bảng mã giảm giá trợ giá sinh viên (vouchers)
CREATE TABLE `vouchers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(30) NOT NULL UNIQUE,
    `discount_amount` DECIMAL(10,2) NOT NULL,
    `min_order_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `quantity` INT NOT NULL DEFAULT 100,
    `used_count` INT NOT NULL DEFAULT 0,
    `description` VARCHAR(255) DEFAULT '',
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo 1 tài khoản Admin mặc định (mật khẩu: admin123)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `balance`, `warning_count`, `is_locked`) VALUES
(1, 'Quản Trị Viên', 'admin@giaicuu.vn', '$2y$10$ywCmoCNDKaaa6l.g7VsRbuX1N8oTMpiQySi7xsnN53RHJrmygflLy', 'admin', 0.00, 0, 0);

-- Nạp sẵn 3 mã voucher ban đầu
INSERT INTO `vouchers` (`code`, `discount_amount`, `min_order_price`, `quantity`, `used_count`, `description`, `expires_at`) VALUES
('VKU2026', 10000.00, 30000.00, 200, 0, 'Giảm 10.000 đ cho sinh viên VKU & đối tác Đà Nẵng', '2026-12-31 23:59:59'),
('DANANGXANH', 15000.00, 40000.00, 150, 0, 'Quỹ Sống Xanh trợ giá 15.000 đ cho túi bất ngờ', '2026-12-31 23:59:59'),
('SINHVIENMOI', 5000.00, 20000.00, 500, 0, 'Ưu đãi chào bạn mới giảm ngay 5.000 đ', '2026-12-31 23:59:59');

