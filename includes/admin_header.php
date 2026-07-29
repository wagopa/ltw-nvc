<?php
/**
 * LAYOUT HEADER cho khu vực Admin (sidebar + topbar).
 * Biến cần set trước khi include:
 *   $trangAdminHienTai (string) - key trang đang active, vd: 'van-don'
 *   $tieuDeTrangAdmin   (string) - tiêu đề hiển thị trên topbar
 *   $nguoiDung          (array)  - thông tin admin đang đăng nhập
 */
if (!isset($trangAdminHienTai)) $trangAdminHienTai = '';
require_once __DIR__ . '/helpers.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($tieuDeTrangAdmin) ? htmlspecialchars($tieuDeTrangAdmin) . ' - ' : '' ?>Quản trị - Nhà Vận Chuyển</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Be+Vietnam+Pro:wght@400;500;600;700&family=Roboto+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="nvc-admin-body">

    <!-- Sidebar -->
    <aside class="nvc-admin-sidebar">
        <a href="../index.php" class="nvc-brand text-decoration-none">
            <span class="nvc-brand-icon"><i class="bi bi-truck text-white fs-6"></i></span>
            <span class="nvc-brand-text">Nhà Vận Chuyển<span>Khu vực quản trị</span></span>
        </a>

        <ul class="nvc-admin-nav">
            <li class="eyebrow">Vận chuyển</li>
            <li>
                <a href="van_don.php" class="<?= $trangAdminHienTai === 'van-don' ? 'active' : '' ?>">
                    <i class="bi bi-box-seam"></i> Quản lý vận đơn
                </a>
            </li>
            <li>
                <a href="phuong_tien.php" class="<?= $trangAdminHienTai === 'phuong-tien' ? 'active' : '' ?>">
                    <i class="bi bi-truck"></i> Phương tiện
                </a>
            </li>
            <li>
                <a href="kho.php" class="<?= $trangAdminHienTai === 'kho' ? 'active' : '' ?>">
                    <i class="bi bi-building"></i> Kho trung chuyển
                </a>
            </li>
            <li>
                <a href="tuyen_duong.php" class="<?= $trangAdminHienTai === 'tuyen-duong' ? 'active' : '' ?>">
                    <i class="bi bi-signpost-split"></i> Tuyến đường
                </a>
            </li>

            <li class="eyebrow">Tài khoản</li>
            <li>
                <a href="khach_hang.php" class="<?= $trangAdminHienTai === 'khach-hang' ? 'active' : '' ?>">
                    <i class="bi bi-people"></i> Khách hàng
                </a>
            </li>

            <li class="eyebrow">Báo cáo</li>
            <li>
                <a href="dashboard.php" class="<?= $trangAdminHienTai === 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-bar-chart"></i> Dashboard
                </a>
            </li>
        </ul>
    </aside>

    <!-- Nội dung chính -->
    <div class="nvc-admin-main">
        <div class="nvc-admin-topbar">
            <h1><?= isset($tieuDeTrangAdmin) ? htmlspecialchars($tieuDeTrangAdmin) : '' ?></h1>
            <div class="dropdown">
                <button class="nvc-user-chip dropdown-toggle" type="button" data-bs-toggle="dropdown" style="background:var(--navy-800);">
                    <span class="nvc-avatar"><?= htmlspecialchars(kyTuDauTien($nguoiDung['ho_ten'])) ?></span>
                    <span class="text-start">
                        <span class="nvc-user-name d-block"><?= htmlspecialchars($nguoiDung['ho_ten']) ?></span>
                        <span class="nvc-user-role">Quản trị viên</span>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                </ul>
            </div>
        </div>
        <div class="nvc-admin-content">
