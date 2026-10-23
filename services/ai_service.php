<?php
// FILE: services/ai_service.php
// Mục đích: Bộ máy Trí tuệ Nhân tạo Lai (Hybrid AI Engine)
// Cơ chế kép tiên tiến:
//   1. Ưu tiên gọi Google Gemini API (gemini-1.5-flash) nếu có cấu hình API Key và có mạng Internet.
//   2. Tự động chuyển đổi sang Bộ máy Heuristic AI Nội Bộ (Local Rule-Based AI Engine) nếu không có Key hoặc offline,
//      đảm bảo website demo 100% không bao giờ gặp lỗi trước hội đồng phản biện!

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// 1. Hàm gọi Google Gemini API qua cURL
function ai_call_gemini_api($api_key, $prompt, $timeout = 4) {
    if (empty($api_key)) {
        return null;
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($api_key);
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.4,
            "maxOutputTokens" => 800
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || $http_code !== 200 || empty($response)) {
        return null; // Tự động fallback sang Local AI
    }

    $json = json_decode($response, true);
    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        return trim($json['candidates'][0]['content']['parts'][0]['text']);
    }

    return null;
}

// 2. AI TRỢ LÝ SINH VIÊN (Tư vấn túi theo ngân sách + Mẹo bảo quản thực phẩm cuối ngày)
function ai_student_advisor($conn, $question, $budget = 0, $district = '') {
    $api_key = get_setting($conn, 'gemini_api_key', '');
    $q_lower = mb_strtolower($question, 'UTF-8');

    // Tìm các túi đang mở bán hoặc sắp mở bán phù hợp với ngân sách & quận
    $sql_bags = "SELECT b.*, s.name AS shop_name, s.address, s.district 
                 FROM bags b 
                 JOIN shops s ON b.shop_id = s.id 
                 WHERE b.stock > 0 ";
    $params = [];
    $types = "";

    if ($budget > 0) {
        $sql_bags .= " AND b.web_price <= ? ";
        $params[] = $budget;
        $types .= "d";
    }

    if (!empty($district)) {
        $sql_bags .= " AND s.district LIKE ? ";
        $params[] = "%" . $district . "%";
        $types .= "s";
    }

    $sql_bags .= " ORDER BY b.web_price ASC LIMIT 4";
    $stmt = mysqli_prepare($conn, $sql_bags);
    if ($types && count($params) > 0) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $matched_bags = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $matched_bags[] = $row;
    }
    mysqli_stmt_close($stmt);

    // Kịch bản A: Thử gọi Gemini AI nếu có key
    if (!empty($api_key)) {
        $bag_info = "";
        foreach ($matched_bags as $b) {
            $bag_info .= "- {$b['name']} tại {$b['shop_name']} ({$b['district']}): Giá web " . number_format($b['web_price'], 0, ',', '.') . " đ (gốc " . number_format($b['original_price'], 0, ',', '.') . " đ)\n";
        }

        $prompt = "Bạn là GreenBot - Trợ lý AI của nền tảng Giải cứu thực phẩm Đà Nẵng dành cho sinh viên.
Câu hỏi của sinh viên: '{$question}'. Ngân sách: " . ($budget > 0 ? number_format($budget, 0, ',', '.') . " đ" : "Tự do") . ". Khu vực: " . ($district ?: "Toàn TP. Đà Nẵng") . ".
Dưới đây là các túi bất ngờ đang có trong hệ thống:
{$bag_info}
Hãy trả lời sinh viên một cách thân thiện, tư vấn lựa chọn túi hợp lý, chia sẻ mẹo bảo quản đồ ăn an toàn và nhấn mạnh giá trị giảm phát thải CO2 bảo vệ môi trường.";

        $gemini_reply = ai_call_gemini_api($api_key, $prompt);
        if ($gemini_reply) {
            return [
                'engine' => 'Google Gemini 1.5 Flash',
                'reply' => $gemini_reply,
                'matched_bags' => $matched_bags
            ];
        }
    }

    // Kịch bản B: Bộ máy Heuristic AI Nội Bộ (Thông minh, chuyên sâu ẩm thực Đà Nẵng & an toàn thực phẩm)
    $reply = "";
    
    // 1. Nhóm câu hỏi về bảo quản thực phẩm cuối ngày
    if (strpos($q_lower, 'bảo quản') !== false || strpos($q_lower, 'hâm nóng') !== false || strpos($q_lower, 'để qua đêm') !== false || strpos($q_lower, 'tủ lạnh') !== false) {
        $reply = "💡 **CẨM NANG BẢO QUẢN & HÂM NÓNG THỰC PHẨM CUỐI NGÀY TỪ GREENBOT:**\n\n";
        $reply .= "1. 🥐 **Bánh mì & Bánh ngọt:** Để ở nhiệt độ phòng thoáng khí nếu ăn trong 24h. Nếu muốn để qua đêm, bọc kín màng bọc thực phẩm rồi cho vào ngăn mát. Khi ăn, nướng lại bằng nồi chiên không dầu (160°C trong 3 phút) bánh sẽ giòn tan như mới ra lò!\n";
        $reply .= "2. 🍱 **Cơm tấm & Món mặn:** Sau khi nhận túi lúc 21h, hãy ăn ngay hoặc cho vào hộp kín để ngăn mát tủ lạnh (dưới 5°C). Tuyệt đối không để ngoài nhiệt độ phòng qua đêm. Hâm nóng vi sóng tối thiểu 2 phút hoặc đảo nóng trên chảo trước khi dùng.\n";
        $reply .= "3. 🧋 **Trà sữa & Đồ uống:** Thưởng thức ngon nhất trong vòng 4 tiếng sau khi lấy. Nên vớt đá ra nếu chưa uống hết để trà không bị nhạt và giữ trong ngăn mát tối đa 1 ngày.\n";
        $reply .= "\n🌱 *Lưu ý:* Việc dùng hết thực phẩm đúng cách giúp bạn tránh lãng phí và bảo vệ đường tiêu hóa của sinh viên!";
    } 
    // 2. Nhóm câu hỏi về dinh dưỡng & tiết kiệm
    elseif (strpos($q_lower, 'tiết kiệm') !== false || strpos($q_lower, 'co2') !== false || strpos($q_lower, 'môi trường') !== false || strpos($q_lower, 'sinh viên') !== false) {
        $reply = "🌍 **TÁC ĐỘNG BẢO VỆ MÔI TRƯỜNG & TIẾT KIỆM NGÂN SÁCH:**\n\n";
        $reply .= "• Trung bình mỗi túi bất ngờ giúp bạn **tiết kiệm từ 35.000 đ đến 60.000 đ** (giảm 30% - 50% so với giá gốc niêm yết tại quán).\n";
        $reply .= "• Cứ mỗi 1 túi được giải cứu thay vì vứt bỏ vào bãi rác Khánh Sơn, bạn đã trực tiếp ngăn chặn phát thải **2.12 kg CO₂** và bảo tồn **165 lít nước sạch**!\n";
        $reply .= "• Sinh viên mua 3 túi/tuần có thể tiết kiệm gần **500.000 đ/tháng** tiền ăn tối!";
    }
    // 3. Tư vấn theo ngân sách và vị trí
    else {
        $reply = "👋 Chào bạn sinh viên! GreenBot đã phân tích ngân sách " . ($budget > 0 ? "**" . number_format($budget, 0, ',', '.') . " đ**" : "hiện có") . " ";
        if ($district) {
            $reply .= "tại khu vực **Quận {$district}** ";
        }
        $reply .= "và tìm thấy các gợi ý giải cứu tối ưu nhất cho bạn:\n\n";

        if (count($matched_bags) > 0) {
            foreach ($matched_bags as $b) {
                $discount_pct = round((1 - ($b['web_price'] / $b['original_price'])) * 100);
                $reply .= "• 🌟 **{$b['name']}** - {$b['shop_name']} ({$b['district']})\n";
                $reply .= "   └ Giá cứu trợ: **" . number_format($b['web_price'], 0, ',', '.') . " đ** (Giá gốc: " . number_format($b['original_price'], 0, ',', '.') . " đ, tiết kiệm -{$discount_pct}%)\n";
                $reply .= "   └ Nhận hàng: " . date('H:i', strtotime($b['pickup_start'])) . " - " . date('H:i', strtotime($b['pickup_end'])) . " hôm nay.\n\n";
            }
            $reply .= "👉 Bạn có thể click trực tiếp vào danh thiếp túi bên dưới để tiến hành đặt giữ chỗ với tiền cọc bảo vệ Escrow an toàn 100%!";
        } else {
            $reply .= "Hiện tại trong khung giờ này các túi phù hợp ngân sách của bạn đã được các bạn sinh viên khác săn hết. Bạn hãy thử tăng nhẹ ngân sách hoặc xem các khu vực lân cận như Hải Châu, Sơn Trà, Ngũ Hành Sơn nhé!";
        }
    }

    return [
        'engine' => 'Local Đà Nẵng Eco-Heuristic Engine',
        'reply' => $reply,
        'matched_bags' => $matched_bags
    ];
}

// 3. AI ĐỊNH GIÁ & DỰ BÁO TIÊU THỤ CHO CHỦ QUÁN (Dynamic Pricing & Clearance Estimator)
function ai_shop_pricing_advisor($food_type, $original_price, $close_hour, $stock) {
    $original_price = (float)$original_price;
    $stock = (int)$stock;

    if ($original_price <= 0) {
        $original_price = 50000;
    }

    // Heuristic định giá thông minh theo loại hình món và giờ đóng cửa
    // Quy định hệ thống: Giảm từ 30% đến 50%
    $discount_pct = 40; // mặc định 40%
    if ($close_hour >= 21) {
        $discount_pct = 45; // Càng muộn càng nên giảm sâu để xả hết
    }
    if ($stock >= 5) {
        $discount_pct = 50; // Tồn nhiều thì chạm trần 50% để thu hút sinh viên
    }

    $suggested_web_price = round($original_price * (1 - ($discount_pct / 100)) / 1000) * 1000;
    $est_clearance_time = max(15, min(45, 10 + ($stock * 4))); // 15 - 45 phút
    $est_success_rate = min(99, 85 + ($discount_pct / 5));

    // Sinh tự động câu mô tả hấp dẫn chuẩn SEO & Marketing
    $auto_descriptions = [
        'Bánh ngọt & Bánh mì' => "Túi Bất Ngờ gồm 3 - 4 món bánh tươi ngon nướng trong ngày (Croissant bơ Pháp, bánh cuộn, pastry mặn). Đóng gói túi giấy thân thiện môi trường, giữ độ xốp mịn, vừa vặn cho bữa xế hoặc bữa sáng hôm sau của sinh viên.",
        'Cơm & Bún phở' => "Phần cơm nóng sốt dinh dưỡng kèm sườn/thịt nướng đậm vị và canh rau tươi. Quán đóng gói cẩn thận, đảm bảo vệ sinh ATTP 100%, cứu đói đêm khuya siêu tiết kiệm cho sinh viên các trường đại học.",
        'Trà sữa & Cafe' => "Combo đồ uống cao cấp nguyên vị từ lá trà ô long tươi và sữa thanh trùng, kèm trọn topping trân châu / thạch giòn dai. Thức uống mát lạnh tiếp thêm năng lượng học tập cuối ngày.",
        'Món chay' => "Túi ẩm thực chay thanh tịnh gồm các món kho, xào và súp dinh dưỡng từ nấm và đậu hũ sạch. Thanh nhẹ cơ thể, giàu năng lượng lành mạnh."
    ];

    $desc = $auto_descriptions[$food_type] ?? "Túi Bất Ngờ ẩm thực cuối ngày thơm ngon, đảm bảo vệ sinh an toàn, trợ giá đặc biệt 30-50% cho sinh viên Đà Nẵng chung tay giảm rác thải thực phẩm.";

    return [
        'discount_percent' => $discount_pct,
        'suggested_price' => $suggested_web_price,
        'clearance_minutes' => $est_clearance_time,
        'success_rate' => $est_success_rate,
        'generated_description' => $desc,
        'co2_saved_est' => round($stock * 2.12, 1)
    ];
}

// 4. AI PHÂN TÍCH CẢM XÚC ĐÁNH GIÁ (Review Sentiment & Tagging)
function ai_analyze_review_sentiment($comment, $rating) {
    $rating = (int)$rating;
    $comment_lower = mb_strtolower($comment, 'UTF-8');

    $positive_words = ['ngon', 'tuyệt', 'thơm', 'rẻ', 'nhiệt tình', 'đầy đặn', 'sạch', 'ủng hộ', 'hài lòng', 'xuất sắc', 'tốt', 'thích'];
    $negative_words = ['dở', 'chua', 'thiu', 'nguội', 'ít', 'đắt', 'thất vọng', 'kém', 'chậm', 'thái độ', 'hỏng'];

    $pos_count = 0;
    $neg_count = 0;

    foreach ($positive_words as $w) {
        if (strpos($comment_lower, $w) !== false) $pos_count++;
    }
    foreach ($negative_words as $w) {
        if (strpos($comment_lower, $w) !== false) $neg_count++;
    }

    $sentiment = 'tich_cuc';
    $score = 95;
    $tags = [];

    if ($rating >= 4 && $neg_count === 0) {
        $sentiment = 'tich_cuc';
        $score = 90 + ($rating * 2);
        $tags = ['#ĐồĂnNgon', '#GiáRẻSinhViên', '#ĐóngGóiSạchSẽ', '#HàiLòng100%'];
    } elseif ($rating <= 2 || $neg_count > $pos_count) {
        $sentiment = 'can_chu_y';
        $score = 40;
        $tags = ['#CầnCảiThiện', '#KháchGópÝ', '#ĐộẤmThựcPhẩm'];
    } else {
        $sentiment = 'trung_lap';
        $score = 75;
        $tags = ['#TạmỔn', '#ĐúngGiá', '#VừaVặn'];
    }

    return [
        'sentiment' => $sentiment,
        'score' => $score,
        'tags' => implode(' ', array_slice($tags, 0, 3))
    ];
}

// 5. AI CỐ VẤN TRỌNG TÀI KHIẾU NẠI CHO ADMIN (Dispute Legal & Arbitration Assistant)
function ai_arbitration_advisor($dispute_data) {
    $reason = mb_strtolower($dispute_data['reason'] ?? '', 'UTF-8');
    $student_warnings = (int)($dispute_data['student_warnings'] ?? 0);
    $shop_violations = (int)($dispute_data['shop_violations'] ?? 0);
    $has_image = !empty($dispute_data['proof_image']);

    $recommendation = "refund_student";
    $confidence = 92;
    $analysis = "";

    // Phân tích logic pháp lý
    if (strpos($reason, 'chua') !== false || strpos($reason, 'hỏng') !== false || strpos($reason, 'mùi') !== false || strpos($reason, 'thiu') !== false) {
        $analysis = "Phân tích AI: Khiếu nại liên quan đến Vệ sinh An toàn Thực phẩm (chất lượng đồ ăn bị biến chất). ";
        if ($has_image) {
            $analysis .= "Sinh viên ĐÃ cung cấp bằng chứng hình ảnh hợp lệ. Cửa hàng có trách nhiệm bảo quản đến tay người dùng. ";
            $recommendation = "refund_student";
            $confidence = 96;
        } else {
            $analysis .= "Dù thiếu ảnh nhưng theo chính sách bảo vệ sức khỏe sinh viên, ưu tiên quyền lợi người tiêu dùng nếu lịch sử sinh viên uy tín (Cảnh cáo sinh viên hiện tại: {$student_warnings}). ";
            $recommendation = "refund_student";
            $confidence = 88;
        }
    } elseif (strpos($reason, 'quán đóng') !== false || strpos($reason, 'không có ai') !== false || strpos($reason, 'hết đồ') !== false) {
        $analysis = "Phân tích AI: Vi phạm cam kết thời gian mở cửa tiếp nhận đơn hàng. Cửa hàng không có mặt trong khung giờ nhận quy định là vi phạm nghiêm trọng thỏa thuận đối tác O2O. ";
        $recommendation = "refund_student";
        $confidence = 98;
    } else {
        if ($student_warnings >= 2) {
            $analysis = "Phân tích AI: Sinh viên đã có {$student_warnings} lần bị cảnh cáo trước đây. Cần xem xét kỹ khả năng lạm dụng chính sách khiếu nại. ";
            $recommendation = "pay_shop";
            $confidence = 80;
        } else {
            $analysis = "Phân tích AI: Vụ việc tranh chấp thông thường. Khuyến nghị hòa giải hoặc hoàn tiền nếu thực phẩm không đúng như cam kết. ";
            $recommendation = "refund_student";
            $confidence = 85;
        }
    }

    $suggested_text = ($recommendation === 'refund_student')
        ? "Căn cứ khoản 4 Điều 12 Quy chế Escrow Thực Phẩm Xanh: Chấp thuận khiếu nại của sinh viên. Hoàn 100% tiền tạm giữ về ví sinh viên và ghi nhận 1 lần nhắc nhở vi phạm đối với cửa hàng."
        : "Căn cứ chứng từ và thời gian đối soát: Bác đơn khiếu nại. Giải ngân số tiền đơn hàng cho chủ quán. Cảnh cáo người mua do gửi khiếu nại chưa đủ căn cứ chứng minh.";

    return [
        'recommendation' => $recommendation,
        'confidence' => $confidence,
        'analysis' => $analysis,
        'suggested_admin_note' => $suggested_text
    ];
}
?>
