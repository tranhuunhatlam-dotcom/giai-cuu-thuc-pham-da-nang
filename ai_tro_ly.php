<?php
// FILE: ai_tro_ly.php
// Mục đích: Trợ lý AI Thông Minh "GreenBot" - Cố vấn ẩm thực & bảo quản thực phẩm cuối ngày cho sinh viên
// Sử dụng Hybrid AI Engine: Gemini 1.5 Flash + Heuristic NLP nội bộ Đà Nẵng

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/services/ai_service.php';

$question = trim($_POST['question'] ?? ($_GET['q'] ?? ''));
$budget = (float)($_POST['budget'] ?? ($_GET['budget'] ?? 0));
$district = trim($_POST['district'] ?? ($_GET['district'] ?? ''));

$ai_result = null;
if (!empty($question) || $budget > 0) {
    if (empty($question) && $budget > 0) {
        $question = "Tôi có ngân sách khoảng " . number_format($budget, 0, ',', '.') . " đ, hãy gợi ý cho tôi túi bất ngờ phù hợp tại Đà Nẵng.";
    }
    $ai_result = ai_student_advisor($conn, $question, $budget, $district);
}

$page_title = 'Trợ Lý AI Ẩm Thực Xanh - GreenBot';
require_once __DIR__ . '/header.php';
?>

<div style="max-width: 1000px; margin: 30px auto; padding: 0 15px;">
    <!-- Banner Header Trợ lý AI -->
    <div style="background: linear-gradient(135deg, #0f172a 0%, #064e3b 50%, #047857 100%); border-radius: 20px; padding: 36px 30px; color: #ffffff; position: relative; overflow: hidden; box-shadow: 0 12px 30px rgba(4, 120, 87, 0.25); margin-bottom: 30px;">
        <div style="position: absolute; right: -20px; bottom: -20px; font-size: 150px; opacity: 0.12; pointer-events: none;">🤖</div>
        <div style="display: flex; align-items: center; gap: 18px; margin-bottom: 12px;">
            <div style="width: 58px; height: 58px; border-radius: 16px; background: rgba(16, 185, 129, 0.2); border: 2px solid #34d399; display: flex; align-items: center; justify-content: center; font-size: 30px; box-shadow: 0 0 20px rgba(52, 211, 153, 0.4);">
                🤖
            </div>
            <div>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(52, 211, 153, 0.2); border: 1px solid #34d399; padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px; color: #6ee7b7; text-transform: uppercase; margin-bottom: 4px;">
                    ✨ Hybrid AI Engine • Google Gemini 1.5 + Eco-Heuristic
                </div>
                <h1 style="font-size: 26px; font-weight: 800; margin: 0; letter-spacing: -0.5px;">Cố Vấn Ẩm Thực Thông Minh GreenBot</h1>
            </div>
        </div>
        <p style="font-size: 14.5px; color: #d1fae5; max-width: 780px; line-height: 1.6; margin: 0;">
            Hỏi đáp mọi lúc về cách chọn túi bất ngờ theo ngân sách sinh viên, tính toán lượng tiền & khí thải $\text{CO}_2$ tiết kiệm được, cùng cẩm nang bảo quản & hâm nóng thực phẩm cuối ngày an toàn từ các chuyên gia dinh dưỡng.
        </p>
    </div>

    <!-- Khung Tương Tác Hỏi Đáp -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 24px; margin-bottom: 35px;">
        <div class="card" style="border: 2px solid #e2e8f0; border-radius: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.04);">
            <form action="ai_tro_ly.php" method="POST" style="margin-bottom: 0;">
                <label style="display: block; font-weight: 700; margin-bottom: 10px; color: #1e293b; font-size: 15px;">
                    💬 Nhập câu hỏi hoặc yêu cầu của bạn:
                </label>
                <div style="margin-bottom: 16px;">
                    <textarea name="question" rows="3" class="form-control" style="font-size: 14.5px; border-radius: 12px; padding: 12px 16px; border: 1.5px solid #cbd5e1;" placeholder="Ví dụ: Tôi có 35k tại quận Ngũ Hành Sơn, nên chọn món gì? Hoặc: Bánh croissant để qua đêm hâm nóng lại bằng cách nào cho giòn?"><?= htmlspecialchars($question) ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 18px;">
                    <div>
                        <label style="font-size: 12.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">
                            💰 Ngân sách tối đa (VNĐ):
                        </label>
                        <input type="number" name="budget" step="5000" min="0" value="<?= $budget > 0 ? htmlspecialchars($budget) : '' ?>" placeholder="VD: 35000" class="form-control" style="border-radius: 10px;">
                    </div>
                    <div>
                        <label style="font-size: 12.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">
                            📍 Khu vực tại TP. Đà Nẵng:
                        </label>
                        <select name="district" class="form-control" style="border-radius: 10px;">
                            <option value="">-- Toàn thành phố --</option>
                            <option value="Hải Châu" <?= $district === 'Hải Châu' ? 'selected' : '' ?>>Hải Châu (Trung tâm)</option>
                            <option value="Thanh Khê" <?= $district === 'Thanh Khê' ? 'selected' : '' ?>>Thanh Khê (Gần Bách Khoa / Sư Phạm)</option>
                            <option value="Sơn Trà" <?= $district === 'Sơn Trà' ? 'selected' : '' ?>>Sơn Trà (Bờ biển)</option>
                            <option value="Ngũ Hành Sơn" <?= $district === 'Ngũ Hành Sơn' ? 'selected' : '' ?>>Ngũ Hành Sơn (Gần VKU / Đô Thị ĐHĐN / ĐH Kinh Tế)</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 15px; border-radius: 12px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); font-weight: 800; border: none; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);">
                        🚀 Hỏi AI GreenBot Ngay
                    </button>
                    <span style="font-size: 13px; color: #64748b;">Thời gian xử lý: &lt; 0.5 giây</span>
                </div>
            </form>

            <!-- Gợi ý câu hỏi nhanh (Quick Prompts) -->
            <div style="margin-top: 20px; padding-top: 18px; border-top: 1px dashed #e2e8f0;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-right: 8px;">Gợi ý câu hỏi nhanh:</span>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                    <a href="ai_tro_ly.php?q=Tôi+có+ngân+sách+35k+ở+quận+Ngũ+Hành+Sơn+gần+VKU+thì+nên+mua+gì?&budget=35000&district=Ngũ+Hành+Sơn" class="badge badge-gray" style="text-decoration: none; padding: 6px 12px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 20px;">
                        💡 Tôi có 35k gần VKU ăn gì?
                    </a>
                    <a href="ai_tro_ly.php?q=Bánh+croissant+và+bánh+ngọt+để+qua+đêm+hâm+lại+như+thế+nào+cho+giòn+ngon?" class="badge badge-gray" style="text-decoration: none; padding: 6px 12px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 20px;">
                        🥐 Mẹo bảo quản bánh ngọt qua đêm
                    </a>
                    <a href="ai_tro_ly.php?q=Cơm+tấm+cuối+ngày+để+tủ+lạnh+bảo+quản+được+mấy+ngày+và+hâm+thế+nào?" class="badge badge-gray" style="text-decoration: none; padding: 6px 12px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 20px;">
                        🍱 Cách hâm cơm tấm không bị khô
                    </a>
                    <a href="ai_tro_ly.php?q=Sinh+viên+mua+túi+giải+cứu+thì+tiết+kiệm+được+bao+nhiêu+và+giảm+phát+thải+thế+nào?" class="badge badge-gray" style="text-decoration: none; padding: 6px 12px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 20px;">
                        🌍 Cứu 1 túi giảm bao nhiêu CO2?
                    </a>
                </div>
            </div>
        </div>

        <!-- Kết Quả Phản Hồi Từ AI -->
        <?php if ($ai_result): ?>
            <div class="card" style="border: 2px solid #34d399; border-radius: 18px; background: #f0fdf4; box-shadow: 0 6px 24px rgba(16, 185, 129, 0.12);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1.5px solid #d1fae5; padding-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 24px;">🌱</span>
                        <strong style="color: #065f46; font-size: 16px;">GreenBot Đã Phản Hồi:</strong>
                    </div>
                    <span class="badge badge-success" style="font-size: 11.5px; padding: 5px 12px; border-radius: 20px;">
                        ⚡ Động cơ: <?= htmlspecialchars($ai_result['engine']) ?>
                    </span>
                </div>

                <div style="font-size: 15px; color: #1e293b; line-height: 1.8; white-space: pre-wrap; font-family: inherit; margin-bottom: 24px;">
                    <?= nl2br(htmlspecialchars($ai_result['reply'])) ?>
                </div>

                <!-- Danh sách túi thực tế phù hợp từ database -->
                <?php if (!empty($ai_result['matched_bags'])): ?>
                    <div style="background: #ffffff; border-radius: 14px; padding: 20px; border: 1px solid #a7f3d0;">
                        <h4 style="font-size: 15px; color: #047857; margin-top: 0; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                            <span>🎯</span> CÁC TÚI BẤT NGỜ PHÙ HỢP CÒN HÀNG TRÊN HỆ THỐNG:
                        </h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px;">
                            <?php foreach ($ai_result['matched_bags'] as $bag): 
                                $discount = round((1 - ($bag['web_price'] / $bag['original_price'])) * 100);
                            ?>
                                <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; background: #f8fafc; display: flex; flex-direction: column; justify-content: space-between;">
                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                                            <strong style="font-size: 14.5px; color: #0f172a;"><?= htmlspecialchars($bag['name']) ?></strong>
                                            <span class="badge badge-danger" style="font-size: 11px;">-<?= $discount ?>%</span>
                                        </div>
                                        <div style="font-size: 12.5px; color: #475569; margin-bottom: 8px;">
                                            🏪 <?= htmlspecialchars($bag['shop_name']) ?> (<?= htmlspecialchars($bag['district']) ?>)
                                        </div>
                                        <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 10px;">
                                            <span style="font-size: 16px; font-weight: 800; color: #059669;">
                                                <?= number_format($bag['web_price'], 0, ',', '.') ?> đ
                                            </span>
                                            <span style="font-size: 12px; color: #94a3b8; text-decoration: line-through;">
                                                <?= number_format($bag['original_price'], 0, ',', '.') ?> đ
                                            </span>
                                        </div>
                                    </div>
                                    <a href="tui_chi_tiet.php?id=<?= $bag['id'] ?>" class="btn btn-primary" style="padding: 7px 12px; font-size: 13px; text-align: center; border-radius: 8px; text-decoration: none;">
                                        👉 Đặt Giữ Túi Ngay
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3 Cam kết cốt lõi của GreenBot -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <div class="card" style="border-radius: 14px; text-align: center; padding: 22px 18px;">
            <div style="font-size: 34px; margin-bottom: 10px;">🛡️</div>
            <h4 style="font-size: 15px; margin-bottom: 8px; color: #0f172a;">Ký Quỹ Escrow 100%</h4>
            <p style="font-size: 13px; color: #64748b; margin: 0; line-height: 1.6;">
                Tiền đặt cọc được giữ an toàn tại hệ thống, chỉ giải ngân cho quán khi sinh viên xác nhận hài lòng.
            </p>
        </div>
        <div class="card" style="border-radius: 14px; text-align: center; padding: 22px 18px;">
            <div style="font-size: 34px; margin-bottom: 10px;">📉</div>
            <h4 style="font-size: 15px; margin-bottom: 8px; color: #0f172a;">Trợ Giá 30% - 50%</h4>
            <p style="font-size: 13px; color: #64748b; margin: 0; line-height: 1.6;">
                Quy định thuật toán bắt buộc mọi túi bất ngờ phải giảm tối thiểu 30% so với giá bán lẻ offline.
            </p>
        </div>
        <div class="card" style="border-radius: 14px; text-align: center; padding: 22px 18px;">
            <div style="font-size: 34px; margin-bottom: 10px;">🌿</div>
            <h4 style="font-size: 15px; margin-bottom: 8px; color: #0f172a;">Giảm Thiểu Lãng Phí</h4>
            <p style="font-size: 13px; color: #64748b; margin: 0; line-height: 1.6;">
                Mỗi bữa ăn được cứu giúp thành phố Đà Nẵng bớt đi gánh nặng rác thải hữu cơ tại bãi rác Khánh Sơn.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
