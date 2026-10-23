<?php
// FILE: admin/tranh_chap.php
// Mục đích: Trọng tài phân xử tranh chấp khiếu nại giữa sinh viên và chủ quán
// Nghiệp vụ: 
//   (a) Hoàn 100% tiền cho sinh viên, quán bị ghi 1 vi phạm
//   (b) Bác khiếu nại, tiền về quán, sinh viên bị cảnh cáo (+1 warning_count, >= 3 thì tự động khóa)
//   Bắt buộc Admin ghi lý do (admin_note) và toàn bộ thay đổi nằm trong Transaction

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../services/ai_service.php';

require_role('admin');

$error = '';
$notice = '';

// Xử lý phân xử khi Admin submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_resolve'])) {
    $dispute_id = (int)($_POST['dispute_id'] ?? 0);
    $decision = $_POST['decision'] ?? ''; // 'refund_student' hoặc 'pay_shop'
    $admin_note = trim($_POST['admin_note'] ?? '');

    if (empty($admin_note)) {
        $error = 'Quản trị viên bắt buộc phải ghi rõ lý do và căn cứ phân xử vào ô ghi chú!';
    } elseif (!in_array($decision, ['refund_student', 'pay_shop'])) {
        $error = 'Quyết định phân xử không hợp lệ.';
    } else {
        // Lấy thông tin chi tiết của tranh chấp và đơn hàng
        $sql_d = "SELECT d.*, o.id AS order_id, o.price, o.user_id AS student_id, o.shop_id, o.status AS order_status,
                         s.owner_id, s.name AS shop_name, u.name AS student_name, u.warning_count AS student_warnings
                  FROM disputes d
                  JOIN orders o ON d.order_id = o.id
                  JOIN shops s ON o.shop_id = s.id
                  JOIN users u ON o.user_id = u.id
                  WHERE d.id = ? LIMIT 1";
        $stmt_d = mysqli_prepare($conn, $sql_d);
        mysqli_stmt_bind_param($stmt_d, "i", $dispute_id);
        mysqli_stmt_execute($stmt_d);
        $res_d = mysqli_stmt_get_result($stmt_d);
        $disp = mysqli_fetch_assoc($res_d);
        mysqli_stmt_close($stmt_d);

        if (!$disp) {
            $error = 'Không tìm thấy hồ sơ khiếu nại.';
        } elseif ($disp['order_status'] !== 'tranh_chap') {
            $error = 'Đơn hàng này không còn ở trạng thái tranh chấp (trạng thái hiện tại: ' . $disp['order_status'] . ')!';
        } else {
            // BẮT ĐẦU TRANSACTION PHÂN XỬ CHÍNH XÁC
            mysqli_begin_transaction($conn);

            if ($decision === 'refund_student') {
                // =========================================================================
                // HƯỚNG A: CHẤP THUẬN KHIẾU NẠI (Hoàn tiền cho SV, ghi 1 vi phạm cho quán)
                // =========================================================================
                // 1. Cập nhật trạng thái đơn sang 'hoan_tien_sv'
                $stmt_ord = mysqli_prepare($conn, "UPDATE orders SET status = 'hoan_tien_sv' WHERE id = ? AND status = 'tranh_chap'");
                mysqli_stmt_bind_param($stmt_ord, "i", $disp['order_id']);
                mysqli_stmt_execute($stmt_ord);
                $aff_ord = mysqli_stmt_affected_rows($stmt_ord);
                mysqli_stmt_close($stmt_ord);

                if ($aff_ord > 0) {
                    // 2. Cập nhật bảng disputes
                    $stmt_up_d = mysqli_prepare($conn, "UPDATE disputes SET status = 'hoan_tien_sv', admin_note = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_up_d, "si", $admin_note, $dispute_id);
                    mysqli_stmt_execute($stmt_up_d);
                    mysqli_stmt_close($stmt_up_d);

                    // 3. Hoàn 100% tiền vào ví sinh viên
                    $stmt_sv = mysqli_prepare($conn, "UPDATE users SET balance = balance + ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_sv, "di", $disp['price'], $disp['student_id']);
                    mysqli_stmt_execute($stmt_sv);
                    mysqli_stmt_close($stmt_sv);

                    // 4. Ghi nhật ký ví tiền của sinh viên
                    $reason = "Admin chấp thuận khiếu nại đơn #" . $disp['order_id'] . ", hoàn tiền 100%";
                    $stmt_log = mysqli_prepare($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                    mysqli_stmt_bind_param($stmt_log, "idsi", $disp['student_id'], $disp['price'], $reason, $disp['order_id']);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);

                    // 5. Ghi 1 vi phạm cho cửa hàng
                    $stmt_viol = mysqli_prepare($conn, "UPDATE shops SET violation_count = violation_count + 1 WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_viol, "i", $disp['shop_id']);
                    mysqli_stmt_execute($stmt_viol);
                    mysqli_stmt_close($stmt_viol);

                    mysqli_commit($conn);

                    // Gửi thông báo đến cả 2 bên
                    add_notification($conn, $disp['student_id'], "Admin đã chấp thuận khiếu nại của bạn cho đơn #" . $disp['order_id'] . ". Số tiền " . format_money($disp['price']) . " đã được hoàn lại vào ví. Lý do: {$admin_note}");
                    add_notification($conn, $disp['owner_id'], "Admin đã chấp thuận khiếu nại của sinh viên cho đơn #" . $disp['order_id'] . ". Tiền hoàn lại cho khách và quán bị ghi nhận 1 lần vi phạm chất lượng. Lý do: {$admin_note}");

                    $notice = "Đã phân xử thành công: Chấp thuận khiếu nại, hoàn tiền cho sinh viên và ghi 1 vi phạm cho quán {$disp['shop_name']}!";
                } else {
                    mysqli_rollback($conn);
                    $error = 'Phân xử thất bại do trạng thái đơn hàng đã bị thay đổi!';
                }

            } else {
                // =========================================================================
                // HƯỚNG B: BÁC KHIẾU NẠI (Chuyển tiền cho quán, cảnh cáo sinh viên)
                // =========================================================================
                // 1. Cập nhật trạng thái đơn sang 'chuyen_tien_quan'
                $stmt_ord = mysqli_prepare($conn, "UPDATE orders SET status = 'chuyen_tien_quan' WHERE id = ? AND status = 'tranh_chap'");
                mysqli_stmt_bind_param($stmt_ord, "i", $disp['order_id']);
                mysqli_stmt_execute($stmt_ord);
                $aff_ord = mysqli_stmt_affected_rows($stmt_ord);
                mysqli_stmt_close($stmt_ord);

                if ($aff_ord > 0) {
                    // 2. Cập nhật bảng disputes
                    $stmt_up_d = mysqli_prepare($conn, "UPDATE disputes SET status = 'chuyen_tien_quan', admin_note = ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_up_d, "si", $admin_note, $dispute_id);
                    mysqli_stmt_execute($stmt_up_d);
                    mysqli_stmt_close($stmt_up_d);

                    // 3. Chuyển tiền cho chủ quán
                    $stmt_shop = mysqli_prepare($conn, "UPDATE users SET balance = balance + ? WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_shop, "di", $disp['price'], $disp['owner_id']);
                    mysqli_stmt_execute($stmt_shop);
                    mysqli_stmt_close($stmt_shop);

                    // 4. Ghi nhật ký ví tiền của chủ quán
                    $reason = "Admin bác bỏ khiếu nại đơn #" . $disp['order_id'] . ", chuyển tiền bán túi cho quán";
                    $stmt_log = mysqli_prepare($conn, "INSERT INTO wallet_logs (user_id, amount, reason, order_id, created_at) VALUES (?, ?, ?, ?, NOW())");
                    mysqli_stmt_bind_param($stmt_log, "idsi", $disp['owner_id'], $disp['price'], $reason, $disp['order_id']);
                    mysqli_stmt_execute($stmt_log);
                    mysqli_stmt_close($stmt_log);

                    // 5. Tăng số lần cảnh cáo của sinh viên (+1 warning_count)
                    // Nếu cảnh cáo >= 3 -> Tự động khóa tài khoản sinh viên (is_locked = 1)
                    $new_warnings = $disp['student_warnings'] + 1;
                    $lock_sql = ($new_warnings >= 3) ? ", is_locked = 1" : "";
                    
                    $stmt_warn = mysqli_prepare($conn, "UPDATE users SET warning_count = warning_count + 1 $lock_sql WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_warn, "i", $disp['student_id']);
                    mysqli_stmt_execute($stmt_warn);
                    mysqli_stmt_close($stmt_warn);

                    mysqli_commit($conn);

                    // Gửi thông báo
                    $warn_msg = "Admin đã bác bỏ khiếu nại của bạn cho đơn #" . $disp['order_id'] . ". Tiền đã được chuyển cho quán. Bạn bị tính 1 lần cảnh cáo (Hiện tại: {$new_warnings}/3 cảnh cáo).";
                    if ($new_warnings >= 3) {
                        $warn_msg .= " TÀI KHOẢN CỦA BẠN ĐÃ BỊ KHÓA do tích lũy đủ 3 lần cảnh cáo!";
                    }
                    $warn_msg .= " Lý do phân xử: {$admin_note}";
                    add_notification($conn, $disp['student_id'], $warn_msg);

                    add_notification($conn, $disp['owner_id'], "Admin đã bác bỏ khiếu nại của khách cho đơn #" . $disp['order_id'] . ". Số tiền " . format_money($disp['price']) . " đã được cộng vào ví quán của bạn. Lý do: {$admin_note}");

                    $notice = "Đã phân xử thành công: Bác khiếu nại, tiền đã chuyển cho quán. Sinh viên {$disp['student_name']} bị cảnh cáo (tổng: {$new_warnings} lần)!";
                } else {
                    mysqli_rollback($conn);
                    $error = 'Phân xử thất bại do trạng thái đơn hàng đã bị thay đổi!';
                }
            }
        }
    }
}

// Lấy danh sách toàn bộ các tranh chấp
$sql_list = "SELECT d.*, o.id AS order_id, o.price, o.created_at AS order_created,
                    s.name AS shop_name, s.violation_count AS shop_violations,
                    u.name AS student_name, u.email AS student_email, u.warning_count AS student_warnings, u.is_locked AS student_locked
             FROM disputes d
             JOIN orders o ON d.order_id = o.id
             JOIN shops s ON o.shop_id = s.id
             JOIN users u ON o.user_id = u.id
             ORDER BY (d.status IN ('cho_shop_phan_hoi', 'cho_admin_xu_ly')) DESC, d.id DESC";
$res_list = mysqli_query($conn, $sql_list);
$disputes = [];
while ($row = mysqli_fetch_assoc($res_list)) {
    $disputes[] = $row;
}

$page_title = 'Trọng tài xử lý khiếu nại';
require_once __DIR__ . '/../header.php';
?>

<div class="card">
    <div class="card-title">
        <span style="color: var(--danger-color);">HỘI ĐỒNG TRỌNG TÀI - PHÂN XỬ TRANH CHẤP KHIẾU NẠI</span>
    </div>

    <div class="alert alert-info">
        <strong>Vai trò Admin:</strong> Bạn là trọng tài phân xử công minh dựa trên bằng chứng ảnh của sinh viên và giải trình của quán.
        Mọi quyết định hoàn tiền hoặc chuyển tiền đều tuân thủ chặt chẽ Transaction và lưu vết ví đầy đủ.
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (empty($disputes)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 30px 0;">Hiện không có vụ tranh chấp nào trong hệ thống.</p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <?php foreach ($disputes as $d): 
                $is_pending = in_array($d['status'], ['cho_shop_phan_hoi', 'cho_admin_xu_ly']);
            ?>
                <div style="border: 2px solid <?php echo $is_pending ? '#ef5350' : '#cfd8dc'; ?>; border-radius: 8px; padding: 18px; background: <?php echo $is_pending ? '#fff' : '#fafafa'; ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <span style="font-size: 16px; font-weight: 700; color: #b71c1c;">VỤ TRANH CHẤP #<?php echo $d['id']; ?> (Đơn #<?php echo $d['order_id']; ?>)</span>
                            <span style="margin-left: 10px; font-size: 14px; color: var(--secondary-color); font-weight: bold;">
                                Tiền tranh chấp: <?php echo format_money($d['price']); ?>
                            </span>
                        </div>
                        <div>
                            <?php if ($d['status'] === 'cho_shop_phan_hoi'): ?>
                                <span class="badge badge-warning">Chờ quán phản hồi (trong 48h)</span>
                            <?php elseif ($d['status'] === 'cho_admin_xu_ly'): ?>
                                <span class="badge badge-danger">Cần Admin phân xử</span>
                            <?php elseif ($d['status'] === 'hoan_tien_sv'): ?>
                                <span class="badge badge-purple">Admin chấp thuận (Hoàn tiền SV)</span>
                            <?php elseif ($d['status'] === 'chuyen_tien_quan'): ?>
                                <span class="badge badge-success">Admin bác khiếu nại (Chuyển tiền quán)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 2 Cột so sánh Bằng chứng của 2 bên -->
                    <div class="form-row" style="margin-bottom: 14px;">
                        <!-- Bên Khiếu nại: Sinh viên -->
                        <div class="form-col" style="background: #ffebee; padding: 14px; border-radius: 6px; font-size: 13px;">
                            <h4 style="color: #c62828; margin-bottom: 6px;">Bên khiếu nại: Sinh viên</h4>
                            <div>Họ tên: <strong><?php echo htmlspecialchars($d['student_name']); ?></strong> (<?php echo htmlspecialchars($d['student_email']); ?>)</div>
                            <div>Lịch sử cảnh cáo: <strong><?php echo $d['student_warnings']; ?> lần</strong> <?php echo ($d['student_locked']) ? '<span style="color:red;">(Đã bị khóa)</span>' : ''; ?></div>
                            <div style="margin-top: 6px;"><strong>Lý do khiếu nại:</strong> <span style="font-weight: bold; color: #d32f2f;"><?php echo htmlspecialchars($d['reason']); ?></span></div>
                            <div style="margin-top: 4px;"><strong>Ghi chú của SV:</strong> <?php echo nl2br(htmlspecialchars($d['student_note'] ?? '')); ?></div>
                            
                            <?php if (!empty($d['student_image'])): ?>
                                <div style="margin-top: 8px;">
                                    <strong>Ảnh bằng chứng SV gửi:</strong><br>
                                    <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($d['student_image']); ?>" target="_blank">
                                        <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($d['student_image']); ?>" alt="Ảnh chứng minh" style="max-width: 180px; max-height: 140px; border-radius: 4px; border: 1px solid #ccc; margin-top: 4px;">
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Bên Bị khiếu nại: Quán -->
                        <div class="form-col" style="background: #e8f5e9; padding: 14px; border-radius: 6px; font-size: 13px;">
                            <h4 style="color: #2e7d32; margin-bottom: 6px;">Bên bị khiếu nại: Quán</h4>
                            <div>Tên quán: <strong><?php echo htmlspecialchars($d['shop_name']); ?></strong></div>
                            <div>Lịch sử vi phạm của quán: <strong><?php echo $d['shop_violations']; ?> lần</strong></div>
                            
                            <div style="margin-top: 6px;">
                                <strong>Phản hồi / Đối chứng từ quán:</strong>
                                <?php if (!empty($d['shop_note'])): ?>
                                    <div style="color: #1b5e20; margin-top: 4px;"><?php echo nl2br(htmlspecialchars($d['shop_note'])); ?></div>
                                    <?php if (!empty($d['shop_image'])): ?>
                                        <div style="margin-top: 8px;">
                                            <strong>Ảnh đối chứng của quán:</strong><br>
                                            <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($d['shop_image']); ?>" target="_blank">
                                                <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($d['shop_image']); ?>" alt="Ảnh đối chứng quán" style="max-width: 180px; max-height: 140px; border-radius: 4px; border: 1px solid #ccc; margin-top: 4px;">
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="color: #757575; font-style: italic; margin-top: 4px;">Quán chưa gửi phản hồi giải trình.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Nếu đã phân xử rồi thì hiện kết quả -->
                    <?php if (!$is_pending): ?>
                        <div style="background: #fff3e0; border-left: 4px solid #ff9800; padding: 10px 14px; font-size: 13px;">
                            <strong>Kết luận phân xử của Admin:</strong><br>
                            <?php echo nl2br(htmlspecialchars($d['admin_note'] ?? '')); ?>
                        </div>
                    <?php else: 
                        $ai_adv = ai_arbitration_advisor([
                            'reason' => $d['reason'],
                            'student_warnings' => $d['student_warnings'],
                            'shop_violations' => $d['shop_violations'] ?? 0,
                            'proof_image' => $d['student_image']
                        ]);
                    ?>
                        <!-- Khuyến Nghị Trọng Tài AI -->
                        <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 8px; padding: 14px; margin-bottom: 12px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <strong style="color: #166534; font-size: 13.5px;">🤖 Cố Vấn Trọng Tài AI (Tham chiếu Điều khoản O2O Escrow):</strong>
                                <span class="badge badge-success" style="font-size: 11px;">Độ tin cậy: <?php echo $ai_adv['confidence']; ?>%</span>
                            </div>
                            <p style="color: #14532d; line-height: 1.5; margin-bottom: 8px;">
                                <?php echo htmlspecialchars($ai_adv['analysis']); ?>
                            </p>
                            <div style="background: #ffffff; border: 1px dashed #4ade80; padding: 8px 12px; border-radius: 6px; color: #166534;">
                                <strong>💡 Khuyến nghị:</strong> <?php echo ($ai_adv['recommendation'] === 'refund_student') ? '<span style="color: #b91c1c; font-weight: bold;">Chấp thuận hoàn tiền cho Sinh viên</span>' : '<span style="color: #047857; font-weight: bold;">Bác đơn khiếu nại (Chuyển tiền quán)</span>'; ?>
                                <br><small style="color: #475569;">Gợi ý căn cứ: "<?php echo htmlspecialchars($ai_adv['suggested_admin_note']); ?>"</small>
                            </div>
                        </div>

                        <!-- Form Phân xử của Admin -->
                        <form action="<?php echo BASE_URL; ?>admin/tranh_chap.php" method="POST" style="background: #fffde7; border: 1px dashed #fbc02d; padding: 14px; border-radius: 6px;">
                            <input type="hidden" name="dispute_id" value="<?php echo $d['id']; ?>">
                            
                            <h4 style="color: #f57f17; margin-bottom: 8px;">Phân xử trọng tài cho vụ tranh chấp này:</h4>

                            <div class="form-group" style="margin-bottom: 12px;">
                                <label class="form-label">Lý do phân xử của Quản trị viên (Bắt buộc ghi rõ căn cứ):</label>
                                <textarea name="admin_note" class="form-control" rows="2" placeholder="Ví dụ: <?php echo htmlspecialchars($ai_adv['suggested_admin_note']); ?>" required><?php echo htmlspecialchars($ai_adv['suggested_admin_note']); ?></textarea>
                            </div>

                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <button type="submit" name="decision" value="refund_student" class="btn btn-danger" onclick="return confirm('XÁC NHẬN CHẤP THUẬN KHIẾU NẠI:\n- Hoàn 100% tiền cho sinh viên\n- Ghi 1 vi phạm cho quán\nBạn có chắc chắn?');">
                                    (A) Chấp thuận khiếu nại (Hoàn tiền SV, phạt quán)
                                </button>
                                <button type="submit" name="decision" value="pay_shop" class="btn btn-primary" onclick="return confirm('XÁC NHẬN BÁC BỎ KHIẾU NẠI:\n- Chuyển tiền cho quán\n- Cảnh cáo sinh viên (+1 warning, >=3 khóa tài khoản)\nBạn có chắc chắn?');">
                                    (B) Bác bỏ khiếu nại (Chuyển tiền quán, cảnh cáo SV)
                                </button>
                            </div>
                            <input type="hidden" name="action_resolve" value="1">
                        </form>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
