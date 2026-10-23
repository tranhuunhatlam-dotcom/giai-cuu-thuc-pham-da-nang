# HỆ THỐNG "GIẢI CỨU THỰC PHẨM CUỐI NGÀY" (MÔ HÌNH TÚI BẤT NGỜ - ĐÀ NẴNG)

Dự án mô phỏng website thương mại điện tử O2O kết nối các tiệm bánh, quán ăn chất lượng tại TP. Đà Nẵng với học sinh, sinh viên nhằm giải cứu thực phẩm tươi sạch cuối ngày, chống lãng phí và bảo vệ môi trường.

---

## 1. CÔNG NGHỆ & ĐẶC TẢ LẬP TRÌNH (CHUẨN SINH VIÊN NĂM 2)
- **Ngôn ngữ:** PHP 8 cơ bản (hàm thuần, không dùng OOP, class, namespace, Composer hay Framework).
- **Cơ sở dữ liệu:** MySQL / MariaDB (InnoDB 100%, hỗ trợ Giao dịch ACID Transaction: `START TRANSACTION`, `COMMIT`, `ROLLBACK`).
- **Thư viện kết nối:** `mysqli` theo hướng thủ tục (`mysqli_connect`, `mysqli_prepare`, `mysqli_stmt_bind_param`, `mysqli_stmt_execute`, `mysqli_stmt_get_result`).
- **Giao diện:** HTML5 ngữ nghĩa và CSS thuần (`css/style.css`), phối màu Xanh lá (`#2e7d32`) + Cam ấm (`#e65100`), tương thích hoàn toàn trên điện thoại và máy tính.
- **Tiêu chuẩn:** Không sử dụng JavaScript, AJAX, JSON API, QR Code hay thư viện ngoài. Mọi cập nhật thời gian, đếm ngược được tính toán bằng PHP trên máy chủ và hiển thị lúc tải trang (nhấn **F5** để làm mới).

---

## 2. CÁCH CÀI ĐẶT & CHẠY DỰ ÁN TRÊN XAMPP (WINDOWS)

### Bước 1: Đặt thư mục dự án vào XAMPP
Dự án được cấu hình chạy tại URL: `http://localhost/giaicuu/`

Nếu thư mục dự án nằm ở ngoài `htdocs`, bạn chỉ cần tạo một liên kết (Junction) hoặc copy toàn bộ thư mục vào `D:\xampp\htdocs\giaicuu` (hoặc `C:\xampp\htdocs\giaicuu` tùy ổ cài XAMPP):
```cmd
mklink /J D:\xampp\htdocs\giaicuu "d:\bai tap\bai tpa\ln1"
```

### Bước 2: Khởi động Apache và MySQL
- Mở **XAMPP Control Panel** trên Windows.
- Bấm nút **Start** ở cả 2 dịch vụ: **Apache** và **MySQL**.

### Bước 3: Import Cơ sở dữ liệu (sql/database.sql)
Có 2 cách đơn giản:
- **Cách 1 (Qua phpMyAdmin):**
  1. Mở trình duyệt vào `http://localhost/phpmyadmin/`.
  2. Bấm vào tab **Import** (Nhập).
  3. Chọn file `sql/database.sql` trong thư mục dự án và bấm **Import** (Thực hiện).
  4. Hệ thống sẽ tự tạo database `giaicuu` với 8 bảng InnoDB và dữ liệu cài đặt mặc định.
- **Cách 2 (Qua dòng lệnh Command Prompt / PowerShell):**
  ```cmd
  D:\xampp\mysql\bin\mysql.exe -u root < "d:\bai tap\bai tpa\ln1\sql\database.sql"
  ```

### Bước 4: Kiểm tra kết nối & Khởi tạo dữ liệu mẫu
- Truy cập vào link kiểm tra kết nối: `http://localhost/giaicuu/test_ket_noi.php`
- Bấm vào **"Tạo Dữ Liệu Mẫu Demo"** hoặc truy cập: `http://localhost/giaicuu/tao_du_lieu_mau.php` và bấm **"Tự Động Khởi Tạo Dữ Liệu Mẫu Ngay"** để nạp sẵn tài khoản, túi hàng và đơn mẫu tại Đà Nẵng.

---

## 3. DANH SÁCH TÀI KHOẢN DEMO SẴN SÀNG ĐỂ CHẤM BÀI

| Vai trò | Tên tài khoản | Email đăng nhập | Mật khẩu | Số dư ví | Ghi chú kịch bản kiểm thử |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Admin** | Quản Trị Viên Đà Nẵng | `admin@giaicuu.vn` | `admin123` | 0 đ | Trọng tài phân xử khiếu nại, duyệt quán, kiểm quỹ, chỉnh settings |
| **Sinh viên** | Nguyễn Văn An (BK Đà Nẵng) | `sv1@giaicuu.vn` | `sv123` | 195.000 đ | Có sẵn **Đơn #1** (Mã: `DEMO01`) ở trạng thái Chờ nhận hàng |
| **Sinh viên** | Trần Thị Mai (Kinh Tế ĐN) | `sv2@giaicuu.vn` | `sv123` | 260.000 đ | Có sẵn **Đơn #2** (Đã nhận hàng) để test nút **"Tôi hài lòng"** hoặc **"Báo cáo gian lận"** |
| **Sinh viên** | Lê Hoàng Phúc (Sư Phạm ĐN) | `sv3@giaicuu.vn` | `sv123` | 100.000 đ | Có sẵn **Đơn #3** (Đang tranh chấp) để test Admin phân xử |
| **Chủ quán** | Tiệm Bánh BonBon (Hải Châu) | `tiembanh@giaicuu.vn` | `shop123` | 0 đ | Quán đã duyệt, có Túi 1 và Đơn #1 để test nhập mã nhận hàng |
| **Chủ quán** | Cơm Tấm Sườn Cây (Thanh Khê) | `comtam@giaicuu.vn` | `shop123` | 0 đ | Quán đã duyệt, có Đơn #2 khách đang giữ tiền |
| **Chủ quán** | Trà Sữa Matcha Mori (Sơn Trà) | `trasua@giaicuu.vn` | `shop123` | 0 đ | Quán đã duyệt, có Đơn #3 đang bị khiếu nại |
| **Chủ quán** | Ẩm Thực Chay An Lạc (Ngũ Hành Sơn) | `dochay@giaicuu.vn` | `shop123` | 0 đ | **Chờ duyệt**: Dùng để test tính năng Admin Phê Duyệt Quán |

---

## 4. CÁCH CHỈNH THỜI GIAN GIỮ TIỀN / HẠN HỦY XUỐNG 1 - 2 PHÚT ĐỂ DEMO NHANH

Trong thực tế, thời gian giữ tiền là `hold_minutes = 45 phút` và hạn hủy đơn là `cancel_before_close_minutes = 90 phút`.
Để giáo viên và người chấm bài kiểm thử ngay các luồng tự động mà **không cần đợi hàng giờ**, hệ thống hỗ trợ 2 cách rút ngắn thời gian:

- **Cách 1 (Khuyên dùng - Bằng 1 nút bấm trên giao diện Web):**
  1. Đăng nhập tài khoản Admin: `admin@giaicuu.vn` / `admin123`.
  2. Bấm vào menu **Cài đặt** (`http://localhost/giaicuu/admin/cai_dat.php`).
  3. Bấm vào nút màu cam: **"Bật Demo Nhanh (1 - 2 Phút)"**.
  4. Hệ thống sẽ tự động cập nhật:
     - `hold_minutes` = `1` (Giữ tiền 1 phút sau khi nhận túi).
     - `cancel_before_close_minutes` = `2` (Chỉ cần hủy trước giờ đóng 2 phút).

- **Cách 2 (Chỉnh thủ công trong CSDL qua SQL):**
  ```sql
  UPDATE settings SET value = '1' WHERE name = 'hold_minutes';
  UPDATE settings SET value = '2' WHERE name = 'cancel_before_close_minutes';
  ```

---

## 5. KỊCH BẢN & DANH SÁCH KIỂM TRA BẰNG TAY (MANUAL TESTING CHECKLIST)

### Kịch bản 1: Mua túi trước giờ mở bán
- **Thực hiện:** Đăng nhập `sv1@giaicuu.vn`, vào trang chủ, chọn túi *"Túi Bánh Mì Men Tự Nhiên & Tart Phô Mai"* (Túi #2 - mở bán sau 2 tiếng).
- **Kỳ vọng:** Trang chi tiết thông báo hộp màu vàng *"Chưa tới giờ mở bán. Túi sẽ mở bán sau X phút"*, không hiển thị nút đặt mua. Nếu cố tình gửi form, PHP chặn lại và báo lỗi.

### Kịch bản 2: Không đủ tiền trong ví
- **Thực hiện:** Đăng ký 1 tài khoản sinh viên mới (số dư ban đầu 0 đ), vào xem túi giá 55.000 đ.
- **Kỳ vọng:** Nút đặt mua bị vô hiệu hóa kèm thông báo *"Số dư ví hiện tại không đủ (Không đủ tiền) - Nạp thêm tiền demo"*.

### Kịch bản 3: Đặt túi cuối cùng (Trừ kho nguyên tử)
- **Thực hiện:** Vào sửa túi còn `stock = 1`, dùng tài khoản sinh viên bấm Đặt mua.
- **Kỳ vọng:** Đơn tạo thành công, kho giảm về `0`, số dư sinh viên bị trừ đúng số tiền, bảng `wallet_logs` ghi nhận 1 bản ghi số âm `-55.000 đ`. Túi chuyển sang nhãn *"Hết túi"*, người thứ 2 bấm mua sẽ bị hệ thống rollback và báo lỗi *"Túi này vừa hết hàng!"*.

### Kịch bản 4: Hủy đơn sớm vs Hủy đơn muộn
- **Hủy sớm (còn cách giờ đóng cửa > 90 phút):** Vào chi tiết đơn trạng thái `cho_nhan`, bấm nút màu đỏ *"HỦY ĐƠN & HOÀN TIỀN 100%"*. Đơn chuyển sang `huy_boi_khach`, ví sinh viên được hoàn lại 100% tiền (ghi nhật ký ví), kho hàng của túi tự động cộng lại `+1`.
- **Hủy muộn (cách giờ đóng cửa < 90 phút):** Nút hủy bị ẩn, hệ thống hiển thị cảnh báo vàng *"Đã quá thời hạn hủy đơn (ít hơn 90 phút)..."*. Nếu cố tình gọi URL `student/huy_don.php`, hệ thống chặn lại và báo lỗi.

### Kịch bản 5: Nhập mã nhận hàng tại quán & Các trường hợp chặn lỗi
- **Nhập đúng mã:** Đăng nhập `tiembanh@giaicuu.vn`, vào menu *"Nhập Mã Nhận Hàng"*, nhập mã `DEMO01`. Đơn chuyển thành công sang `da_nhan`, lưu `picked_up_at` là thời điểm hiện tại và `hold_until` cộng thêm 45 phút. Tiền chưa chuyển vào ví quán.
- **Nhập mã lần 2:** Nhập lại mã `DEMO01`. Hệ thống báo lỗi rõ ràng: *"Mã nhận hàng này đã được xác nhận nhận túi trước đó vào lúc... Không thể nhận lần 2."*
- **Nhập mã quán khác:** Quán BonBon Bakery nhập mã `DEMO02` (thuộc Cơm Tấm Sườn Cây). Hệ thống báo lỗi: *"Mã nhận hàng này thuộc về một cửa hàng khác (Cơm Tấm Sườn Cây), không phải của quán bạn!"*

### Kịch bản 6: Khách hàng vắng mặt (Hết giờ đóng cửa)
- **Cơ chế tự động:** Hàm `check_orders($conn)` chạy ở đầu mỗi trang kiểm tra các đơn `cho_nhan` mà `close_at <= NOW()`.
- **Kỳ vọng:** Đơn tự động chuyển sang `huy_vang_mat`, tiền được cộng vào ví của chủ quán để bồi thường chi phí chuẩn bị đồ ăn, **KHÔNG cộng lại kho** (thực phẩm cuối ngày hết hạn sử dụng). Cả sinh viên và quán đều nhận được thông báo chuông.

### Kịch bản 7: Nút "Tôi hài lòng" để nhả tiền sớm
- **Thực hiện:** Đăng nhập `sv2@giaicuu.vn`, vào xem Đơn #2 (trạng thái `da_nhan`), bấm nút xanh *"TÔI HÀI LÒNG (NHẢ TIỀN CHO QUÁN NGAY)"*.
- **Kỳ vọng:** Đơn chuyển ngay sang `hoan_thanh`, tiền 40.000 đ lập tức cộng vào số dư ví của quán Cơm Tấm, ghi `wallet_logs` cho quán.

### Kịch bản 8: Báo cáo gian lận (Tranh chấp) trong và sau thời gian giữ tiền
- **Báo cáo trong thời gian giữ tiền (hold_until > NOW()):** Sinh viên chọn lý do, tải lên 1 ảnh chụp bánh hỏng/mốc. Đơn chuyển sang `tranh_chap`, tiền bị đóng băng. Quán nhận thông báo có khiếu nại.
- **Báo cáo sau thời gian giữ tiền:** Nếu đã hết thời gian giữ tiền, đơn đã tự động thành `hoan_thanh` và tiền đã về ví quán, hệ thống chặn không cho sinh viên gửi khiếu nại.
- **Giới hạn số lần:** Nếu sinh viên đã khiếu nại đủ 3 lần trong tháng hiện tại (`max_disputes_per_month`), hệ thống khóa form và thông báo đã đạt giới hạn.

### Kịch bản 9: Quán phản đối khiếu nại trong 48 giờ
- **Thực hiện:** Đăng nhập `trasua@giaicuu.vn`, vào Đơn quán, thấy Đơn #3 bị khiếu nại, bấm *"Phản đối khiếu nại"*.
- **Kỳ vọng:** Quán nhập lý do giải trình, tải ảnh đối chứng tại quầy. Hồ sơ chuyển sang trạng thái chờ Admin phân xử.

### Kịch bản 10: Admin phân xử tranh chấp (2 hướng phán quyết)
- Đăng nhập `admin@giaicuu.vn`, vào menu *"Xử lý Khiếu nại"* (`admin/tranh_chap.php`), chọn vụ tranh chấp #3:
  - **Hướng A - Chấp thuận khiếu nại:** Bắt buộc nhập lý do, bấm nút *(A)*. Đơn chuyển sang `hoan_tien_sv`, hoàn 100% tiền vào ví sinh viên, quán bị tăng thêm `+1` lần vi phạm chất lượng (`violation_count`).
  - **Hướng B - Bác bỏ khiếu nại:** Bắt buộc nhập lý do, bấm nút *(B)*. Đơn chuyển sang `chuyen_tien_quan`, chuyển tiền vào ví quán, sinh viên bị tính thêm `+1` lần cảnh cáo (`warning_count`). Nếu sinh viên đạt đủ 3 lần cảnh cáo, tài khoản tự động bị khóa (`is_locked = 1`).

### Kịch bản 11: Kiểm toán quỹ tiền toàn hệ thống
- Vào menu **Kiểm quỹ** (`http://localhost/giaicuu/admin/kiem_tra_tien.php`).
- Xem bảng tổng hợp: `Tổng số dư ví người dùng + Tổng tiền đang giữ trong các đơn = Tổng tiền nạp vào demo`.
- Hệ thống hiển thị thông báo màu xanh lá: **"QUỸ TIỀN TOÀN HỆ THỐNG CÂN BẰNG TUYỆT ĐỐI"**.

---

## 6. CẤU TRÚC THƯ MỤC CHUẨN CỦA DỰ ÁN

```text
giaicuu/
├── config.php                  # Kết nối CSDL mysqli thủ tục, session_start, timezone Asia/Ho_Chi_Minh
├── functions.php               # Các hàm dùng chung, check_orders() tự động hóa
├── header.php                  # Đầu trang, menu điều hướng động theo vai trò (student/shop/admin)
├── footer.php                  # Chân trang, lưu ý F5 cập nhật thời gian
├── index.php                   # Trang chủ: danh sách túi, lọc theo quận và loại quán (GET)
├── tui_chi_tiet.php            # Chi tiết túi bất ngờ, kiểm tra giờ mở bán, đặt hàng nguyên tử
├── dang_ky.php                 # Đăng ký tài khoản Sinh viên hoặc Chủ quán (kèm hồ sơ quán)
├── dang_nhap.php               # Đăng nhập, kiểm tra tài khoản bị khóa, phân luồng vai trò
├── dang_xuat.php               # Hủy session đăng xuất
├── thong_bao.php               # Hộp thư thông báo của người dùng, đánh dấu đã đọc
├── test_ket_noi.php            # Kiểm tra trạng thái MySQL và bảng cấu hình settings
├── tao_du_lieu_mau.php         # Khởi tạo dữ liệu mẫu phong phú và bảng tài khoản demo
├── README.md                   # Tài liệu hướng dẫn chi tiết và kịch bản kiểm thử
├── css/
│   └── style.css               # Giao diện Xanh lá + Cam ấm, responsive mobile
├── uploads/                    # Thư mục lưu trữ ảnh tải lên (JPG, PNG, WEBP < 2MB)
├── sql/
│   └── database.sql            # Mã nguồn tạo 8 bảng InnoDB và dữ liệu khởi tạo
├── student/
│   ├── vi_tien.php             # Số dư ví tiền ảo, nạp tiền demo, nhật ký biến động số dư
│   ├── don_hang.php            # Danh sách đơn hàng đã đặt
│   ├── don_chi_tiet.php        # Chi tiết đơn, mã nhận hàng 36px nổi bật, đếm ngược thời gian
│   ├── huy_don.php             # Xử lý hủy đơn trước giờ đóng cửa (hoàn 100% tiền + cộng kho)
│   ├── toi_hai_long.php        # Xác nhận hài lòng để nhả tiền sớm cho quán
│   └── bao_cao.php             # Báo cáo gian lận / tranh chấp (upload ảnh bằng chứng)
├── shop/
│   ├── ho_so.php               # Xem/sửa hồ sơ quán, số lần vi phạm, trạng thái duyệt
│   ├── danh_sach_tui.php       # Danh sách túi của quán, xem tồn kho và các mốc giờ
│   ├── them_tui.php            # Thêm túi mới, validate giảm giá 30-50% và ràng buộc thời gian
│   ├── sua_tui.php             # Sửa thông tin túi (khóa giá nếu đã có người đặt)
│   ├── xoa_tui.php             # Xóa túi nếu chưa có đơn hàng
│   ├── nhap_ma.php             # Nhập mã nhận hàng 6 ký tự, chuyển da_nhan và giữ tiền 45 phút
│   ├── don_hang.php            # Danh sách đơn hàng của quán
│   ├── phan_hoi.php            # Phản đối khiếu nại trong 48 giờ
│   └── doanh_thu.php           # Thống kê doanh thu: Đang tạm giữ / Đã nhận / Đang tranh chấp
└── admin/
    ├── thong_ke.php            # Thống kê SQL: túi giải cứu, tiền SV tiết kiệm, số tranh chấp
    ├── duyet_quan.php          # Xét duyệt quán đối tác mới
    ├── tranh_chap.php          # Trọng tài phân xử tranh chấp (hướng A và hướng B)
    ├── tai_khoan.php           # Quản lý tài khoản, khóa / mở khóa, theo dõi cảnh cáo
    ├── cai_dat.php             # Chỉnh sửa bảng settings, phím tắt bật Demo Nhanh
    └── kiem_tra_tien.php       # Kiểm toán cân bằng quỹ tiền toàn hệ thống
```
