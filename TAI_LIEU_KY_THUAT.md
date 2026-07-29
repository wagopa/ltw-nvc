# 📘 TÀI LIỆU KỸ THUẬT — Dự án "Nhà Vận Chuyển"

Tài liệu này mô tả **logic bên trong mã nguồn**: từng file làm gì, từng hàm xử lý ra sao,
luồng dữ liệu đi qua các lớp như thế nào. Dùng để tham chiếu khi đọc lại code hoặc phát triển tiếp.

> Tài liệu song song `README.md` mô tả cách **cài đặt và sử dụng**. File này mô tả **cách vận hành bên trong**.

---

## 1. Kiến trúc tổng thể

```
Trình duyệt
   │
   ├─ Trang PHP thường (GET) ──► render HTML server-side (header/footer include lẫn nhau)
   │
   └─ Fetch API (AJAX) ──► ajax/*.php (JSON in/out) ──► config/database.php (PDO) ──► MySQL
```

**Nguyên tắc xuyên suốt:**
- Mỗi trang PHP trong `pages/` chỉ **render khung + gọi include**; toàn bộ dữ liệu động được
  nạp bằng JavaScript gọi tới file tương ứng trong `ajax/` sau khi trang đã load (`DOMContentLoaded`).
- Mỗi file trong `ajax/` là một **API JSON độc lập theo mẫu action-based**: nhận `action` qua
  GET hoặc POST, `switch` sang nhánh xử lý, luôn trả JSON dạng `{ thanh_cong: bool, ... }`.
- Không dùng framework, không autoload — mỗi file `require_once` trực tiếp file nó cần.
- Toàn bộ truy vấn dùng **PDO prepared statement** (`ATTR_EMULATE_PREPARES => false` — xem mục 3.1
  và lưu ý quan trọng về tham số trùng tên ở mục 9).

---

## 2. Luồng xác thực & phân quyền

### 2.1. Đăng nhập (`login.php`)
1. Nếu đã có session (`$_SESSION['nguoi_dung_id']`) → redirect luôn theo `vai_tro`
   (khách hàng → `index.php`, admin → `pages/dashboard.php`). Tránh hiện lại form khi đã đăng nhập.
2. Nếu có POST: lấy `username`/`mat_khau`, truy vấn bảng `nguoi_dung`, dùng
   `password_verify()` để so khớp hash bcrypt.
3. Thành công → ghi 3 giá trị vào session: `nguoi_dung_id`, `ho_ten`, `vai_tro` → redirect theo vai trò.
4. Thất bại → hiện lại form kèm `$loi`.

### 2.2. Kiểm tra đăng nhập cho trang cần bảo vệ (`includes/auth_check.php`)
- `require_once` file này ở đầu mọi trang trong `pages/` (trước khi có output HTML).
- Nếu chưa có `$_SESSION['nguoi_dung_id']` → `header('Location: ../login.php')` rồi `exit`.
- Hàm `nguoiDungHienTai()` — trả về mảng `['id', 'ho_ten', 'vai_tro']` lấy từ session, dùng để
  hiển thị tên/vai trò và để mỗi trang tự kiểm tra thêm `vai_tro` có đúng không (xem 2.3).

### 2.3. Phân quyền theo vai trò (chặn chéo)
- `auth_check.php` **chỉ đảm bảo đã đăng nhập**, không phân biệt vai trò.
- Mỗi trang admin (`pages/dashboard.php`, `van_don.php`, `khach_hang.php`, `phuong_tien.php`,
  `kho.php`, `tuyen_duong.php`) tự kiểm tra thêm:
  ```php
  if ($nguoiDung['vai_tro'] !== 'admin') { echo "..."; exit; }
  ```
- Tương tự `pages/thong_tin_giao_hang.php` kiểm tra `vai_tro !== 'khach_hang'`.
- Mỗi file AJAX **tự kiểm tra session + vai trò ở đầu file** (không phụ thuộc `auth_check.php`,
  vì AJAX gọi trực tiếp, không qua include) — xem mẫu chung ở mục 4.

### 2.4. Đăng xuất (`logout.php`)
- `session_unset()` + `session_destroy()` → redirect về `login.php`.

---

## 3. Lớp nền tảng (`config/`, `includes/`)

### 3.1. `config/database.php`
- Định nghĩa hằng số kết nối (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`).
- `ketNoiDatabase()` — dùng biến `static $pdo` để chỉ tạo **1 kết nối duy nhất** dùng lại trong
  suốt vòng đời request (singleton đơn giản, không cần class).
- Cấu hình PDO quan trọng:
  - `ATTR_ERRMODE => ERRMODE_EXCEPTION` — lỗi SQL sẽ ném exception thay vì âm thầm bỏ qua.
  - `ATTR_EMULATE_PREPARES => false` — dùng prepared statement **thật** của MySQL (an toàn hơn,
    nhưng có ràng buộc: không được lặp tên tham số `:x` trong 1 câu lệnh — xem mục 9).

### 3.2. `includes/helpers.php`
- `kyTuDauTien(string $chuoi): string` — lấy ký tự đầu tiên của tên để hiển thị lên avatar tròn.
  Ưu tiên dùng `mb_substr()` nếu có extension `mbstring`; nếu không có, tự tính số byte của ký tự
  UTF-8 đầu tiên bằng cách đọc bit đầu của byte đầu (tránh cắt vỡ ký tự tiếng Việt nếu thiếu
  mbstring). Được `header.php` và `admin_header.php` cùng dùng.

### 3.3. `includes/header.php` + `includes/footer.php` (layout công khai)
- Dùng chung cho `index.php` và `pages/thong_tin_giao_hang.php`.
- Biến đầu vào (set **trước khi** include):
  - `$trangHienTai` — key trang hiện tại, để tô sáng mục menu đang active.
  - `$urlGoc` — `''` nếu file gọi nằm ở gốc, `'../'` nếu nằm trong `pages/`. **Bắt buộc phải khai
    báo đúng**, nếu không toàn bộ link/CSS/JS trong header/footer sẽ sai đường dẫn.
  - `$nguoiDung` — mảng thông tin người dùng nếu đã đăng nhập (để hiện avatar/tên/dropdown
    đăng xuất); để trống hoặc không set nếu chưa đăng nhập.
- `footer.php` còn chứa:
  - Widget liên hệ nhanh (Zalo + gọi điện) — biến `$zaloSdtDemo` khai báo ngay trong file.
  - Nút cuộn lên đầu trang (`#nvcScrollTop`).
  - `<script src="...assets/js/main.js">` — nạp hiệu ứng dùng chung (mục 7).

### 3.4. `includes/admin_header.php` + `includes/admin_footer.php` (layout Admin)
- Layout riêng: sidebar bên trái (menu điều hướng) + topbar bên phải (tên admin + đăng xuất).
- Biến đầu vào: `$trangAdminHienTai` (tô sáng menu), `$tieuDeTrangAdmin` (tiêu đề topbar),
  `$nguoiDung` (bắt buộc, vì trang admin luôn đòi hỏi đã đăng nhập).
- Không dùng `$urlGoc` vì mọi trang admin đều nằm cùng cấp trong `pages/`, nên toàn bộ liên kết
  trong sidebar dùng đường dẫn tương đối cố định (`van_don.php`, `kho.php`...).
- `admin_footer.php` nhúng thêm `assets/js/admin.js` (sau Bootstrap, trước script riêng của từng
  trang) — file này chỉ chứa 1 hàm dùng chung: `renderPhanTrang(pt, hamTai)`, dựng điều khiển
  phân trang (nút số trang, tiến/lùi, tổng số bản ghi) cho **cả 5 trang quản lý** (mục 6).

---

## 4. Mẫu chung của mọi file AJAX (`ajax/*.php`)

Tất cả 7 file trong `ajax/` đều theo đúng 1 khuôn:

```php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

// Kiểm tra quyền — mỗi file tự kiểm tra vai_tro phù hợp với mục đích của nó
if (!isset($_SESSION['nguoi_dung_id']) || $_SESSION['vai_tro'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['thanh_cong' => false, 'thong_bao' => '...']);
    exit;
}

$pdo = ketNoiDatabase();
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'danh_sach': ... break;
        case 'them':      ... break;
        case 'sua':       ... break;
        case 'xoa':       ... break;
        default: http_response_code(400); echo json_encode([...]); 
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
}
```

Quy ước đặt tên response JSON:
- `thanh_cong` (bool) — luôn có, cờ báo thành công/thất bại.
- `thong_bao` (string) — thông báo lỗi hoặc thành công, hiển thị trực tiếp cho người dùng.
- `du_lieu` — mảng kết quả (dùng ở hầu hết action `danh_sach`).

Các hàm `layDuLieuXxx(array $post): array` (xuất hiện trong `van_don_ajax.php`, `khach_hang_ajax.php`,
`kho_ajax.php`, `phuong_tien_ajax.php`, `tuyen_duong_ajax.php`) đều theo 1 khuôn: đọc `$_POST`,
validate, trả về `['loi' => string|null, 'data' => array|null]` — nếu `loi` khác null thì dừng xử lý
và trả lỗi ngay, nếu không thì `data` được dùng trực tiếp trong câu `INSERT`/`UPDATE`.

### Chi tiết từng file AJAX

| File | Actions | Ghi chú logic riêng |
|---|---|---|
| `giao_hang_khach_ajax.php` | *(không có action, chỉ 1 luồng)* | Yêu cầu `vai_tro === 'khach_hang'`. Nhận `trang`/`so_dong` (phân trang), `trang_thai` (lọc, optional) và `q` (tìm theo mã vận đơn, optional) qua GET. Thống kê tổng quan (`thong_ke`) luôn tính trên **toàn bộ** đơn của khách hàng (không bị ảnh hưởng bởi bộ lọc); còn số liệu phân trang (`phan_trang.tong_so_don`) phản ánh **số đơn khớp bộ lọc hiện tại**. `trang_thai` được validate theo whitelist ENUM trước khi đưa vào câu SQL. |
| `van_don_ajax.php` | `danh_sach`, `khach_hang`, `them`, `sua`, `xoa`, `doi_trang_thai` | `khach_hang` trả danh sách khách hàng cho dropdown chọn chủ vận đơn. `them` tự sinh mã vận đơn (`'VD' . date('ymd') . rand(1000,9999)`) và **tự ghi 1 dòng vào `lich_su_trang_thai`** ngay khi tạo. `doi_trang_thai` là action **riêng biệt** với `sua` — chỉ đổi cột `trang_thai` và luôn ghi thêm 1 dòng lịch sử mới (đây là nghiệp vụ cốt lõi, không phải update thông thường). |
| `khach_hang_ajax.php` | `danh_sach`, `them`, `sua`, `xoa` | Kiểm tra trùng `username` trước khi thêm/sửa. Mật khẩu chỉ được cập nhật nếu người dùng nhập (ô để trống khi sửa = giữ nguyên mật khẩu cũ) — xử lý bằng 2 câu `UPDATE` khác nhau tùy có mật khẩu mới hay không. |
| `phuong_tien_ajax.php` | `danh_sach`, `kho`, `them`, `sua`, `xoa` | `kho` trả danh sách kho (id, tên) cho dropdown "Kho phụ trách". Kiểm tra trùng `bien_so` trước khi thêm/sửa. |
| `kho_ajax.php` | `danh_sach`, `them`, `sua`, `xoa` | `danh_sach` có subquery đếm `so_phuong_tien` đang gán vào từng kho. |
| `tuyen_duong_ajax.php` | `danh_sach`, `kho`, `them`, `sua`, `xoa` | Hàm `kiemTraTrungTuyen()` kiểm tra đã tồn tại tuyến giữa 2 kho đó chưa (bỏ qua chính bản ghi đang sửa nếu có). `layDuLieuTuyenDuong()` chặn luôn trường hợp kho đi = kho đến. |
| `thong_ke_ajax.php` | *(không có action, chỉ 1 luồng)* | Chạy nhiều truy vấn tổng hợp độc lập (tổng vận đơn, tổng khách hàng, tổng doanh thu, nhóm theo trạng thái, nhóm theo 7 ngày gần nhất, top 5 vận đơn mới, top 5 khách hàng, thống kê phương tiện/kho) rồi gộp thành 1 JSON duy nhất cho Dashboard. Không có thao tác ghi (chỉ đọc). |

---

## 5. Chi tiết từng trang trong `pages/`

Mỗi trang admin (`van_don.php`, `khach_hang.php`, `kho.php`, `phuong_tien.php`, `tuyen_duong.php`)
đi theo đúng 1 khuôn JS giống nhau:

1. `taiDanhSach...()` — gọi `fetch(...&action=danh_sach...)`, build lại `<tbody>` bằng template
   literal, lưu kết quả vào 1 biến toàn cục `danhSach...HienTai` (mảng JS).
2. `moModalSuaTheoId(id)` / `xoaXxxTheoId(id)` — nút trong bảng chỉ truyền `id`, hàm này **tra
   cứu lại object đầy đủ từ biến toàn cục** rồi mới gọi `moModalSua(obj)` / `xoaXxx(...)`.
   *(Thiết kế này để tránh nhúng JSON trực tiếp vào thuộc tính `onclick` — vốn từng gây lỗi vỡ
   HTML nếu dữ liệu chứa dấu nháy đơn — xem mục 9.)*
3. `moModalThem()` / `moModalSua(obj)` — reset hoặc điền sẵn form trong modal Bootstrap.
4. `luuXxx(e)` — `e.preventDefault()`, gom `FormData`, thêm `action=them` hoặc `action=sua` tùy
   có `id` hay không, `fetch POST`, load lại danh sách nếu thành công.
5. `xoaXxx(...)` — `confirm()` rồi `fetch POST action=xoa`.

### Điểm riêng của từng trang
- **`van_don.php`** — có thêm ô `<select class="trang-thai-select">` ngay trong bảng, đổi trạng
  thái tức thời qua hàm `doiTrangThai(id, trangThai)` (gọi riêng `action=doi_trang_thai`, không
  qua modal sửa). Có ô tìm kiếm với **debounce 350ms** (`setTimeout`/`clearTimeout`) để tránh gọi
  API liên tục khi gõ.
- **`khach_hang.php`** — form có 2 trạng thái nhãn khác nhau cho ô mật khẩu tùy đang Thêm hay Sửa
  (`fMatKhauGhiChu` đổi text, `fMatKhauSao` ẩn/hiện dấu `*`).
- **`phuong_tien.php`** — gọi thêm `taiDanhSachKho()` để đổ dữu liệu dropdown "Kho phụ trách"
  song song với `taiDanhSachPT()`.
- **`tuyen_duong.php`** — dropdown "Kho đi"/"Kho đến" dùng chung 1 danh sách kho (gọi 1 lần,
  gán `innerHTML` cho cả 2 select).
- **`thong_tin_giao_hang.php`** (khách hàng) — khác biệt hẳn: không có modal CRUD, chỉ hiển thị.
  - `taoTheVanDon(don, chiSo)` — dựng HTML 1 "thẻ vận đơn" (waybill): thanh tiến trình
    (`taoThanhTienTrinh`), khối người gửi/nhận, `<ul class="nvc-timeline">` dựng từ `don.lich_su`,
    nút sao chép mã vận đơn, và nhãn "Mới cập nhật" nếu có.
  - `taoThanhTienTrinh(trangThai)` — dựng thanh tiến trình ngang dạng bước dựa trên mảng thứ tự
    cố định `CAC_BUOC`; nếu `trangThai === 'huy'` thì trả về banner cảnh báo thay vì thanh bước.
  - `moTaDuKien(ngayDuKien, trangThai)` — tính số ngày còn lại/quá hạn so với hôm nay
    (`new Date()` phía client), trả kèm class CSS (`con-som`/`qua-han`) để tô màu; bỏ qua tính
    toán nếu đơn đã `da_giao`/`huy` (không còn ý nghĩa).
  - `coCapNhatMoi(lichSu)` — so sánh `thoi_gian` của dòng lịch sử mới nhất với thời điểm hiện tại,
    trả `true` nếu trong vòng 24 giờ (dùng để gắn nhãn "Mới cập nhật").
  - `taiDuLieuGiaoHang(trang)` — gọi API kèm `trang`, `so_dong`, và 2 tham số lọc lấy trực tiếp
    từ UI mỗi lần gọi: `trang_thai` (từ `#nvcLocTrangThai`) và `q` (từ `#nvcTimMaVanDon`). Tham
    số `trang` khi được truyền (đổi trang / đổi lọc / gõ tìm kiếm) còn kích hoạt cuộn mượt
    (`scrollIntoView`) về đầu khối danh sách — không cuộn ở lần tải đầu tiên.
  - Ô tìm kiếm (`#nvcTimMaVanDon`) dùng **debounce 350ms** giống các trang admin; đổi dropdown
    lọc trạng thái (`#nvcLocTrangThai`) gọi lại `taiDuLieuGiaoHang(1)` ngay (luôn quay về trang 1
    khi đổi điều kiện lọc/tìm kiếm, tránh trường hợp trang hiện tại vượt quá số trang mới).
  - `renderPhanTrang(pt)` — dựng các nút số trang + nút tiến/lùi dựa trên object `phan_trang`
    mà API trả về (`trang_hien_tai`, `tong_so_trang`, `tong_so_don` — số này phản ánh **tổng số
    đơn khớp bộ lọc hiện tại**, không phải tổng toàn bộ đơn của khách hàng).
- **`dashboard.php`** — không có CRUD, chỉ gọi 1 lần `fetch thong_ke_ajax.php`, sau đó:
  - Vẽ 2 biểu đồ bằng **Chart.js**: cột (7 ngày gần nhất) và doughnut (tỉ lệ trạng thái).
  - Render các danh sách tĩnh (vận đơn mới nhất, top khách hàng, phân bổ phương tiện theo kho)
    bằng template literal thông thường, không cần modal/CRUD.

---

## 6. Cơ chế phân trang

Áp dụng cho **cả trang khách hàng** (`thong_tin_giao_hang.php`) **và cả 5 trang quản lý ở Admin**
(`van_don.php`, `khach_hang.php`, `phuong_tien.php`, `kho.php`, `tuyen_duong.php`).

- Mỗi file AJAX `danh_sach` nhận `trang` (mặc định 1) và `so_dong` (mặc định 5 ở trang khách
  hàng, 10 ở các trang admin; giới hạn 1–50 hoặc 1–100 tùy file) qua GET.
- Khi có bộ lọc/tìm kiếm (`q`, `trang_thai`...), điều kiện `WHERE` được build thành 1 biến chung
  (`$dieuKien` + `$params`) rồi tái sử dụng cho **cả câu đếm tổng số** (`SELECT COUNT(*) ...`)
  **và câu lấy dữ liệu** (`SELECT ... LIMIT ... OFFSET ...`) — đảm bảo số trang luôn khớp với
  đúng tập dữ liệu đang được lọc, không phải tổng toàn bộ bảng.
- Tính `offset = (trang - 1) * so_dong`, dùng `LIMIT :so_dong OFFSET :offset` — **bind bằng
  `bindValue(..., PDO::PARAM_INT)`** thay vì truyền thẳng trong mảng `execute()`, vì MySQL không
  chấp nhận `LIMIT`/`OFFSET` dạng chuỗi khi dùng prepared statement thật.
- Nếu `trang` được truyền lớn hơn tổng số trang thực tế, backend **tự kẹp về trang hợp lệ cuối
  cùng** (`if ($trang > $tongSoTrang) $trang = $tongSoTrang;`) thay vì trả mảng rỗng hoặc lỗi.
- Trả kèm object `phan_trang` (`trang_hien_tai`, `tong_so_trang`, `tong_so_don`) để frontend biết
  tổng số trang mà không cần tính lại.
- **Frontend:**
  - Ở `thong_tin_giao_hang.php`, hàm `renderPhanTrang()` được định nghĩa **riêng trong chính
    trang đó** (vì trang này không dùng layout Admin, không có `admin.js`).
  - Ở 5 trang Admin, hàm `renderPhanTrang(pt, hamTai)` dùng chung từ `assets/js/admin.js`
    (mục 3.4) — nhận thêm tham số `hamTai` (function) để biết gọi lại đúng hàm tải danh sách của
    trang đó khi người dùng bấm chuyển trang (mỗi trang có 1 hàm `taiDanhSach...` riêng).
  - Quy ước gọi lại sau khi Thêm/Sửa/Xóa (thống nhất ở cả 5 trang admin): **Thêm mới** →
    `taiDanhSach...(1)` (quay về trang 1, vì bản ghi mới luôn đứng đầu do `ORDER BY ... DESC`);
    **Sửa/Xóa/đổi trạng thái** → gọi hàm tải danh sách **không truyền tham số trang** (giữ
    nguyên trang đang xem, biến trang hiện tại đã được lưu ở 1 biến toàn cục kiểu
    `trangHienTaiXxx` trong mỗi trang).
  - Đổi ô tìm kiếm (debounce 350ms) hoặc đổi dropdown lọc đều gọi lại với tham số trang `1` —
    tránh trường hợp đang ở trang 5 rồi lọc ra kết quả chỉ có 1 trang, gây hiển thị rỗng.

---

## 7. `assets/js/main.js` — hiệu ứng dùng chung

File JS duy nhất được nạp cho **mọi trang dùng `header.php`/`footer.php`** (không dùng ở trang
admin). Bọc trong 1 IIFE để tránh rò biến ra global scope. Gồm 4 hàm độc lập, mỗi hàm tự kiểm tra
phần tử liên quan có tồn tại trên trang hay không trước khi chạy (an toàn khi 1 trang không có
đủ mọi phần tử):

| Hàm | Cơ chế |
|---|---|
| `khoiTaoNavbarCuon()` | Lắng nghe `scroll`, thêm/bỏ class `.scrolled` cho `.nvc-navbar` khi `scrollY > 20`. |
| `khoiTaoScrollReveal()` | Dùng `IntersectionObserver` để thêm class `.nvc-reveal-in` cho các phần tử `.nvc-reveal`/`.nvc-reveal-stagger` khi cuộn tới (`threshold: 0.15`). Tự tắt hiệu ứng (hiện luôn) nếu trình duyệt không hỗ trợ `IntersectionObserver` hoặc người dùng bật `prefers-reduced-motion`. |
| `khoiTaoDemSo()` | Với mỗi phần tử có `data-count-to`, dùng `requestAnimationFrame` + easing (`easeOut` bậc 3) để đếm số chạy từ 0 lên giá trị đích trong 1.4s, chỉ bắt đầu khi phần tử lọt vào khung nhìn (`IntersectionObserver`, `threshold: 0.5`). |
| `khoiTaoNutLenDau()` | Hiện/ẩn nút `#nvcScrollTop` theo `scrollY > 500`, click thì `window.scrollTo({top:0, behavior:'smooth'})`. |

---

## 8. Cấu trúc Database & ràng buộc

```
nguoi_dung (id, username, mat_khau, ho_ten, sdt, email, vai_tro, ngay_tao)
    │
    └──< don_hang (id, ma_van_don, nguoi_dung_id→FK, ten_nguoi_gui, dia_chi_gui,
    │              ten_nguoi_nhan, sdt_nguoi_nhan, dia_chi_nhan, loai_hang,
    │              trong_luong, phi_van_chuyen, trang_thai, ngay_tao, ngay_du_kien)
    │       │
    │       └──< lich_su_trang_thai (id, don_hang_id→FK, trang_thai, dia_diem, ghi_chu, thoi_gian)
    │
kho_trung_chuyen (id, ten_kho, khu_vuc, dia_chi, nguoi_quan_ly, sdt_quan_ly, trang_thai, ngay_tao)
    │
    ├──< phuong_tien (id, bien_so, loai_xe, ten_tai_xe, sdt_tai_xe, trang_thai, kho_id→FK, ghi_chu)
    │
    └──< tuyen_duong (id, kho_di_id→FK, kho_den_id→FK, khoang_cach_km,
                       thoi_gian_du_kien_gio, trang_thai, ghi_chu)
                       [UNIQUE (kho_di_id, kho_den_id)]
```

**Các `ON DELETE` đáng chú ý:**
- Xóa `nguoi_dung` → xóa CASCADE toàn bộ `don_hang` của người đó (và theo đó là `lich_su_trang_thai`).
- Xóa `don_hang` → xóa CASCADE `lich_su_trang_thai` liên quan.
- Xóa `kho_trung_chuyen` → `phuong_tien.kho_id` tự chuyển về `NULL` (`ON DELETE SET NULL`, xe
  không bị xóa, chỉ mất gán kho); `tuyen_duong` liên quan bị xóa CASCADE.

**Vai trò** (`nguoi_dung.vai_tro`) là ENUM `admin` / `khach_hang` — dùng chung 1 bảng thay vì
tách 2 bảng riêng, đơn giản hóa việc đăng nhập (chỉ cần 1 câu truy vấn theo `username`).

---

## 9. Các "bẫy" kỹ thuật đã gặp và cách đã xử lý (đọc trước khi sửa code)

1. **Không lặp tên tham số PDO trong 1 câu SQL.**
   Vì `ATTR_EMULATE_PREPARES = false`, viết `WHERE a LIKE :tk OR b LIKE :tk` sẽ ném
   `SQLSTATE[HY093]: Invalid parameter number` dù chỉ bind 1 lần. Luôn đặt tên riêng
   (`:tk1`, `:tk2`...) cho mỗi lần xuất hiện, dù cùng 1 giá trị. *(Đã sửa ở cả 5 file tìm kiếm.)*

2. **Import SQL qua dòng lệnh `mysql` có thể làm hỏng tiếng Việt** nếu client dùng charset khác
   `utf8mb4` mặc định (thường là `latin1`), gây "double-encode" — dữ liệu bị hỏng **thật trong DB**,
   không chỉ hỏng khi hiển thị. File `database/nha_van_chuyen.sql` đã có `SET NAMES utf8mb4;`
   ngay đầu file để tự sửa vấn đề này bất kể client dùng charset gì.

3. **`mb_substr()` có thể không tồn tại** nếu thiếu extension `mbstring` → dùng hàm
   `kyTuDauTien()` (mục 3.2) thay vì gọi `mb_substr()` trực tiếp ở bất kỳ đâu khác trong code.

4. **Không nhúng dữ liệu động trực tiếp vào thuộc tính `onclick`** (kiểu
   `onclick='moModalSua(${JSON.stringify(obj)})'`) — nếu dữ liệu (tên, địa chỉ...) chứa dấu nháy
   đơn sẽ làm vỡ HTML. Luôn dùng mẫu "lưu mảng toàn cục + tra cứu theo id" như mô tả ở mục 5.

5. **`border-radius: 50%` chỉ tạo hình tròn khi width = height cố định.** Tránh vừa animate
   `width` vừa dùng `border-radius: 50%` (từng làm nút Zalo méo thành hình bầu dục khi hover) —
   nếu cần hiệu ứng mở rộng, tách nhãn ra thành phần tử riêng (`position: absolute`) thay vì đổi
   kích thước chính nút.

6. **Nút bấm cần reset `margin/padding` mặc định của trình duyệt** khi định vị bằng
   `position: absolute` bên trong 1 khối khác (input, thẻ...) — nếu không, sai lệch vị trí vài
   pixel do padding/margin mặc định của `<button>` cộng dồn vào toạ độ `right/left`.

7. **`$urlGoc` phải được khai báo đúng trước khi include `header.php`/`footer.php`** — quên khai
   báo hoặc khai báo sai (`''` thay vì `'../'`) sẽ làm toàn bộ CSS/JS/link trong layout bị sai
   đường dẫn ở trang đó.

---

## 10. Quy ước đặt tên xuyên suốt dự án

- **Toàn bộ tên bảng, cột, biến PHP, hàm PHP, hàm JS đều dùng tiếng Việt không dấu** (vd:
  `don_hang`, `ma_van_don`, `layDuLieuVanDon`, `taiDanhSachKho`) — chỉ có tên class CSS
  (`nvc-*`), key JSON (`thanh_cong`, `du_lieu`...) và code HTML/CSS/JS core là giữ quy ước
  tiếng Anh/kebab-case theo chuẩn web thông thường.
- Tiền tố `nvc-` cho **mọi class CSS tự viết** — để không xung đột với class của Bootstrap.
- Tên hàm AJAX-loader luôn bắt đầu `taiDanhSach...` (tải danh sách), form-submit luôn
  `luu...` (lưu), xóa luôn `xoa...`, mở modal luôn `moModal...`.
- File AJAX luôn đặt tên `<đối_tượng>_ajax.php`, đặt trong `ajax/`, không có file nào ngoài
  thư mục này được truy vấn trực tiếp từ JS.

---

## 11. Bootstrap trong dự án — chi tiết sử dụng

### 11.1. Cách nhúng
- Nhúng qua **CDN jsdelivr**, không tải về cục bộ: `bootstrap@5.3.3` (CSS + JS bundle, đã gộp
  sẵn Popper.js) và `bootstrap-icons@1.11.3`.
- Mỗi loại layout (`header.php`, `admin_header.php`, `login.php`) **tự khai báo link riêng**
  trong thẻ `<head>` của nó (không có 1 include `<head>` dùng chung duy nhất), vì 3 layout
  này độc lập nhau. Thứ tự nhúng luôn là:
  ```html
  <link href="...bootstrap.min.css" rel="stylesheet">
  <link href="...bootstrap-icons.css" rel="stylesheet">
  <link href="...assets/css/style.css" rel="stylesheet">   <!-- load SAU CÙNG để ghi đè -->
  ```
- File `bootstrap.bundle.min.js` (JS + Popper) được nhúng ở **cuối trang**, ngay trước
  `</body>`, sau toàn bộ nội dung — để không chặn hiển thị trang trong lúc tải.

### 11.2. Các thành phần Bootstrap thực sự được dùng

| Thành phần Bootstrap | Dùng ở đâu | Mục đích |
|---|---|---|
| Grid (`.container`, `.row`, `.col-*`, `.g-*`) | Toàn bộ dự án | Bố cục responsive, không viết media query riêng cho từng khối |
| Navbar + Collapse (`.navbar-expand-lg`, `.navbar-toggler`, `.collapse`) | `header.php` | Menu chính, tự thu gọn thành menu hamburger trên mobile |
| Dropdown (`.dropdown`, `.dropdown-toggle`, `.dropdown-menu`) | Menu user ở `header.php`, topbar ở `admin_header.php` | Menu "Đăng xuất" sổ xuống khi bấm avatar |
| **Modal** (`.modal`, `.modal-dialog`, `.modal-content`...) | **Tất cả form Thêm/Sửa ở Admin** (`van_don.php`, `khach_hang.php`, `phuong_tien.php`, `kho.php`, `tuyen_duong.php`) | Hiện form nhập liệu dạng popup; khởi tạo bằng `new bootstrap.Modal(...)` trong JS của từng trang |
| Forms (`.form-control`, `.form-select`, `.form-label`) | Mọi form trong dự án (đăng nhập, liên hệ, các modal admin) | Style input/select mặc định, sau đó bị `style.css` ghi đè lại màu viền/focus theo bộ nhận diện riêng |
| Buttons (`.btn`, `.btn-close`) | `.btn-close` dùng cho nút đóng modal; `.btn` nền dùng cho vài nút phụ (Hủy...) | Nút hành động **chính** (CTA, Lưu, Đăng nhập) dùng class tự viết (`.nvc-btn-accent`, `.btn-accent-lg`, `.nvc-btn-login`) — **không dùng `.btn-primary`** vì màu xanh dương mặc định của Bootstrap không hợp bộ nhận diện navy + cam |
| Utilities (`d-flex`, `justify-content-*`, `align-items-*`, `gap-*`, `text-muted`, `text-danger`, `mb-*`, `py-*`, `d-none`, `d-lg-flex`, `table-responsive`...) | Rải khắp mọi trang | Canh layout nhanh, ẩn/hiện theo breakpoint, không cần viết CSS riêng cho việc vặt |

### 11.3. Những gì **không** dùng theo mặc định của Bootstrap (tự viết đè lên)

Dự án dùng Bootstrap như một **bộ khung hành vi + lưới layout**, còn toàn bộ giao diện trực
quan (màu sắc, hình khối) đều tự viết trong `style.css` với tiền tố `nvc-` để không lẫn với
Bootstrap gốc:

| Mặc định Bootstrap | Thay bằng | Lý do |
|---|---|---|
| Bảng màu (`.btn-primary`, `.bg-primary`, `.text-primary`...) | Biến CSS riêng (`--navy-800`, `--accent`...) | Bộ nhận diện navy + cam, không dùng xanh dương mặc định |
| `.card` | `.nvc-waybill`, `.nvc-dash-card`, `.nvc-service-card`, `.nvc-summary-card`... | Cần thiết kế đặc thù (viền răng cưa như phiếu gửi hàng, icon riêng, hiệu ứng hover riêng) |
| `.table` | `.nvc-table` | Kiểm soát màu header (navy), màu hover, khoảng cách riêng cho từng module admin |
| `.badge` | `.nvc-badge` + biến thể theo trạng thái (`.badge-hoat_dong`, `.badge-cho_xu_ly`...) | Mỗi trạng thái nghiệp vụ (chờ xử lý/đang vận chuyển/đã giao/hủy...) cần 1 màu riêng nhất quán xuyên suốt toàn bộ dự án |

### 11.4. Bootstrap Icons
- Dùng qua class `bi bi-*` (vd: `bi-truck`, `bi-box-seam`, `bi-telephone-fill`, `bi-building`) —
  xuất hiện ở icon menu sidebar, icon trong bảng, icon nút hành động, icon thẻ dịch vụ, v.v.
- **Riêng icon Zalo không dùng Bootstrap Icons** (bộ icon này không có sẵn icon Zalo) — được vẽ
  bằng 1 đoạn SVG tùy chỉnh nhúng trực tiếp trong `includes/footer.php` (xem mục 3.3 / mục 9.5).

### 11.5. Vì sao dùng Bootstrap thay vì tự viết Flexbox/Grid từ đầu
- Đề bài yêu cầu rõ phải dùng Bootstrap.
- Các thành phần **có hành vi JS phức tạp** (Modal mở/đóng, Dropdown tự đóng khi click ra ngoài,
  Collapse cho menu mobile) được Bootstrap xử lý sẵn — nếu tự viết từ đầu sẽ tốn nhiều công sức
  hơn cho phần "hành vi", trong khi mục tiêu chính của dự án là phần thiết kế riêng (bộ nhận diện
  navy + cam, thẻ "vận đơn" kiểu phiếu gửi hàng...).
- Grid system giúp toàn bộ trang responsive tốt trên mobile/tablet/desktop mà không cần viết
  media query cho từng khối riêng lẻ.

---

*Tài liệu này mô tả trạng thái mã nguồn tại thời điểm tạo. Khi thêm module mới, nên bổ sung theo
đúng khuôn đã mô tả ở mục 4 (AJAX) và mục 5 (trang admin) để giữ tính nhất quán của toàn bộ dự án.*
