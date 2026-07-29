<?php
/**
 * HEADER dùng chung cho toàn bộ website.
 * Biến tùy chọn có thể set TRƯỚC KHI include file này:
 *   $trangHienTai  (string) - key trang đang active, vd: 'giao-hang'
 *   $nguoiDung     (array)  - ['ho_ten' => ..., 'vai_tro' => 'admin'|'khach_hang'] nếu đã đăng nhập
 *   $urlGoc        (string) - đường dẫn tương đối về thư mục gốc website.
 *                             '' nếu file gọi nằm ở gốc (vd: index.php), '../' nếu nằm trong pages/
 */
if (!isset($trangHienTai)) $trangHienTai = '';
if (!isset($urlGoc)) $urlGoc = '';
$dangNhap = isset($nguoiDung) && !empty($nguoiDung);
require_once __DIR__ . '/helpers.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($tieuDeTrang) ? htmlspecialchars($tieuDeTrang) . ' - ' : '' ?>Nhà Vận Chuyển</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Be+Vietnam+Pro:wght@400;500;600;700&family=Roboto+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= $urlGoc ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Topbar liên hệ nhanh -->
<div class="nvc-topbar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <i class="bi bi-telephone-fill"></i> Hotline: 1900 6868
            <span class="divider d-none d-sm-inline"></span>
            <span class="d-none d-sm-inline"><i class="bi bi-envelope-fill"></i> cskh@nhavanchuyen.vn</span>
        </div>
        <div>
            <i class="bi bi-clock-fill"></i> Hoạt động 24/7 tất cả các ngày trong tuần
        </div>
    </div>
</div>

<!-- Navbar chính -->
<header class="nvc-navbar">
    <nav class="navbar navbar-expand-lg navbar-dark py-0">
        <div class="container align-items-center">

            <a class="navbar-brand nvc-brand" href="<?= $urlGoc ?>index.php">
                <span class="nvc-brand-icon">
                    <i class="bi bi-truck text-white fs-5"></i>
                </span>
                <span class="nvc-brand-text">
                    Nhà Vận Chuyển
                    <span>Giao hàng nhanh &middot; Uy tín &middot; Toàn quốc</span>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nvcNavMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="nvcNavMenu">
                <ul class="navbar-nav nvc-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $trangHienTai === 'trang-chu' ? 'active' : '' ?>" href="<?= $urlGoc ?>index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $trangHienTai === 'giao-hang' ? 'active' : '' ?>" href="<?= $urlGoc ?>pages/thong_tin_giao_hang.php">Thông tin giao hàng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $trangHienTai === 'dich-vu' ? 'active' : '' ?>" href="<?= $urlGoc ?>index.php#dich-vu">Dịch vụ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $trangHienTai === 'lien-he' ? 'active' : '' ?>" href="<?= $urlGoc ?>index.php#lien-he">Liên hệ</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 py-2 py-lg-0">
                    <?php if ($dangNhap): ?>
                        <div class="dropdown">
                            <button class="nvc-user-chip dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <span class="nvc-avatar"><?= htmlspecialchars(kyTuDauTien($nguoiDung['ho_ten'])) ?></span>
                                <span class="text-start">
                                    <span class="nvc-user-name d-block"><?= htmlspecialchars($nguoiDung['ho_ten']) ?></span>
                                    <span class="nvc-user-role"><?= $nguoiDung['vai_tro'] === 'admin' ? 'Quản trị viên' : 'Khách hàng' ?></span>
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= $urlGoc ?>pages/thong_tin_giao_hang.php"><i class="bi bi-box-seam me-2"></i>Đơn hàng của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= $urlGoc ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= $urlGoc ?>login.php" class="btn btn-sm" style="background:var(--accent);color:#fff;">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </nav>
</header>

<main>
