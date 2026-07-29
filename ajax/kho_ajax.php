<?php
/**
 * AJAX: CRUD Quản lý kho trung chuyển (Admin).
 * action = danh_sach | them | sua | xoa
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

        case 'danh_sach':
            $tuKhoa = trim($_GET['q'] ?? '');

            $trang = max(1, (int)($_GET['trang'] ?? 1));
            $soDong = (int)($_GET['so_dong'] ?? 10);
            if ($soDong < 1 || $soDong > 100) $soDong = 10;

            $dieuKien = "";
            $params = [];
            if ($tuKhoa !== '') {
                $dieuKien .= " AND (k.ten_kho LIKE :tk1 OR k.dia_chi LIKE :tk2 OR k.nguoi_quan_ly LIKE :tk3)";
                $params['tk1'] = "%$tuKhoa%";
                $params['tk2'] = "%$tuKhoa%";
                $params['tk3'] = "%$tuKhoa%";
            }

            $sqlDem = "SELECT COUNT(*) FROM kho_trung_chuyen k WHERE 1=1 $dieuKien";
            $stmt = $pdo->prepare($sqlDem);
            $stmt->execute($params);
            $tongSo = (int)$stmt->fetchColumn();

            $tongSoTrang = max(1, (int)ceil($tongSo / $soDong));
            if ($trang > $tongSoTrang) $trang = $tongSoTrang;
            $offset = ($trang - 1) * $soDong;

            $sql = "SELECT k.*,
                           (SELECT COUNT(*) FROM phuong_tien p WHERE p.kho_id = k.id) AS so_phuong_tien
                    FROM kho_trung_chuyen k WHERE 1=1 $dieuKien
                    ORDER BY k.ngay_tao DESC
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

        case 'them':
            $du = layDuLieuKho($_POST);
            if ($du['loi']) { echo json_encode(['thanh_cong' => false, 'thong_bao' => $du['loi']]); break; }

            $stmt = $pdo->prepare("
                INSERT INTO kho_trung_chuyen (ten_kho, khu_vuc, dia_chi, nguoi_quan_ly, sdt_quan_ly, trang_thai)
                VALUES (:ten_kho, :khu_vuc, :dia_chi, :nguoi_quan_ly, :sdt_quan_ly, :trang_thai)
            ");
            $stmt->execute($du['data']);
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã thêm kho trung chuyển.']);
            break;

        case 'sua':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Thiếu mã kho.']); break; }

            $du = layDuLieuKho($_POST);
            if ($du['loi']) { echo json_encode(['thanh_cong' => false, 'thong_bao' => $du['loi']]); break; }

            $stmt = $pdo->prepare("
                UPDATE kho_trung_chuyen SET
                    ten_kho = :ten_kho, khu_vuc = :khu_vuc, dia_chi = :dia_chi,
                    nguoi_quan_ly = :nguoi_quan_ly, sdt_quan_ly = :sdt_quan_ly, trang_thai = :trang_thai
                WHERE id = :id
            ");
            $stmt->execute(array_merge($du['data'], ['id' => $id]));
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã cập nhật kho trung chuyển.']);
            break;

        case 'xoa':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Thiếu mã kho.']); break; }

            $pdo->prepare("DELETE FROM kho_trung_chuyen WHERE id = :id")->execute(['id' => $id]);
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã xóa kho trung chuyển.']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Hành động không hợp lệ.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
}

function layDuLieuKho(array $post): array
{
    $tenKho = trim($post['ten_kho'] ?? '');
    $khuVuc = $post['khu_vuc'] ?? '';
    $diaChi = trim($post['dia_chi'] ?? '');
    $nguoiQuanLy = trim($post['nguoi_quan_ly'] ?? '');
    $sdtQuanLy = trim($post['sdt_quan_ly'] ?? '');
    $trangThai = $post['trang_thai'] ?? 'hoat_dong';

    $khuVucHopLe = ['mien_bac', 'mien_trung', 'mien_nam'];
    if ($tenKho === '' || $diaChi === '' || !in_array($khuVuc, $khuVucHopLe, true)) {
        return ['loi' => 'Vui lòng nhập đầy đủ tên kho, khu vực và địa chỉ.', 'data' => null];
    }

    return [
        'loi' => null,
        'data' => [
            'ten_kho' => $tenKho,
            'khu_vuc' => $khuVuc,
            'dia_chi' => $diaChi,
            'nguoi_quan_ly' => $nguoiQuanLy ?: null,
            'sdt_quan_ly' => $sdtQuanLy ?: null,
            'trang_thai' => $trangThai,
        ],
    ];
}
