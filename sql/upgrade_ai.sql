-- FILE: sql/upgrade_ai.sql
-- Mục đích: Bổ sung cấu hình AI và các trường dữ liệu AI phân tích cảm xúc & trọng tài

-- 1. Nới rộng trường value của settings để chứa API Key
ALTER TABLE settings MODIFY COLUMN `value` VARCHAR(255) NOT NULL;

-- 2. Thêm cấu hình Gemini API Key và AI Mode vào settings nếu chưa có
INSERT INTO settings (name, value)
SELECT 'gemini_api_key', ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'gemini_api_key');

INSERT INTO settings (name, value)
SELECT 'ai_assistant_mode', 'hybrid'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE name = 'ai_assistant_mode');

-- 3. Bổ sung trường phân tích cảm xúc AI vào bảng reviews
ALTER TABLE reviews 
ADD COLUMN IF NOT EXISTS ai_sentiment VARCHAR(30) DEFAULT 'tich_cuc',
ADD COLUMN IF NOT EXISTS ai_tags VARCHAR(255) DEFAULT '#NgonRe #HaiLong';

-- 4. Bổ sung trường khuyến nghị AI trọng tài vào bảng disputes
ALTER TABLE disputes 
ADD COLUMN IF NOT EXISTS ai_recommendation TEXT NULL;

-- 5. Cập nhật nhãn phân tích cảm xúc cho các review mẫu hiện tại
UPDATE reviews 
SET ai_sentiment = 'tich_cuc', 
    ai_tags = '#BanhNgon #ThomBoPhap #GiaRe' 
WHERE id = 1;

UPDATE reviews 
SET ai_sentiment = 'tich_cuc', 
    ai_tags = '#ComTamSuon #DayUU #NoCangBung' 
WHERE id = 2;

UPDATE reviews 
SET ai_sentiment = 'tich_cuc', 
    ai_tags = '#TraSuaOLong #TranChauDeo #TuiBaoVeMoiTruong' 
WHERE id = 3;
