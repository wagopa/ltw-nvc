<?php
/**
 * AJAX: CRUD Quản lý phương tiện (Admin).
 * action = danh_sach | kho | them | sua | xoa
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
                $dieuKien .= " AND (p.bien_so LIKE :tk1 OR p.ten_tai_xe LIKE :tk2 OR p.sdt_tai_xe LIKE :tk3)";
                $params['tk1'] = "%$tuKhoa%";
                $params['tk2'] = "%$tuKhoa%";
                $params['tk3'] = "%$tuKhoa%";
            }

            $sqlDem = "SELECT COUNT(*) FROM phuong_tien p WHERE 1=1 $dieuKien";
            $stmt = $pdo->prepare($sqlDem);
            $stmt->execute($params);
            $tongSo = (int)$stmt->fetchColumn();

            $tongSoTrang = max(1, (int)ceil($tongSo / $soDong));
            if ($trang > $tongSoTrang) $trang = $tongSoTrang;
            $offset = ($trang - 1) * $soDong;

            $sql = "SELECT p.*, k.ten_kho
                    FROM phuong_tien p
                    LEFT JOIN kho_trung_chuyen k ON k.id = p.kho_id
                    WHERE 1=1 $dieuKien
                    ORDER BY p.ngay_tao DESC
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

        case 'kho':
            $stmt = $pdo->query("SELECT id, ten_kho FROM kho_trung_chuyen ORDER BY ten_kho");
            echo json_encode(['thanh_cong' => true, 'du_lieu' => $stmt->fetchAll()]);
            break;

        case 'them':
            $du = layDuLieuPhuongTien($_POST);
            if ($du['loi']) { echo json_encode(['thanh_cong' => false, 'thong_bao' => $du['loi']]); break; }

            $trung = $pdo->prepare("SELECT id FROM phuong_tien WHERE bien_so = :bs");
            $trung->execute(['bs' => $du['data']['bien_so']]);
            if ($trung->fetch()) {
                echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Biển số này đã tồn tại trong hệ thống.']);
                break;
            }

            $stmt = $pdo->prepare("
                INSERT INTO phuong_tien (bien_so, loai_xe, ten_tai_xe, sdt_tai_xe, trang_thai, kho_id, ghi_chu)
                VALUES (:bien_so, :loai_xe, :ten_tai_xe, :sdt_tai_xe, :trang_thai, :kho_id, :ghi_chu)
            ");
            $stmt->execute($du['data']);
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã thêm phương tiện.']);
            break;

        case 'sua':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Thiếu mã phương tiện.']); break; }

            $du = layDuLieuPhuongTien($_POST);
            if ($du['loi']) { echo json_encode(['thanh_cong' => false, 'thong_bao' => $du['loi']]); break; }

            $trung = $pdo->prepare("SELECT id FROM phuong_tien WHERE bien_so = :bs AND id != :id");
            $trung->execute(['bs' => $du['data']['bien_so'], 'id' => $id]);
            if ($trung->fetch()) {
                echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Biển số này đã tồn tại trong hệ thống.']);
                break;
            }

            $stmt = $pdo->prepare("
                UPDATE phuong_tien SET
                    bien_so = :bien_so, loai_xe = :loai_xe, ten_tai_xe = :ten_tai_xe,
                    sdt_tai_xe = :sdt_tai_xe, trang_thai = :trang_thai, kho_id = :kho_id, ghi_chu = :ghi_chu
                WHERE id = :id
            ");
            $stmt->execute(array_merge($du['data'], ['id' => $id]));
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã cập nhật phương tiện.']);
            break;

        case 'xoa':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Thiếu mã phương tiện.']); break; }

            $pdo->prepare("DELETE FROM phuong_tien WHERE id = :id")->execute(['id' => $id]);
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã xóa phương tiện.']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Hành động không hợp lệ.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
}

function layDuLieuPhuongTien(array $post): array
{
    $bienSo = trim($post['bien_so'] ?? '');
    $loaiXe = $post['loai_xe'] ?? '';
    $tenTaiXe = trim($post['ten_tai_xe'] ?? '');
    $sdtTaiXe = trim($post['sdt_tai_xe'] ?? '');
    $trangThai = $post['trang_thai'] ?? 'hoat_dong';
    $khoId = trim($post['kho_id'] ?? '');
    $ghiChu = trim($post['ghi_chu'] ?? '');

    $loaiXeHopLe = ['xe_may', 'xe_tai_nho', 'xe_tai_lon'];
    if ($bienSo === '' || $tenTaiXe === '' || $sdtTaiXe === '' || !in_array($loaiXe, $loaiXeHopLe, true)) {
        return ['loi' => 'Vui lòng nhập đầy đủ biển số, loại xe, tên tài xế và SĐT.', 'data' => null];
    }

    return [
        'loi' => null,
        'data' => [
            'bien_so' => $bienSo,
            'loai_xe' => $loaiXe,
            'ten_tai_xe' => $tenTaiXe,
            'sdt_tai_xe' => $sdtTaiXe,
            'trang_thai' => $trangThai,
            'kho_id' => $khoId !== '' ? (int)$khoId : null,
            'ghi_chu' => $ghiChu ?: null,
        ],
    ];
}
