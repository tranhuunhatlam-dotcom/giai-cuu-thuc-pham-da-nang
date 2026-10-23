-- Schema for Food Rescue O2O Platform (Mô hình Túi Bất Ngờ)
-- Database: food_rescue_db
-- Engine: InnoDB with strict foreign keys and transactional locks

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS simulation_logs;
DROP TABLE IF EXISTS simulation_runs;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS wallet_transactions;
DROP TABLE IF EXISTS escrow_holds;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS surprise_bags;
DROP TABLE IF EXISTS stores;
DROP TABLE IF EXISTS wallets;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. BẢNG NGƯỜI DÙNG (Users)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'store_owner', 'admin') NOT NULL DEFAULT 'student',
    avatar VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. BẢNG VÍ TIỀN (Wallets - Ví nội bộ giả lập & Ví Tạm Giữ Escrow)
CREATE TABLE wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL UNIQUE, -- NULL nếu là ví tổng hệ thống / System Escrow Pool
    wallet_type ENUM('student', 'store', 'system_escrow') NOT NULL,
    balance DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    pending_escrow DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. BẢNG CỬA HÀNG ĐỐI TÁC (Stores - Bakery / Cafe tại Đà Nẵng)
CREATE TABLE stores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    store_name VARCHAR(150) NOT NULL,
    category ENUM('bakery', 'cafe', 'fastfood', 'restaurant') NOT NULL DEFAULT 'bakery',
    address VARCHAR(255) NOT NULL,
    district ENUM('Hải Châu', 'Thanh Khê', 'Liên Chiểu', 'Ngũ Hành Sơn', 'Sơn Trà', 'Cẩm Lệ') NOT NULL DEFAULT 'Hải Châu',
    city VARCHAR(50) NOT NULL DEFAULT 'Đà Nẵng',
    lat DECIMAL(10, 8) DEFAULT 16.0544,
    lng DECIMAL(11, 8) DEFAULT 108.2022,
    phone VARCHAR(20) NOT NULL,
    rating DECIMAL(2, 1) NOT NULL DEFAULT 4.8,
    image_url VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('approved', 'pending', 'rejected') NOT NULL DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_store_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. BẢNG TÚI BẤT NGỜ (Surprise Bags)
CREATE TABLE surprise_bags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Bánh ngọt & Mặn',
    original_price DECIMAL(12, 2) NOT NULL,
    rescue_price DECIMAL(12, 2) NOT NULL,
    quantity_available INT NOT NULL DEFAULT 0,
    initial_quantity INT NOT NULL DEFAULT 0,
    pickup_start TIME NOT NULL DEFAULT '19:30:00',
    pickup_end TIME NOT NULL DEFAULT '21:30:00',
    sale_date DATE NOT NULL,
    status ENUM('available', 'sold_out', 'expired', 'paused') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_store_date (store_id, sale_date),
    INDEX idx_quantity (quantity_available),
    CONSTRAINT fk_bag_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. BẢNG ĐƠN HÀNG O2O (Orders)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(32) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    store_id INT NOT NULL,
    bag_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total_amount DECIMAL(12, 2) NOT NULL,
    pickup_otp VARCHAR(6) NOT NULL,
    pickup_qr_token VARCHAR(64) NOT NULL UNIQUE,
    pickup_window_start DATETIME NOT NULL,
    pickup_window_end DATETIME NOT NULL,
    status ENUM('escrow_held', 'completed', 'cancelled_by_store', 'cancelled_by_student', 'no_show') NOT NULL DEFAULT 'escrow_held',
    pickup_verified_at DATETIME DEFAULT NULL,
    cancelled_reason TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_student (student_id),
    INDEX idx_store (store_id),
    CONSTRAINT fk_order_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_bag FOREIGN KEY (bag_id) REFERENCES surprise_bags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. BẢNG VÍ TẠM GIỮ (Escrow Holds - Quản lý rủi ro giao dịch O2O)
CREATE TABLE escrow_holds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    student_wallet_id INT NOT NULL,
    store_wallet_id INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    status ENUM('holding', 'released', 'refunded', 'no_show_settled') NOT NULL DEFAULT 'holding',
    held_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    released_at DATETIME DEFAULT NULL,
    note VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_escrow_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_escrow_student_wallet FOREIGN KEY (student_wallet_id) REFERENCES wallets(id),
    CONSTRAINT fk_escrow_store_wallet FOREIGN KEY (store_wallet_id) REFERENCES wallets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. BẢNG LỊCH SỬ GIAO DỊCH VÍ (Wallet Transactions - Kế toán kép / Audit Trail)
CREATE TABLE wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_id INT NOT NULL,
    order_id INT NULL,
    transaction_type ENUM('topup', 'escrow_hold', 'escrow_release', 'escrow_refund', 'payout', 'no_show_settle') NOT NULL,
    direction ENUM('credit', 'debit') NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    balance_before DECIMAL(12, 2) NOT NULL,
    balance_after DECIMAL(12, 2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    reference_code VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wallet (wallet_id),
    CONSTRAINT fk_wtrans_wallet FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    CONSTRAINT fk_wtrans_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. BẢNG ĐÁNH GIÁ (Reviews)
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    student_id INT NOT NULL,
    store_id INT NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. BẢNG KIỂM THỬ THỰC NGHIỆM ĐỒ HỌA (Simulation Runs & Concurrency Stress Test Logs)
CREATE TABLE simulation_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(100) NOT NULL,
    initial_stock INT NOT NULL,
    concurrent_requests INT NOT NULL,
    successful_orders INT NOT NULL,
    failed_requests INT NOT NULL,
    execution_time_ms DECIMAL(8, 2) NOT NULL,
    oversell_detected TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE simulation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    run_id INT NOT NULL,
    thread_number INT NOT NULL,
    student_id INT NOT NULL,
    requested_qty INT NOT NULL,
    status ENUM('SUCCESS', 'OUT_OF_STOCK', 'INSUFFICIENT_FUNDS', 'LOCK_TIMEOUT', 'FAILED') NOT NULL,
    order_id INT NULL,
    lock_wait_time_ms DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
    message VARCHAR(255) NOT NULL,
    timestamp_micro VARCHAR(32) NOT NULL,
    CONSTRAINT fk_sim_run FOREIGN KEY (run_id) REFERENCES simulation_runs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
