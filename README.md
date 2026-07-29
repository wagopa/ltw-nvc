# 🚚 Nhà Vận Chuyển

Website quản lý vận chuyển hàng hóa (logistics), xây dựng bằng **PHP thuần** (không framework),
**MySQL** (qua XAMPP), giao diện **HTML/CSS/Bootstrap 5**, tương tác động bằng **AJAX**.

Dự án thực hành, tập trung vào nghiệp vụ của một nhà vận chuyển: vận đơn, phương tiện,
kho trung chuyển, tuyến đường — không phải sàn thương mại điện tử.

---

## 1. Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Backend | PHP thuần (PDO, không framework) |
| Database | MySQL / MariaDB (qua XAMPP) |
| Frontend | HTML5, CSS3, Bootstrap 5.3.3, Bootstrap Icons |
| Biểu đồ | Chart.js (chỉ dùng ở Dashboard) |
| Tương tác động | AJAX (Fetch API thuần, không dùng jQuery) |
| Font chữ | Oswald (tiêu đề), Be Vietnam Pro (nội dung), Roboto Mono (mã vận đơn) |
| Nhúng ngoài | Google Maps, Facebook Page Plugin, Zalo |

Tất cả thư viện ngoài (Bootstrap, Bootstrap Icons, Chart.js, Google Fonts) được tải qua **CDN**
— máy chạy dự án cần có kết nối Internet để giao diện hiển thị đầy đủ.

---

## 2. Cấu trúc thư mục

```
nha-van-chuyen/
├── config/
│   └── database.php          # Kết nối MySQL (PDO)
├── database/
│   └── nha_van_chuyen.sql    # Schema + dữ liệu mẫu — import file này để khởi tạo DB
├── includes/
│   ├── header.php             # Header công khai (navbar, dùng $urlGoc để tự nhận đường dẫn)
│   ├── footer.php             # Footer công khai + widget Zalo/gọi điện + main.js
│   ├── admin_header.php       # Layout Admin: sidebar + topbar
│   ├── admin_footer.php       # Đóng layout Admin
│   ├── auth_check.php         # Chặn truy cập nếu chưa đăng nhập
│   └── helpers.php            # Hàm tiện ích dùng chung (vd: lấy ký tự đầu tên an toàn UTF-8)
├── assets/
│   ├── css/style.css          # Toàn bộ style + hiệu ứng của dự án
│   ├── js/main.js             # Hiệu ứng dùng chung: scroll reveal, đếm số, navbar cuộn...
│   └── img/                   # (trống, dự phòng cho ảnh tĩnh nếu cần)
├── ajax/                       # Toàn bộ endpoint AJAX (chi tiết ở mục 6)
├── pages/                      # Các trang khách hàng + admin (sau khi đăng nhập)
├── index.php                   # Trang chủ công khai (Hero, Dịch vụ, Liên hệ...)
├── login.php                   # Đăng nhập (dùng chung cho khách hàng & admin)
├── logout.php                  # Đăng xuất
└── tao_mat_khau.php            # Tiện ích tạo hash mật khẩu bcrypt (xem mục 8)
```

---

## 3. Yêu cầu hệ thống

- **XAMPP** (Apache + MySQL/MariaDB + PHP ≥ 7.4, khuyến nghị PHP 8.x)
- Extension PHP: `pdo_mysql` (mặc định có sẵn trong XAMPP). Extension `mbstring` nên bật
  (mặc định XAMPP đã bật) — nếu thiếu, hệ thống vẫn chạy được nhờ cơ chế dự phòng trong
  `includes/helpers.php`, nhưng nên bật để hiển thị tiếng Việt chuẩn nhất.
- Trình duyệt có kết nối Internet (để tải Bootstrap/Font/Chart.js từ CDN và hiển thị Google Map/Facebook).

---

## 4. Hướng dẫn cài đặt

1. Copy toàn bộ thư mục `nha-van-chuyen` vào:
   - Windows: `C:\xampp\htdocs\`
   - macOS: `/Applications/XAMPP/htdocs/`
2. Mở **XAMPP Control Panel**, khởi động **Apache** và **MySQL**.
3. Vào `http://localhost/phpmyadmin`.
4. Chọn tab **Import**, chọn file `database/nha_van_chuyen.sql`, bấm **Go**.
   - File này tự chứa `CREATE DATABASE`, toàn bộ bảng, và dữ liệu mẫu.
   - File đã có sẵn `SET NAMES utf8mb4;` ở đầu để đảm bảo tiếng Việt import đúng
     dù bạn import qua phpMyAdmin hay dòng lệnh `mysql`.
5. Truy cập `http://localhost/nha-van-chuyen/login.php`.
6. Đăng nhập bằng tài khoản mẫu ở mục 5.

**Nếu đổi tên thư mục dự án:** dự án dùng đường dẫn tương đối (`../`, biến `$urlGoc`) nên
không cần sửa gì thêm, chỉ cần đường dẫn URL tương ứng với tên thư mục bạn đặt trong `htdocs`.

---

## 5. Tài khoản mẫu

| Vai trò | Tài khoản | Mật khẩu |
|---|---|---|
| Quản trị viên (admin) | `admin` | `123456` |
| Khách hàng | `khachhang01` | `123456` |

> ⚠️ Đây là dữ liệu demo — **đổi mật khẩu trước khi triển khai thật**.
> Dùng `tao_mat_khau.php` (mục 8) để tạo hash mật khẩu mới, rồi cập nhật trực tiếp
> trong bảng `nguoi_dung` qua phpMyAdmin. **Nhớ xóa file `tao_mat_khau.php` sau khi dùng xong.**

---

## 6. Danh sách chức năng

### 6.1. Khu vực công khai (chưa đăng nhập)
- Trang chủ: Hero, số liệu nổi (đếm chạy), Dịch vụ, Vì sao chọn chúng tôi, Liên hệ
- Form liên hệ (hiện tại chỉ demo giao diện — xem mục 9 "Việc còn dang dở")
- Nhúng Google Map (vị trí văn phòng) và Facebook Fanpage
- Widget nổi góc màn hình: Zalo chat + Gọi điện nhanh
- Đăng nhập dùng chung 1 form, tự động điều hướng theo vai trò sau khi đăng nhập

### 6.2. Khu vực Khách hàng (`pages/thong_tin_giao_hang.php`)
- Xem danh sách vận đơn của mình, có **phân trang** (5 đơn/trang)
- Thống kê nhanh: tổng đơn / chờ xử lý / đang vận chuyển / đã giao
- Xem lịch sử trạng thái từng vận đơn dạng dòng thời gian (timeline)

### 6.3. Khu vực Admin
| Trang | File | Chức năng |
|---|---|---|
| Dashboard | `pages/dashboard.php` | Thống kê tổng quan, biểu đồ 7 ngày, biểu đồ trạng thái, vận đơn mới nhất, top khách hàng, số liệu phương tiện/kho |
| Quản lý vận đơn | `pages/van_don.php` | CRUD, đổi trạng thái nhanh (tự ghi lịch sử), tìm kiếm/lọc |
| Quản lý khách hàng | `pages/khach_hang.php` | CRUD tài khoản khách hàng |
| Quản lý phương tiện | `pages/phuong_tien.php` | CRUD, gán vào kho, kiểm tra trùng biển số |
| Kho trung chuyển | `pages/kho.php` | CRUD |
| Tuyến đường | `pages/tuyen_duong.php` | CRUD, kiểm tra trùng tuyến & kho đi = kho đến |

### 6.4. Chức năng không phải CRUD thuần túy
- Xác thực & phân quyền theo vai trò (session, điều hướng khác nhau, chặn truy cập chéo)
- Đổi trạng thái vận đơn → tự động ghi thêm dòng lịch sử (nghiệp vụ, không phải Update đơn thuần)
- Dashboard: tổng hợp/đếm/nhóm dữ liệu, vẽ biểu đồ
- Tìm kiếm, lọc, phân trang
- Kiểm tra ràng buộc nghiệp vụ (trùng tài khoản/biển số/tuyến đường, kho đi ≠ kho đến)
- Sinh mã vận đơn tự động, băm mật khẩu (bcrypt)
- Hiệu ứng giao diện: scroll reveal, đếm số chạy, navbar cuộn thông minh (xem `assets/js/main.js`)

---

## 7. Cấu trúc Database

| Bảng | Mô tả |
|---|---|
| `nguoi_dung` | Tài khoản dùng chung, phân biệt qua cột `vai_tro` (`admin` / `khach_hang`) |
| `don_hang` | Vận đơn — gắn với khách hàng qua `nguoi_dung_id` |
| `lich_su_trang_thai` | Lịch sử trạng thái từng vận đơn (dùng cho timeline) |
| `phuong_tien` | Phương tiện vận chuyển, có thể gán vào 1 kho |
| `kho_trung_chuyen` | Kho / điểm trung chuyển theo khu vực (Bắc/Trung/Nam) |
| `tuyen_duong` | Tuyến đường cố định giữa 2 kho (khoảng cách, thời gian dự kiến) |

Xem chi tiết cột và ràng buộc khóa ngoại trong `database/nha_van_chuyen.sql`.

---

## 8. Các file/công cụ cần lưu ý

- **`tao_mat_khau.php`** — mở `tao_mat_khau.php?mk=matkhau_moi` để lấy hash bcrypt, dùng khi
  cần đổi mật khẩu thủ công qua phpMyAdmin. **Xóa file này khỏi server thật sau khi dùng xong.**
- **`includes/header.php` / `includes/footer.php`** — dùng biến `$urlGoc` để tự nhận biết đang
  được gọi từ thư mục gốc (`''`) hay từ trong `pages/` (`'../'`). Khi tạo trang mới, nhớ khai báo
  `$urlGoc` đúng trước khi include.
- **`index.php`** — chứa 2 biến cấu hình gần đầu file cần chỉnh khi triển khai thật:
  ```php
  $diaChiCongTyMap = 'Số 1 Đường Vận Chuyển, Quận Tân Bình, TP.HCM'; // địa chỉ thật cho Google Map
  $urlFacebookFanpage = 'https://www.facebook.com/META'; // link fanpage thật
  ```
- **`includes/footer.php`** — biến `$zaloSdtDemo` (số điện thoại demo `0900000000`) cần thay
  bằng số Zalo thật để nút chat hoạt động đúng.
- **PDO & Prepared Statements** — dự án tắt `ATTR_EMULATE_PREPARES` (dùng prepared statement
  thật của MySQL). Lưu ý: **không được dùng trùng tên tham số có `:ten` trong cùng 1 câu SQL**
  (vd: `WHERE a LIKE :tk OR b LIKE :tk` sẽ lỗi `SQLSTATE[HY093]`) — phải đặt tên riêng cho mỗi
  vị trí (`:tk1`, `:tk2`...) dù cùng giá trị.

---

## 9. Việc còn dang dở / có thể phát triển thêm

- Form liên hệ ở trang chủ mới chỉ là **demo giao diện** (hiện thông báo cảm ơn bằng JS), chưa
  nối với backend gửi email thật.
- Chưa có: in phiếu vận đơn PDF/QR, bảng giá vận chuyển công khai, lịch bảo trì phương tiện,
  bản đồ hệ thống kho toàn quốc, xuất báo cáo CSV, đổi mật khẩu tự phục vụ cho khách hàng.
- Sidebar Admin hiện có đủ 6 mục hoạt động (Vận đơn, Phương tiện, Kho, Tuyến đường, Khách hàng,
  Dashboard) — có thể mở rộng thêm nếu cần.

---

## 10. Khắc phục sự cố thường gặp

| Triệu chứng | Nguyên nhân thường gặp | Cách khắc phục |
|---|---|---|
| Lỗi kết nối database | Chưa khởi động MySQL trong XAMPP, hoặc chưa import DB | Kiểm tra XAMPP Control Panel, import lại `database/nha_van_chuyen.sql` |
| Tiếng Việt hiển thị lỗi font (mojibake) | Import DB không đúng charset | Đã cố định bằng `SET NAMES utf8mb4;` đầu file SQL — import lại file mới nhất |
| Đăng nhập báo sai mật khẩu dù đúng | Hash mật khẩu mẫu bị hỏng khi copy thủ công | Dùng `tao_mat_khau.php` tạo hash mới và cập nhật lại |
| Trang trắng / lỗi 500 | Thiếu extension PHP hoặc sai đường dẫn `htdocs` | Kiểm tra tên thư mục trùng với URL, bật `display_errors` trong `php.ini` để xem chi tiết lỗi |
| Giao diện xấu, thiếu icon/font | Máy không có Internet (CDN không tải được) | Bootstrap/Font/Icon đều tải qua CDN — cần kết nối mạng khi chạy |

---

*README này được cập nhật theo trạng thái dự án tại thời điểm tạo. Khi phát triển thêm chức năng mới, nên bổ sung vào mục 6 và 9 để tài liệu luôn phản ánh đúng dự án.*
