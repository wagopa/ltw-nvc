<?php
/**
 * AJAX: Dữ liệu thống kê cho Dashboard (Admin).
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['nguoi_dung_id']) || $_SESSION['vai_tro'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Bạn không có quyền truy cập.']);
    exit;
}

$pdo = ketNoiDatabase();

try {
    // Tổng quan
    $tongVanDon = (int)$pdo->query("SELECT COUNT(*) FROM don_hang")->fetchColumn();
    $tongKhachHang = (int)$pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'khach_hang'")->fetchColumn();
    $tongDoanhThu = (float)$pdo->query("SELECT COALESCE(SUM(phi_van_chuyen),0) FROM don_hang WHERE trang_thai != 'huy'")->fetchColumn();

    // Số lượng theo từng trạng thái
    $stmt = $pdo->query("SELECT trang_thai, COUNT(*) AS so_luong FROM don_hang GROUP BY trang_thai");
    $theoTrangThaiRaw = $stmt->fetchAll();
    $theoTrangThai = ['cho_xu_ly' => 0, 'da_tiep_nhan' => 0, 'dang_van_chuyen' => 0, 'da_giao' => 0, 'huy' => 0];
    foreach ($theoTrangThaiRaw as $row) {
        $theoTrangThai[$row['trang_thai']] = (int)$row['so_luong'];
    }

    // Số vận đơn tạo mới theo 7 ngày gần nhất
    $stmt = $pdo->query("
        SELECT DATE(ngay_tao) AS ngay, COUNT(*) AS so_luong
        FROM don_hang
        WHERE ngay_tao >= (CURDATE() - INTERVAL 6 DAY)
        GROUP BY DATE(ngay_tao)
        ORDER BY ngay ASC
    ");
    $theoNgayRaw = $stmt->fetchAll();
    $theoNgayMap = [];
    foreach ($theoNgayRaw as $row) { $theoNgayMap[$row['ngay']] = (int)$row['so_luong']; }

    $nhan7Ngay = [];
    $soLuong7Ngay = [];
    for ($i = 6; $i >= 0; $i--) {
        $ngay = date('Y-m-d', strtotime("-$i day"));
        $nhan7Ngay[] = date('d/m', strtotime($ngay));
        $soLuong7Ngay[] = $theoNgayMap[$ngay] ?? 0;
    }

    // 5 vận đơn mới nhất
    $stmt = $pdo->query("
        SELECT d.ma_van_don, d.ten_nguoi_nhan, d.trang_thai, d.ngay_tao, n.ho_ten AS ten_khach_hang
        FROM don_hang d
        JOIN nguoi_dung n ON n.id = d.nguoi_dung_id
        ORDER BY d.ngay_tao DESC
        LIMIT 5
    ");
    $moiNhat = $stmt->fetchAll();

    // Top 5 khách hàng có nhiều vận đơn nhất
    $stmt = $pdo->query("
        SELECT n.ho_ten, COUNT(d.id) AS so_van_don
        FROM nguoi_dung n
        JOIN don_hang d ON d.nguoi_dung_id = n.id
        WHERE n.vai_tro = 'khach_hang'
        GROUP BY n.id
        ORDER BY so_van_don DESC
        LIMIT 5
    ");
    $topKhachHang = $stmt->fetchAll();

    // Thống kê phương tiện theo trạng thái
    $tongPhuongTien = (int)$pdo->query("SELECT COUNT(*) FROM phuong_tien")->fetchColumn();
    $stmt = $pdo->query("SELECT trang_thai, COUNT(*) AS so_luong FROM phuong_tien GROUP BY trang_thai");
    $ptTheoTrangThaiRaw = $stmt->fetchAll();
    $ptTheoTrangThai = ['hoat_dong' => 0, 'bao_tri' => 0, 'ngung_hoat_dong' => 0];
    foreach ($ptTheoTrangThaiRaw as $row) {
        $ptTheoTrangThai[$row['trang_thai']] = (int)$row['so_luong'];
    }

    // Thống kê kho trung chuyển
    $tongKho = (int)$pdo->query("SELECT COUNT(*) FROM kho_trung_chuyen")->fetchColumn();
    $khoHoatDong = (int)$pdo->query("SELECT COUNT(*) FROM kho_trung_chuyen WHERE trang_thai = 'hoat_dong'")->fetchColumn();

    // Số phương tiện theo từng kho (để hiển thị danh sách phân bổ)
    $stmt = $pdo->query("
        SELECT k.ten_kho, k.khu_vuc,
               COUNT(p.id) AS so_phuong_tien
        FROM kho_trung_chuyen k
        LEFT JOIN phuong_tien p ON p.kho_id = k.id
        GROUP BY k.id
        ORDER BY so_phuong_tien DESC
    ");
    $phuongTienTheoKho = $stmt->fetchAll();

    echo json_encode([
        'thanh_cong' => true,
        'tong_quan' => [
            'tong_van_don'   => $tongVanDon,
            'tong_khach_hang' => $tongKhachHang,
            'tong_doanh_thu' => $tongDoanhThu,
        ],
        'theo_trang_thai' => $theoTrangThai,
        'theo_ngay' => ['nhan' => $nhan7Ngay, 'so_luong' => $soLuong7Ngay],
        'moi_nhat' => $moiNhat,
        'top_khach_hang' => $topKhachHang,
        'phuong_tien' => [
            'tong_so'        => $tongPhuongTien,
            'theo_trang_thai' => $ptTheoTrangThai,
        ],
        'kho' => [
            'tong_so'         => $tongKho,
            'hoat_dong'       => $khoHoatDong,
            'phuong_tien_theo_kho' => $phuongTienTheoKho,
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
}
