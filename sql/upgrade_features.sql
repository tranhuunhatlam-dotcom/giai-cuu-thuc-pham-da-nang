-- FILE: sql/upgrade_features.sql
-- Nâng cấp cơ sở dữ liệu: Hệ thống đánh giá 5 sao (reviews), Mã voucher trợ giá (vouchers), Trường Đại học sinh viên (university)

USE giaicuu;

-- 1. Bổ sung cột university cho bảng users (nếu chưa có)
ALTER TABLE users ADD COLUMN IF NOT EXISTS university VARCHAR(150) DEFAULT 'Đại học CNTT&TT Việt - Hàn (VKU)';

-- 2. Bổ sung cột voucher cho bảng orders (nếu chưa có)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS voucher_id INT DEFAULT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0.00;

-- 3. Bảng đánh giá & nhận xét 5 sao của sinh viên
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,
    shop_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng mã giảm giá voucher trợ giá sinh viên
CREATE TABLE IF NOT EXISTS vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    discount_amount DECIMAL(10,2) NOT NULL,
    min_order_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    quantity INT NOT NULL DEFAULT 100,
    used_count INT NOT NULL DEFAULT 0,
    description VARCHAR(255) DEFAULT '',
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Nạp sẵn 3 mã voucher hấp dẫn cho sinh viên Đà Nẵng
INSERT INTO vouchers (code, discount_amount, min_order_price, quantity, used_count, description, expires_at)
VALUES 
('VKU2026', 10000.00, 30000.00, 200, 0, 'Giảm 10.000 đ cho sinh viên VKU & đối tác Đà Nẵng', '2026-12-31 23:59:59'),
('DANANGXANH', 15000.00, 40000.00, 150, 0, 'Quỹ Sống Xanh trợ giá 15.000 đ cho túi bất ngờ', '2026-12-31 23:59:59'),
('SINHVIENMOI', 5000.00, 20000.00, 500, 0, 'Ưu đãi chào bạn mới giảm ngay 5.000 đ', '2026-12-31 23:59:59')
ON DUPLICATE KEY UPDATE 
discount_amount = VALUES(discount_amount),
min_order_price = VALUES(min_order_price),
expires_at = VALUES(expires_at);
