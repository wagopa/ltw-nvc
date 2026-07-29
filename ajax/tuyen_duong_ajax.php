<?php
/**
 * AJAX: CRUD Quản lý tuyến đường vận chuyển (Admin).
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
                $dieuKien .= " AND (kd.ten_kho LIKE :tk1 OR kc.ten_kho LIKE :tk2)";
                $params['tk1'] = "%$tuKhoa%";
                $params['tk2'] = "%$tuKhoa%";
            }

            $sqlDem = "SELECT COUNT(*)
                       FROM tuyen_duong t
                       JOIN kho_trung_chuyen kd ON kd.id = t.kho_di_id
                       JOIN kho_trung_chuyen kc ON kc.id = t.kho_den_id
                       WHERE 1=1 $dieuKien";
            $stmt = $pdo->prepare($sqlDem);
            $stmt->execute($params);
            $tongSo = (int)$stmt->fetchColumn();

            $tongSoTrang = max(1, (int)ceil($tongSo / $soDong));
            if ($trang > $tongSoTrang) $trang = $tongSoTrang;
            $offset = ($trang - 1) * $soDong;

            $sql = "SELECT t.*, kd.ten_kho AS ten_kho_di, kc.ten_kho AS ten_kho_den
                    FROM tuyen_duong t
                    JOIN kho_trung_chuyen kd ON kd.id = t.kho_di_id
                    JOIN kho_trung_chuyen kc ON kc.id = t.kho_den_id
                    WHERE 1=1 $dieuKien
                    ORDER BY t.ngay_tao DESC
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
            $du = layDuLieuTuyenDuong($_POST);
            if ($du['loi']) { echo json_encode(['thanh_cong' => false, 'thong_bao' => $du['loi']]); break; }

            $trung = kiemTraTrungTuyen($pdo, $du['data']['kho_di_id'], $du['data']['kho_den_id']);
            if ($trung) { echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Tuyến đường giữa 2 kho này đã tồn tại.']); break; }

            $stmt = $pdo->prepare("
                INSERT INTO tuyen_duong (kho_di_id, kho_den_id, khoang_cach_km, thoi_gian_du_kien_gio, trang_thai, ghi_chu)
                VALUES (:kho_di_id, :kho_den_id, :khoang_cach_km, :thoi_gian_du_kien_gio, :trang_thai, :ghi_chu)
            ");
            $stmt->execute($du['data']);
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã thêm tuyến đường.']);
            break;

        case 'sua':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Thiếu mã tuyến đường.']); break; }

            $du = layDuLieuTuyenDuong($_POST);
            if ($du['loi']) { echo json_encode(['thanh_cong' => false, 'thong_bao' => $du['loi']]); break; }

            $trung = kiemTraTrungTuyen($pdo, $du['data']['kho_di_id'], $du['data']['kho_den_id'], $id);
            if ($trung) { echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Tuyến đường giữa 2 kho này đã tồn tại.']); break; }

            $stmt = $pdo->prepare("
                UPDATE tuyen_duong SET
                    kho_di_id = :kho_di_id, kho_den_id = :kho_den_id,
                    khoang_cach_km = :khoang_cach_km, thoi_gian_du_kien_gio = :thoi_gian_du_kien_gio,
                    trang_thai = :trang_thai, ghi_chu = :ghi_chu
                WHERE id = :id
            ");
            $stmt->execute(array_merge($du['data'], ['id' => $id]));
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã cập nhật tuyến đường.']);
            break;

        case 'xoa':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Thiếu mã tuyến đường.']); break; }

            $pdo->prepare("DELETE FROM tuyen_duong WHERE id = :id")->execute(['id' => $id]);
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã xóa tuyến đường.']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Hành động không hợp lệ.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
}

function layDuLieuTuyenDuong(array $post): array
{
    $khoDiId = (int)($post['kho_di_id'] ?? 0);
    $khoDenId = (int)($post['kho_den_id'] ?? 0);
    $khoangCach = $post['khoang_cach_km'] ?? '';
    $thoiGian = $post['thoi_gian_du_kien_gio'] ?? '';
    $trangThai = $post['trang_thai'] ?? 'hoat_dong';
    $ghiChu = trim($post['ghi_chu'] ?? '');

    if ($khoDiId <= 0 || $khoDenId <= 0) {
        return ['loi' => 'Vui lòng chọn đầy đủ kho đi và kho đến.', 'data' => null];
    }
    if ($khoDiId === $khoDenId) {
        return ['loi' => 'Kho đi và kho đến không được trùng nhau.', 'data' => null];
    }

    return [
        'loi' => null,
        'data' => [
            'kho_di_id' => $khoDiId,
            'kho_den_id' => $khoDenId,
            'khoang_cach_km' => $khoangCach !== '' ? (float)$khoangCach : null,
            'thoi_gian_du_kien_gio' => $thoiGian !== '' ? (float)$thoiGian : null,
            'trang_thai' => $trangThai,
            'ghi_chu' => $ghiChu ?: null,
        ],
    ];
}

/**
 * Kiểm tra tuyến đi/đến đã tồn tại chưa (bỏ qua chính bản ghi đang sửa nếu có $boQuaId).
 */
function kiemTraTrungTuyen(PDO $pdo, int $khoDiId, int $khoDenId, ?int $boQuaId = null): bool
{
    $sql = "SELECT id FROM tuyen_duong WHERE kho_di_id = :di AND kho_den_id = :den";
    $params = ['di' => $khoDiId, 'den' => $khoDenId];
    if ($boQuaId !== null) {
        $sql .= " AND id != :id";
        $params['id'] = $boQuaId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (bool)$stmt->fetch();
}
