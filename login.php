<?php
/**
 * TRANG ĐĂNG NHẬP
 * Xác thực người dùng qua bảng nguoi_dung, điều hướng theo vai_tro.
 */
session_start();
require_once __DIR__ . '/config/database.php';

// Nếu đã đăng nhập rồi thì chuyển thẳng đi, khỏi hiện lại form
if (isset($_SESSION['nguoi_dung_id'])) {
    header('Location: ' . ($_SESSION['vai_tro'] === 'khach_hang' ? 'index.php' : 'pages/dashboard.php'));
    exit;
}

$loi = '';
$usernameCu = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $matKhau    = $_POST['mat_khau'] ?? '';
    $usernameCu = $username;

    if ($username === '' || $matKhau === '') {
        $loi = 'Vui lòng nhập đầy đủ tài khoản và mật khẩu.';
    } else {
        $pdo = ketNoiDatabase();
        $stmt = $pdo->prepare('SELECT * FROM nguoi_dung WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($matKhau, $user['mat_khau'])) {
            $_SESSION['nguoi_dung_id'] = $user['id'];
            $_SESSION['ho_ten']        = $user['ho_ten'];
            $_SESSION['vai_tro']       = $user['vai_tro'];

            header('Location: ' . ($user['vai_tro'] === 'khach_hang' ? 'index.php' : 'pages/dashboard.php'));
            exit;
        } else {
            $loi = 'Sai tài khoản hoặc mật khẩu. Vui lòng thử lại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đăng nhập - Nhà Vận Chuyển</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Be+Vietnam+Pro:wght@400;500;600;700&family=Roboto+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="nvc-login-screen">

    <!-- Cột trái: thương hiệu -->
    <div class="nvc-login-brand">
        <div class="nvc-login-brand-top nvc-brand">
            <span class="nvc-brand-icon"><i class="bi bi-truck text-white fs-5"></i></span>
            <span class="nvc-brand-text">
                Nhà Vận Chuyển
                <span>Giao hàng nhanh &middot; Uy tín &middot; Toàn quốc</span>
            </span>
        </div>

        <div class="nvc-login-headline">
            <h2>Theo dõi hành trình<br>đơn hàng của bạn,<br><span style="color:var(--accent)">mọi lúc mọi nơi.</span></h2>
            <p>Đăng nhập để xem trạng thái vận chuyển, lịch sử giao nhận và toàn bộ vận đơn của bạn theo thời gian thực.</p>
        </div>

        <ul class="nvc-login-points">
            <li>
                <span class="ic"><i class="bi bi-shield-check"></i></span>
                <span><b>An toàn</b>Thông tin đơn hàng được bảo mật tuyệt đối.</span>
            </li>
            <li>
                <span class="ic"><i class="bi bi-clock-history"></i></span>
                <span><b>Cập nhật liên tục</b>Trạng thái vận chuyển theo thời gian thực.</span>
            </li>
            <li>
                <span class="ic"><i class="bi bi-geo-alt"></i></span>
                <span><b>Minh bạch</b>Xem đầy đủ lịch sử từng chặng đường của hàng hóa.</span>
            </li>
        </ul>

        <div class="nvc-login-brand-bottom">
            &copy; <?= date('Y') ?> Nhà Vận Chuyển. Dự án thực hành PHP thuần.
        </div>
    </div>

    <!-- Cột phải: form đăng nhập -->
    <div class="nvc-login-form-col">
        <div class="nvc-login-card">

            <div class="nvc-login-card-head">
                <div class="tag">Cổng đăng nhập</div>
                <h1><i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập hệ thống</h1>
            </div>
            <div class="nvc-waybill-perforation small"></div>

            <div class="nvc-login-card-body">

                <?php if ($loi): ?>
                <div class="nvc-login-alert">
                    <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                    <span><?= htmlspecialchars($loi) ?></span>
                </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <label class="nvc-form-label">Tài khoản</label>
                    <div class="nvc-input-group">
                        <i class="bi bi-person"></i>
                        <input type="text" name="username" placeholder="Nhập tên tài khoản"
                               value="<?= htmlspecialchars($usernameCu) ?>" autofocus>
                    </div>

                    <label class="nvc-form-label">Mật khẩu</label>
                    <div class="nvc-input-group">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="mat_khau" id="nvcMatKhau" placeholder="Nhập mật khẩu">
                        <button type="button" class="nvc-toggle-pass" id="nvcTogglePass" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>

                    <button type="submit" class="nvc-btn-login">Đăng nhập</button>
                </form>

                <a href="index.php" class="nvc-login-back"><i class="bi bi-arrow-left"></i> Quay lại trang chủ</a>
            </div>
        </div>
    </div>

</div>

<script>
document.getElementById('nvcTogglePass').addEventListener('click', function () {
    const input = document.getElementById('nvcMatKhau');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>
</body>
</html>
