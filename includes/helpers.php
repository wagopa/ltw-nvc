<?php
/**
 * Các hàm tiện ích dùng chung cho toàn bộ website.
 */

/**
 * Lấy ký tự đầu tiên của tên (an toàn với UTF-8/tiếng Việt).
 * Dùng mb_substr nếu có extension mbstring, nếu không thì rơi về cách thủ công
 * để tránh làm hỏng ký tự tiếng Việt (vốn chiếm nhiều byte trong UTF-8).
 */
function kyTuDauTien(string $chuoi): string
{
    $chuoi = trim($chuoi);
    if ($chuoi === '') return '?';

    if (function_exists('mb_substr')) {
        return mb_strtoupper(mb_substr($chuoi, 0, 1));
    }

    // Fallback không cần mbstring: lấy đủ số byte của 1 ký tự UTF-8 đầu tiên
    $byteDau = ord($chuoi[0]);
    $soByte = 1;
    if ($byteDau >= 0xF0) $soByte = 4;
    elseif ($byteDau >= 0xE0) $soByte = 3;
    elseif ($byteDau >= 0xC0) $soByte = 2;

    return strtoupper(substr($chuoi, 0, $soByte));
}
