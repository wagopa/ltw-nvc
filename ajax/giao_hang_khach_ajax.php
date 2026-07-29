<?php
/**
 * AJAX: Lấy danh sách đơn hàng (vận đơn) của khách hàng đang đăng nhập,
 * kèm lịch sử trạng thái từng đơn. Có phân trang, lọc theo trạng thái, tìm theo mã vận đơn.
 * Tham số: trang (mặc định 1), so_dong (mặc định 5), trang_thai (lọc, optional), q (tìm mã vận đơn, optional)
 * Trả JSON.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['nguoi_dung_id']) || $_SESSION['vai_tro'] !== 'khach_hang') {
    http_response_code(401);
    echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Bạn cần đăng nhập với tài khoản khách hàng.']);
    exit;
}

$nguoiDungId = $_SESSION['nguoi_dung_id'];

// Tham số phân trang
$trang = max(1, (int)($_GET['trang'] ?? 1));
$soDong = (int)($_GET['so_dong'] ?? 5);
if ($soDong < 1 || $soDong > 50) $soDong = 5;

// Tham số lọc / tìm kiếm
$cacTrangThaiHopLe = ['cho_xu_ly', 'da_tiep_nhan', 'dang_van_chuyen', 'da_giao', 'huy'];
$trangThaiLoc = trim($_GET['trang_thai'] ?? '');
if (!in_array($trangThaiLoc, $cacTrangThaiHopLe, true)) $trangThaiLoc = '';
$tuKhoa = trim($_GET['q'] ?? '');

try {
    $pdo = ketNoiDatabase();

    // Thống kê nhanh — luôn tính trên TOÀN BỘ đơn hàng của khách (không bị ảnh hưởng bởi bộ lọc)
    $stmt = $pdo->prepare("
        SELECT trang_thai, COUNT(*) AS so_luong
        FROM don_hang
        WHERE nguoi_dung_id = :nguoi_dung_id
        GROUP BY trang_thai
    ");
    $stmt->execute(['nguoi_dung_id' => $nguoiDungId]);
    $theoTrangThaiRaw = $stmt->fetchAll();

    $tongSo = 0;
    $choXuLy = 0;
    $dangVanChuyen = 0;
    $daGiao = 0;
    foreach ($theoTrangThaiRaw as $row) {
        $tongSo += (int)$row['so_luong'];
        if ($row['trang_thai'] === 'cho_xu_ly') $choXuLy = (int)$row['so_luong'];
        if (in_array($row['trang_thai'], ['da_tiep_nhan', 'dang_van_chuyen'], true)) $dangVanChuyen += (int)$row['so_luong'];
        if ($row['trang_thai'] === 'da_giao') $daGiao = (int)$row['so_luong'];
    }

    // Điều kiện lọc dùng chung cho câu đếm + câu lấy danh sách
    $dieuKienLoc = "";
    $thamSoLoc = ['nguoi_dung_id' => $nguoiDungId];
    if ($trangThaiLoc !== '') {
        $dieuKienLoc .= " AND trang_thai = :trang_thai_loc";
        $thamSoLoc['trang_thai_loc'] = $trangThaiLoc;
    }
    if ($tuKhoa !== '') {
        $dieuKienLoc .= " AND ma_van_don LIKE :tu_khoa";
        $thamSoLoc['tu_khoa'] = "%$tuKhoa%";
    }

    // Đếm tổng số đơn PHÙ HỢP BỘ LỌC (dùng để tính phân trang đúng theo bộ lọc)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM don_hang WHERE nguoi_dung_id = :nguoi_dung_id $dieuKienLoc");
    $stmt->execute($thamSoLoc);
    $tongSoLoc = (int)$stmt->fetchColumn();

    $tongSoTrang = max(1, (int)ceil($tongSoLoc / $soDong));
    if ($trang > $tongSoTrang) $trang = $tongSoTrang;
    $offset = ($trang - 1) * $soDong;

    // Lấy danh sách đơn hàng theo bộ lọc + TRANG hiện tại, mới nhất trước
    $stmt = $pdo->prepare("
        SELECT id, ma_van_don, ten_nguoi_gui, dia_chi_gui, ten_nguoi_nhan, sdt_nguoi_nhan,
               dia_chi_nhan, loai_hang, trong_luong, phi_van_chuyen, trang_thai, ngay_tao, ngay_du_kien
        FROM don_hang
        WHERE nguoi_dung_id = :nguoi_dung_id $dieuKienLoc
        ORDER BY ngay_tao DESC
        LIMIT :so_dong OFFSET :offset
    ");
    foreach ($thamSoLoc as $ten => $giaTri) {
        $stmt->bindValue(':' . $ten, $giaTri);
    }
    $stmt->bindValue(':so_dong', $soDong, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $donHangs = $stmt->fetchAll();

    // Lấy lịch sử trạng thái cho từng đơn trong trang hiện tại
    $stmtLichSu = $pdo->prepare("
        SELECT trang_thai, dia_diem, ghi_chu, thoi_gian
        FROM lich_su_trang_thai
        WHERE don_hang_id = :don_hang_id
        ORDER BY thoi_gian DESC
    ");

    foreach ($donHangs as &$don) {
        $stmtLichSu->execute(['don_hang_id' => $don['id']]);
        $don['lich_su'] = $stmtLichSu->fetchAll();
    }
    unset($don);

    echo json_encode([
        'thanh_cong' => true,
        'thong_ke'   => [
            'tong_so'         => $tongSo,
            'dang_van_chuyen' => $dangVanChuyen,
            'da_giao'         => $daGiao,
            'cho_xu_ly'       => $choXuLy,
        ],
        'phan_trang' => [
            'trang_hien_tai' => $trang,
            'tong_so_trang'  => $tongSoTrang,
            'so_dong'        => $soDong,
            'tong_so_don'    => $tongSoLoc,
        ],
        'don_hang' => $donHangs,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Lỗi truy vấn dữ liệu: ' . $e->getMessage()]);
}
