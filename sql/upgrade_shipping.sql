-- FILE: sql/upgrade_shipping.sql
-- Mục đích: Bổ sung tính năng Đặt Ship Giao Hàng Tận Nơi vào hệ thống đơn hàng

ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS delivery_type ENUM('pickup', 'delivery') NOT NULL DEFAULT 'pickup',
ADD COLUMN IF NOT EXISTS shipping_address VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS shipping_phone VARCHAR(20) NULL,
ADD COLUMN IF NOT EXISTS shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00;
