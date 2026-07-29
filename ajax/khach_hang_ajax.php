<?php
/**
 * AJAX: CRUD Quản lý tài khoản khách hàng (Admin).
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

        // -----------------------------------------------------
        case 'danh_sach':
            $tuKhoa = trim($_GET['q'] ?? '');

            $trang = max(1, (int)($_GET['trang'] ?? 1));
            $soDong = (int)($_GET['so_dong'] ?? 10);
            if ($soDong < 1 || $soDong > 100) $soDong = 10;

            $dieuKien = "";
            $params = [];

            if ($tuKhoa !== '') {
                $dieuKien .= " AND (n.ho_ten LIKE :tk1 OR n.username LIKE :tk2 OR n.sdt LIKE :tk3 OR n.email LIKE :tk4)";
                $params['tk1'] = "%$tuKhoa%";
                $params['tk2'] = "%$tuKhoa%";
                $params['tk3'] = "%$tuKhoa%";
                $params['tk4'] = "%$tuKhoa%";
            }

            $sqlDem = "SELECT COUNT(*) FROM nguoi_dung n WHERE n.vai_tro = 'khach_hang' $dieuKien";
            $stmt = $pdo->prepare($sqlDem);
            $stmt->execute($params);
            $tongSo = (int)$stmt->fetchColumn();

            $tongSoTrang = max(1, (int)ceil($tongSo / $soDong));
            if ($trang > $tongSoTrang) $trang = $tongSoTrang;
            $offset = ($trang - 1) * $soDong;

            $sql = "SELECT n.id, n.username, n.ho_ten, n.sdt, n.email, n.ngay_tao,
                           (SELECT COUNT(*) FROM don_hang d WHERE d.nguoi_dung_id = n.id) AS so_van_don
                    FROM nguoi_dung n
                    WHERE n.vai_tro = 'khach_hang' $dieuKien
                    ORDER BY n.ngay_tao DESC
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
        case 'them':
            $du = layDuLieuKhachHang($_POST, true);
            if ($du['loi']) { echo json_encode(['thanh_cong' => false, 'thong_bao' => $du['loi']]); break; }

            $trung = $pdo->prepare("SELECT id FROM nguoi_dung WHERE username = :u");
            $trung->execute(['u' => $du['data']['username']]);
            if ($trung->fetch()) {
                echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Tên tài khoản đã tồn tại, vui lòng chọn tên khác.']);
                break;
            }

            $stmt = $pdo->prepare("
                INSERT INTO nguoi_dung (username, mat_khau, ho_ten, sdt, email, vai_tro)
                VALUES (:username, :mat_khau, :ho_ten, :sdt, :email, 'khach_hang')
            ");
            $stmt->execute([
                'username' => $du['data']['username'],
                'mat_khau' => password_hash($du['data']['mat_khau'], PASSWORD_DEFAULT),
                'ho_ten'   => $du['data']['ho_ten'],
                'sdt'      => $du['data']['sdt'],
                'email'    => $du['data']['email'],
            ]);
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã tạo tài khoản khách hàng.']);
            break;

        // -----------------------------------------------------
        case 'sua':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Thiếu mã khách hàng.']); break; }

            $du = layDuLieuKhachHang($_POST, false);
            if ($du['loi']) { echo json_encode(['thanh_cong' => false, 'thong_bao' => $du['loi']]); break; }

            $trung = $pdo->prepare("SELECT id FROM nguoi_dung WHERE username = :u AND id != :id");
            $trung->execute(['u' => $du['data']['username'], 'id' => $id]);
            if ($trung->fetch()) {
                echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Tên tài khoản đã tồn tại, vui lòng chọn tên khác.']);
                break;
            }

            if (!empty($du['data']['mat_khau'])) {
                $stmt = $pdo->prepare("
                    UPDATE nguoi_dung SET username = :username, mat_khau = :mat_khau,
                           ho_ten = :ho_ten, sdt = :sdt, email = :email
                    WHERE id = :id AND vai_tro = 'khach_hang'
                ");
                $stmt->execute([
                    'username' => $du['data']['username'],
                    'mat_khau' => password_hash($du['data']['mat_khau'], PASSWORD_DEFAULT),
                    'ho_ten'   => $du['data']['ho_ten'],
                    'sdt'      => $du['data']['sdt'],
                    'email'    => $du['data']['email'],
                    'id'       => $id,
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE nguoi_dung SET username = :username,
                           ho_ten = :ho_ten, sdt = :sdt, email = :email
                    WHERE id = :id AND vai_tro = 'khach_hang'
                ");
                $stmt->execute([
                    'username' => $du['data']['username'],
                    'ho_ten'   => $du['data']['ho_ten'],
                    'sdt'      => $du['data']['sdt'],
                    'email'    => $du['data']['email'],
                    'id'       => $id,
                ]);
            }
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã cập nhật tài khoản khách hàng.']);
            break;

        // -----------------------------------------------------
        case 'xoa':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['thanh_cong' => false, 'thong_bao' => 'Thiếu mã khách hàng.']); break; }

            $pdo->prepare("DELETE FROM nguoi_dung WHERE id = :id AND vai_tro = 'khach_hang'")->execute(['id' => $id]);
            echo json_encode(['thanh_cong' => true, 'thong_bao' => 'Đã xóa tài khoản khách hàng (và toàn bộ vận đơn liên quan).']);
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
 * Lấy & kiểm tra dữ liệu form khách hàng từ $_POST.
 * $batBuocMatKhau = true khi tạo mới (bắt buộc nhập mật khẩu),
 * false khi sửa (để trống = giữ nguyên mật khẩu cũ).
 */
function layDuLieuKhachHang(array $post, bool $batBuocMatKhau): array
{
    $username = trim($post['username'] ?? '');
    $hoTen    = trim($post['ho_ten'] ?? '');
    $sdt      = trim($post['sdt'] ?? '');
    $email    = trim($post['email'] ?? '');
    $matKhau  = $post['mat_khau'] ?? '';

    if ($username === '' || $hoTen === '' || $sdt === '') {
        return ['loi' => 'Vui lòng nhập đầy đủ tài khoản, họ tên và số điện thoại.', 'data' => null];
    }
    if ($batBuocMatKhau && strlen($matKhau) < 6) {
        return ['loi' => 'Mật khẩu phải có ít nhất 6 ký tự.', 'data' => null];
    }
    if (!$batBuocMatKhau && $matKhau !== '' && strlen($matKhau) < 6) {
        return ['loi' => 'Mật khẩu mới phải có ít nhất 6 ký tự.', 'data' => null];
    }

    return [
        'loi' => null,
        'data' => [
            'username' => $username,
            'ho_ten'   => $hoTen,
            'sdt'      => $sdt,
            'email'    => $email ?: null,
            'mat_khau' => $matKhau,
        ],
    ];
}
