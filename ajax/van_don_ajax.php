<?php
/**
 * AJAX: CRUD Quản lý vận đơn (Admin).
 * action = danh_sach | khach_hang | them | sua | xoa | doi_trang_thai
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
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        // -----------------------------------------------------
        case 'danh_sach':
            $tuKhoa = trim($_GET['q'] ?? '');
            $trangThai = trim($_GET['trang_thai'] ?? '');

            $trang = max(1, (int)($_GET['trang'] ?? 1));
            $soDong = (int)($_GET['so_dong'] ?? 10);
            if ($soDong < 1 || $soDong > 100) $soDong = 10;

            $dieuKien = "";
            $params = [];

            if ($tuKhoa !== '') {
                $dieuKien .= " AND (d.ma_van_don LIKE :tk1 OR d.ten_nguoi_nhan LIKE :tk2 OR n.ho_ten LIKE :tk3)";
                $params['tk1'] = "%$tuKhoa%";
                $params['tk2'] = "%$tuKhoa%";
                $params['tk3'] = "%$tuKhoa%";
            }
            if ($trangThai !== '') {
                $dieuKien .= " AND d.trang_thai = :trang_thai";
                $params['trang_thai'] = $trangThai;
            }

            $sqlDem = "SELECT COUNT(*) FROM don_hang d JOIN nguoi_dung n ON n.id = d.nguoi_dung_id WHERE 1=1 $dieuKien";
            $stmt = $pdo->prepare($sqlDem);
            $stmt->execute($params);
            $tongSo = (int)$stmt->fetchColumn();

            $tongSoTrang = max(1, (int)ceil($tongSo / $soDong));
            if ($trang > $tongSoTrang) $trang = $tongSoTrang;
            $offset = ($trang - 1) * $soDong;

            $sql = "SELECT d.*, n.ho_ten AS ten_khach_hang
                    FROM don_hang d
                    JOIN nguoi_dung n ON n.id = d.nguoi_dung_id
                    WHERE 1=1 $dieuKien
                    ORDER BY d.ngay_tao DESC
                    LIMIT :so_dong OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $ten => $giaTri) { $stmt->bindValue(':' . $ten, $giaTri); }
            $stmt->bindValue(':so_dong', $soDong, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode([
                'thanh_cong' => true,
                'du_lieu' => $stmt->fetchAll(),
                'phan_trang' => [
                    'trang_hien_tai' => $trang,
                    'tong_so_trang'  => $tongSoTrang,
                    'tong_so_don'    => $tongSo,
                ],
            ]);
            break;

        // -----------------------------------------------------
        case 'khach_hang':
            $stmt = $pdo->query("SELECT id, ho_ten, sdt FROM nguoi_dung WHERE vai_tro = 'khach_hang' ORDER BY ho_ten");
            echo json_encode(['thanh_cong' => true, 'du_lieu' => $stmt->fetchAll()]);
            break;

        // -----------------------------------------------------
        case 'them':
            $du = layDuLieuVanDon($_POST);
            if ($du['loi']) {
                echo json_encode(['thanh_cong' => false, 'thong_bao' => $du['loi']]);
                break;
            }

            $maVanDon = 'VD' . date('ymd') . rand(1000, 9999);

            $stmt = $pdo->prepare("
                INSERT INTO don_hang
                (ma_van_don, nguoi_dung_id, ten_nguoi_gui, dia_chi_gui, ten_nguoi_nhan, sdt_nguoi_nhan,
                 dia_chi_nhan, loai_hang, trong_luong, phi_van_chuyen, trang_thai, ngay_du_kien)
                VALUES
                (:ma_van_don, :nguoi_dung_id, :ten_nguoi_gui, :dia_chi_gui, :ten_nguoi_nhan, :sdt_nguoi_nhan,
                 :dia_chi_nhan, :loai_hang, :trong_luong, :phi_van_chuyen, :trang_thai, :ngay_du_kien)
            ");
            $stmt->execute(array_merge($du['data'], ['ma_van_don' => $maVanDon]));
            $donHangId = $pdo->lastInsertId();

            // Ghi lịch sử trạng thái khởi tạo
            $pdo->prepare("INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai, dia_diem, ghi_chu) VALUES (:id, :tt, :dd, 'Vận đơn được tạo')")
                ->execute(['id' => $donHangId, 'tt' => $du['data']['trang_thai'], 'dd' => $du['data']['dia_chi_gui']]);

            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã tạo vận đơn ' . $maVanDon]);
            break;

        // -----------------------------------------------------
        case 'sua':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Thiếu mã vận đơn.']); break; }

            $du = layDuLieuVanDon($_POST);
            if ($du['loi']) {
                echo json_encode(['thanh_cong' => false, 'thong_bao' => $du['loi']]);
                break;
            }

            $stmt = $pdo->prepare("
                UPDATE don_hang SET
                    nguoi_dung_id = :nguoi_dung_id,
                    ten_nguoi_gui = :ten_nguoi_gui,
                    dia_chi_gui = :dia_chi_gui,
                    ten_nguoi_nhan = :ten_nguoi_nhan,
                    sdt_nguoi_nhan = :sdt_nguoi_nhan,
                    dia_chi_nhan = :dia_chi_nhan,
                    loai_hang = :loai_hang,
                    trong_luong = :trong_luong,
                    phi_van_chuyen = :phi_van_chuyen,
                    trang_thai = :trang_thai,
                    ngay_du_kien = :ngay_du_kien
                WHERE id = :id
            ");
            $stmt->execute(array_merge($du['data'], ['id' => $id]));
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã cập nhật vận đơn.']);
            break;

        // -----------------------------------------------------
        case 'xoa':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Thiếu mã vận đơn.']); break; }

            $pdo->prepare("DELETE FROM don_hang WHERE id = :id")->execute(['id' => $id]);
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã xóa vận đơn.']);
            break;

        // -----------------------------------------------------
        case 'doi_trang_thai':
            $id = (int)($_POST['id'] ?? 0);
            $trangThaiMoi = $_POST['trang_thai'] ?? '';
            $cacTrangThaiHopLe = ['cho_xu_ly', 'da_tiep_nhan', 'dang_van_chuyen', 'da_giao', 'huy'];

            if ($id <= 0 || !in_array($trangThaiMoi, $cacTrangThaiHopLe, true)) {
                echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Dữ liệu không hợp lệ.']);
                break;
            }

            $pdo->prepare("UPDATE don_hang SET trang_thai = :tt WHERE id = :id")
                ->execute(['tt' => $trangThaiMoi, 'id' => $id]);

            $pdo->prepare("INSERT INTO lich_su_trang_thai (don_hang_id, trang_thai, ghi_chu) VALUES (:id, :tt, 'Cập nhật bởi quản trị viên')")
                ->execute(['id' => $id, 'tt' => $trangThaiMoi]);

            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã cập nhật trạng thái.']);
            break;

        // -----------------------------------------------------
        default:
            http_response_code(400);
            echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Hành động không hợp lệ.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
}

/**
 * Lấy & kiểm tra dữ liệu form vận đơn từ $_POST.
 */
function layDuLieuVanDon(array $post): array
{
    $nguoiDungId   = (int)($post['nguoi_dung_id'] ?? 0);
    $tenNguoiGui   = trim($post['ten_nguoi_gui'] ?? '');
    $diaChiGui     = trim($post['dia_chi_gui'] ?? '');
    $tenNguoiNhan  = trim($post['ten_nguoi_nhan'] ?? '');
    $sdtNguoiNhan  = trim($post['sdt_nguoi_nhan'] ?? '');
    $diaChiNhan    = trim($post['dia_chi_nhan'] ?? '');
    $loaiHang      = trim($post['loai_hang'] ?? '');
    $trongLuong    = (float)($post['trong_luong'] ?? 0);
    $phiVanChuyen  = (float)($post['phi_van_chuyen'] ?? 0);
    $trangThai     = $post['trang_thai'] ?? 'cho_xu_ly';
    $ngayDuKien    = $post['ngay_du_kien'] ?? null;

    if ($nguoiDungId <= 0 || $tenNguoiGui === '' || $diaChiGui === '' || $tenNguoiNhan === '' ||
        $sdtNguoiNhan === '' || $diaChiNhan === '') {
        return ['loi' => 'Vui lòng nhập đầy đủ các trường bắt buộc.', 'data' => null];
    }

    return [
        'loi' => null,
        'data' => [
            'nguoi_dung_id'  => $nguoiDungId,
            'ten_nguoi_gui'  => $tenNguoiGui,
            'dia_chi_gui'    => $diaChiGui,
            'ten_nguoi_nhan' => $tenNguoiNhan,
            'sdt_nguoi_nhan' => $sdtNguoiNhan,
            'dia_chi_nhan'   => $diaChiNhan,
            'loai_hang'      => $loaiHang,
            'trong_luong'    => $trongLuong,
            'phi_van_chuyen' => $phiVanChuyen,
            'trang_thai'     => $trangThai,
            'ngay_du_kien'   => $ngayDuKien ?: null,
        ],
    ];
}
