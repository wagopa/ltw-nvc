-- =========================================================
-- CƠ SỞ DỮ LIỆU: nha_van_chuyen
-- Website quản lý vận chuyển hàng hóa (logistics)
-- =========================================================

-- Ép charset kết nối về utf8mb4 khi import, tránh lỗi hiển thị sai
-- tiếng Việt (mojibake) nếu client mysql mặc định dùng charset khác (vd: latin1).
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS nha_van_chuyen
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE nha_van_chuyen;

-- ---------------------------------------------------------
-- Bảng: nguoi_dung
-- Gộp chung tài khoản admin và khách hàng, phân biệt bằng vai_tro
-- ---------------------------------------------------------
CREATE TABLE nguoi_dung (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    ho_ten VARCHAR(100) NOT NULL,
    sdt VARCHAR(15) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    vai_tro ENUM('admin', 'khach_hang') NOT NULL DEFAULT 'khach_hang',
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Bảng: don_hang
-- Thông tin đơn vận chuyển, gắn với khách hàng qua nguoi_dung_id
-- ---------------------------------------------------------
CREATE TABLE don_hang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_van_don VARCHAR(20) NOT NULL UNIQUE,
    nguoi_dung_id INT NOT NULL,
    ten_nguoi_gui VARCHAR(100) NOT NULL,
    dia_chi_gui VARCHAR(255) NOT NULL,
    ten_nguoi_nhan VARCHAR(100) NOT NULL,
    sdt_nguoi_nhan VARCHAR(15) NOT NULL,
    dia_chi_nhan VARCHAR(255) NOT NULL,
    loai_hang VARCHAR(100) DEFAULT NULL,
    trong_luong DECIMAL(10,2) DEFAULT 0,
    phi_van_chuyen DECIMAL(12,0) DEFAULT 0,
    trang_thai ENUM('cho_xu_ly', 'da_tiep_nhan', 'dang_van_chuyen', 'da_giao', 'huy') NOT NULL DEFAULT 'cho_xu_ly',
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ngay_du_kien DATE DEFAULT NULL,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Bảng: lich_su_trang_thai
-- Lịch sử / dòng thời gian trạng thái của từng đơn hàng
-- ---------------------------------------------------------
CREATE TABLE lich_su_trang_thai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    don_hang_id INT NOT NULL,
    trang_thai ENUM('cho_xu_ly', 'da_tiep_nhan', 'dang_van_chuyen', 'da_giao', 'huy') NOT NULL,
    dia_diem VARCHAR(150) DEFAULT NULL,
    ghi_chu VARCHAR(255) DEFAULT NULL,
    thoi_gian DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (don_hang_id) REFERENCES don_hang(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Bảng: phuong_tien
-- Phương tiện vận chuyển (xe máy, xe tải...) và tài xế phụ trách
-- ---------------------------------------------------------
CREATE TABLE phuong_tien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bien_so VARCHAR(20) NOT NULL UNIQUE,
    loai_xe ENUM('xe_may', 'xe_tai_nho', 'xe_tai_lon') NOT NULL DEFAULT 'xe_may',
    ten_tai_xe VARCHAR(100) NOT NULL,
    sdt_tai_xe VARCHAR(15) NOT NULL,
    trang_thai ENUM('hoat_dong', 'bao_tri', 'ngung_hoat_dong') NOT NULL DEFAULT 'hoat_dong',
    kho_id INT DEFAULT NULL,
    ghi_chu VARCHAR(255) DEFAULT NULL,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Bảng: kho_trung_chuyen
-- Các kho / điểm trung chuyển hàng hóa theo khu vực
-- ---------------------------------------------------------
CREATE TABLE kho_trung_chuyen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_kho VARCHAR(150) NOT NULL,
    khu_vuc ENUM('mien_bac', 'mien_trung', 'mien_nam') NOT NULL,
    dia_chi VARCHAR(255) NOT NULL,
    nguoi_quan_ly VARCHAR(100) DEFAULT NULL,
    sdt_quan_ly VARCHAR(15) DEFAULT NULL,
    trang_thai ENUM('hoat_dong', 'tam_ngung') NOT NULL DEFAULT 'hoat_dong',
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Ràng buộc khóa ngoại phuong_tien -> kho_trung_chuyen (thêm sau vì kho_trung_chuyen
-- được định nghĩa phía dưới phuong_tien trong file này)
ALTER TABLE phuong_tien
    ADD CONSTRAINT fk_phuong_tien_kho
    FOREIGN KEY (kho_id) REFERENCES kho_trung_chuyen(id) ON DELETE SET NULL;

-- ---------------------------------------------------------
-- Bảng: tuyen_duong
-- Các tuyến vận chuyển cố định giữa 2 kho trung chuyển
-- ---------------------------------------------------------
CREATE TABLE tuyen_duong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kho_di_id INT NOT NULL,
    kho_den_id INT NOT NULL,
    khoang_cach_km DECIMAL(8,1) DEFAULT NULL,
    thoi_gian_du_kien_gio DECIMAL(6,1) DEFAULT NULL,
    trang_thai ENUM('hoat_dong', 'tam_ngung') NOT NULL DEFAULT 'hoat_dong',
    ghi_chu VARCHAR(255) DEFAULT NULL,
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tuyen (kho_di_id, kho_den_id),
    FOREIGN KEY (kho_di_id) REFERENCES kho_trung_chuyen(id) ON DELETE CASCADE,
    FOREIGN KEY (kho_den_id) REFERENCES kho_trung_chuyen(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- DỮ LIỆU MẪU
-- =========================================================

-- Mật khẩu mẫu cho cả 2 tài khoản bên dưới đều là: 123456
-- (đã băm bằng password_hash của PHP - thuật toán BCRYPT)
INSERT INTO nguoi_dung (username, mat_khau, ho_ten, sdt, email, vai_tro) VALUES
('admin', '$2b$10$M7N1mjhPNCbecM9mm9AqPuwxHHWxuZowzmavuMUNj97n8/TeGG.Wm', 'Quản trị viên', '0900000000', 'admin@nhavanchuyen.vn', 'admin'),
('khachhang01', '$2b$10$M7N1mjhPNCbecM9mm9AqPuwxHHWxuZowzmavuMUNj97n8/TeGG.Wm', 'Nguyễn Văn An', '0912345678', 'an.nguyen@gmail.com', 'khach_hang');

-- Đơn hàng mẫu gắn với khách hàng vừa tạo (id = 2 trên máy sạch)
INSERT INTO don_hang (ma_van_don, nguoi_dung_id, ten_nguoi_gui, dia_chi_gui, ten_nguoi_nhan, sdt_nguoi_nhan, dia_chi_nhan, loai_hang, trong_luong, phi_van_chuyen, trang_thai, ngay_du_kien) VALUES
('VD2026070001', 2, 'Công ty TNHH ABC', 'KCN Tân Bình, TP.HCM', 'Nguyễn Văn An', '0912345678', '123 Lê Lợi, Q.1, TP.HCM', 'Thiết bị điện tử', 2.50, 45000, 'dang_van_chuyen', '2026-07-27'),
('VD2026070002', 2, 'Shop Thời Trang XYZ', '45 Nguyễn Trãi, Hà Nội', 'Nguyễn Văn An', '0912345678', '123 Lê Lợi, Q.1, TP.HCM', 'Quần áo', 1.20, 30000, 'da_giao', '2026-07-20'),
('VD2026070003', 2, 'Nhà sách Minh Khai', '78 Trần Hưng Đạo, Đà Nẵng', 'Nguyễn Văn An', '0912345678', '123 Lê Lợi, Q.1, TP.HCM', 'Sách vở', 3.00, 40000, 'cho_xu_ly', '2026-07-30'),
('VD2026070004', 2, 'Cửa hàng Điện máy Xanh', '12 Cách Mạng Tháng 8, TP.HCM', 'Nguyễn Văn An', '0912345678', '123 Lê Lợi, Q.1, TP.HCM', 'Đồ gia dụng', 5.00, 55000, 'da_giao', '2026-07-15'),
('VD2026070005', 2, 'Shop Mỹ Phẩm Lan Anh', '89 Hai Bà Trưng, TP.HCM', 'Nguyễn Văn An', '0912345678', '123 Lê Lợi, Q.1, TP.HCM', 'Mỹ phẩm', 0.80, 25000, 'da_giao', '2026-07-10'),
('VD2026070006', 2, 'Xưởng Nội Thất Gỗ Việt', 'KCN Biên Hòa, Đồng Nai', 'Nguyễn Văn An', '0912345678', '123 Lê Lợi, Q.1, TP.HCM', 'Nội thất', 25.00, 120000, 'dang_van_chuyen', '2026-07-29'),
('VD2026070007', 2, 'Cửa hàng Thể Thao ProSport', '34 Lý Thường Kiệt, Hà Nội', 'Nguyễn Văn An', '0912345678', '123 Lê Lợi, Q.1, TP.HCM', 'Dụng cụ thể thao', 4.50, 42000, 'huy', '2026-07-12'),
('VD2026070008', 2, 'Tiệm Bánh Kẹo Hương Việt', '56 Trần Phú, Đà Nẵng', 'Nguyễn Văn An', '0912345678', '123 Lê Lợi, Q.1, TP.HCM', 'Thực phẩm', 2.00, 35000, 'cho_xu_ly', '2026-08-01');

INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai, dia_diem, ghi_chu, thoi_gian) VALUES
(1, 'cho_xu_ly', 'TP.HCM', 'Đơn hàng được tạo', '2026-07-22 08:00:00'),
(1, 'da_tiep_nhan', 'Kho TP.HCM', 'Đã tiếp nhận hàng từ người gửi', '2026-07-22 14:30:00'),
(1, 'dang_van_chuyen', 'Trung chuyển Đà Nẵng', 'Hàng đang trên đường vận chuyển', '2026-07-23 09:15:00'),

(2, 'cho_xu_ly', 'Hà Nội', 'Đơn hàng được tạo', '2026-07-18 09:00:00'),
(2, 'da_tiep_nhan', 'Kho Hà Nội', 'Đã tiếp nhận hàng từ người gửi', '2026-07-18 15:00:00'),
(2, 'dang_van_chuyen', 'Trung chuyển TP.HCM', 'Hàng đang trên đường vận chuyển', '2026-07-19 10:00:00'),
(2, 'da_giao', '123 Lê Lợi, Q.1, TP.HCM', 'Giao hàng thành công', '2026-07-20 16:45:00'),

(3, 'cho_xu_ly', 'Đà Nẵng', 'Đơn hàng được tạo, chờ xử lý', '2026-07-24 08:30:00'),

(4, 'cho_xu_ly', 'TP.HCM', 'Đơn hàng được tạo', '2026-07-13 08:00:00'),
(4, 'da_tiep_nhan', 'Kho TP.HCM', 'Đã tiếp nhận hàng từ người gửi', '2026-07-13 15:00:00'),
(4, 'dang_van_chuyen', 'Trung chuyển TP.HCM', 'Hàng đang trên đường vận chuyển', '2026-07-14 09:00:00'),
(4, 'da_giao', '123 Lê Lợi, Q.1, TP.HCM', 'Giao hàng thành công', '2026-07-15 11:20:00'),

(5, 'cho_xu_ly', 'TP.HCM', 'Đơn hàng được tạo', '2026-07-08 08:00:00'),
(5, 'da_giao', '123 Lê Lợi, Q.1, TP.HCM', 'Giao hàng thành công', '2026-07-10 14:00:00'),

(6, 'cho_xu_ly', 'Đồng Nai', 'Đơn hàng được tạo', '2026-07-27 08:00:00'),
(6, 'da_tiep_nhan', 'Kho Đồng Nai', 'Đã tiếp nhận hàng từ người gửi', '2026-07-27 16:00:00'),
(6, 'dang_van_chuyen', 'Trung chuyển TP.HCM', 'Hàng đang trên đường vận chuyển', '2026-07-28 08:30:00'),

(7, 'cho_xu_ly', 'Hà Nội', 'Đơn hàng được tạo', '2026-07-10 08:00:00'),
(7, 'huy', 'Hà Nội', 'Đơn hàng bị hủy theo yêu cầu người gửi', '2026-07-11 10:00:00'),

(8, 'cho_xu_ly', 'Đà Nẵng', 'Đơn hàng được tạo, chờ xử lý', '2026-08-01 08:00:00');

-- Kho trung chuyển mẫu
INSERT INTO kho_trung_chuyen (ten_kho, khu_vuc, dia_chi, nguoi_quan_ly, sdt_quan_ly, trang_thai) VALUES
('Kho trung chuyển TP.HCM', 'mien_nam', 'KCN Tân Bình, Q. Tân Bình, TP.HCM', 'Lê Văn Hùng', '0909111222', 'hoat_dong'),
('Kho trung chuyển Hà Nội', 'mien_bac', 'Khu công nghiệp Sài Đồng, Long Biên, Hà Nội', 'Phạm Thị Lan', '0909333444', 'hoat_dong'),
('Kho trung chuyển Đà Nẵng', 'mien_trung', '25 Nguyễn Văn Linh, Q. Thanh Khê, Đà Nẵng', 'Trần Văn Bình', '0909555666', 'hoat_dong');

-- Phương tiện mẫu
INSERT INTO phuong_tien (bien_so, loai_xe, ten_tai_xe, sdt_tai_xe, trang_thai, kho_id, ghi_chu) VALUES
('59A1-12345', 'xe_may', 'Nguyễn Văn Tài', '0911222333', 'hoat_dong', 1, 'Chuyên giao hàng nội thành TP.HCM'),
('51C-67890', 'xe_tai_nho', 'Hoàng Văn Sơn', '0911333444', 'hoat_dong', 1, 'Xe tải 1.5 tấn'),
('29B-11111', 'xe_tai_lon', 'Đỗ Văn Cường', '0911444555', 'bao_tri', 2, 'Đang bảo trì định kỳ, dự kiến hoạt động lại tuần sau'),
('43H-22222', 'xe_may', 'Lê Thị Mai', '0911555666', 'hoat_dong', 3, NULL);

-- Tuyến đường mẫu (giữa các kho: 1=TP.HCM, 2=Hà Nội, 3=Đà Nẵng)
INSERT INTO tuyen_duong (kho_di_id, kho_den_id, khoang_cach_km, thoi_gian_du_kien_gio, trang_thai, ghi_chu) VALUES
(1, 3, 964.0, 14.0, 'hoat_dong', 'Tuyến chính Nam - Trung'),
(3, 2, 764.0, 11.0, 'hoat_dong', 'Tuyến chính Trung - Bắc'),
(1, 2, 1710.0, 24.0, 'hoat_dong', 'Tuyến xuyên Việt Nam - Bắc'),
(2, 3, 764.0, 11.0, 'tam_ngung', 'Tạm ngừng do bảo trì cầu đường');
