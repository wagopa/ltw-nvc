<?php
/**
 * TIỆN ÍCH: Tạo hash mật khẩu bcrypt hợp lệ.
 * Cách dùng: mở http://localhost/nha-van-chuyen/tao_mat_khau.php?mk=123456
 * rồi copy chuỗi hash trả về, dùng để UPDATE cột mat_khau trong bảng nguoi_dung.
 * (Xóa file này sau khi dùng xong để đảm bảo an toàn.)
 */
$matKhau = $_GET['mk'] ?? '123456';
echo 'Mật khẩu gốc: ' . htmlspecialchars($matKhau) . '<br>';
echo 'Hash bcrypt: ' . password_hash($matKhau, PASSWORD_DEFAULT);
