-- FILE: sql/upgrade_payment_methods.sql
-- Mục đích: Bổ sung phương thức thanh toán Tiền mặt (Cash) và Chuyển khoản qua Mã QR (VietQR)

ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS payment_method ENUM('wallet', 'cash', 'bank_qr') NOT NULL DEFAULT 'wallet',
ADD COLUMN IF NOT EXISTS payment_status ENUM('da_thanh_toan', 'cho_xac_nhan', 'chua_thanh_toan') NOT NULL DEFAULT 'da_thanh_toan';
