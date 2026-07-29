<?php
/**
 * Kiểm tra người dùng đã đăng nhập chưa.
 * Include ở đầu các trang cần đăng nhập (trước khi có output HTML).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['nguoi_dung_id'])) {
    header('Location: ../login.php');
    exit;
}

function nguoiDungHienTai()
{
    return [
        'id'      => $_SESSION['nguoi_dung_id'] ?? null,
        'ho_ten'  => $_SESSION['ho_ten'] ?? '',
        'vai_tro' => $_SESSION['vai_tro'] ?? '',
    ];
}
