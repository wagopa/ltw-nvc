<?php
/**
 * Cấu hình kết nối cơ sở dữ liệu
 * Sử dụng PDO để kết nối MySQL thông qua XAMPP
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'nha_van_chuyen');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP mặc định không có mật khẩu cho user root
define('DB_CHARSET', 'utf8mb4');

function ketNoiDatabase()
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage());
        }
    }

    return $pdo;
}
