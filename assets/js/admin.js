/**
 * admin.js — Các hàm JS dùng chung cho toàn bộ khu vực Admin.
 * Được nhúng ở includes/admin_footer.php, load SAU Bootstrap và TRƯỚC script riêng của từng trang,
 * nên các hàm ở đây có thể gọi trực tiếp trong script riêng của mỗi trang pages/*.php.
 */

/**
 * Dựng điều khiển phân trang (nút số trang, tiến/lùi, tổng số bản ghi) vào phần tử #nvcPhanTrang.
 * Dùng chung cho mọi bảng danh sách ở Admin (vận đơn, khách hàng, phương tiện, kho, tuyến đường).
 *
 * @param {object} pt - Object phân trang do backend trả về:
 *                      { trang_hien_tai, tong_so_trang, tong_so_don }
 * @param {function} hamTai - Hàm sẽ được gọi lại khi người dùng bấm chuyển trang,
 *                            nhận vào 1 tham số là số trang cần tải (number).
 */
function renderPhanTrang(pt, hamTai) {
    const el = document.getElementById('nvcPhanTrang');
    if (!el) return;

    if (pt.tong_so_trang <= 1) {
        el.innerHTML = pt.tong_so_don > 0
            ? `<div class="nvc-pagination"><span class="nvc-page-info">Tổng ${pt.tong_so_don} bản ghi</span></div>`
            : '';
        return;
    }

    let nutHtml = '';
    for (let i = 1; i <= pt.tong_so_trang; i++) {
        nutHtml += `<button type="button" class="nvc-page-btn ${i === pt.trang_hien_tai ? 'active' : ''}" data-trang="${i}">${i}</button>`;
    }

    el.innerHTML = `
        <div class="nvc-pagination">
            <button type="button" class="nvc-page-nav" data-trang="${pt.trang_hien_tai - 1}" ${pt.trang_hien_tai <= 1 ? 'disabled' : ''}>
                <i class="bi bi-chevron-left"></i>
            </button>
            ${nutHtml}
            <button type="button" class="nvc-page-nav" data-trang="${pt.trang_hien_tai + 1}" ${pt.trang_hien_tai >= pt.tong_so_trang ? 'disabled' : ''}>
                <i class="bi bi-chevron-right"></i>
            </button>
            <span class="nvc-page-info">Tổng ${pt.tong_so_don} bản ghi</span>
        </div>
    `;
    el.querySelectorAll('[data-trang]').forEach(btn => {
        btn.addEventListener('click', () => hamTai(parseInt(btn.dataset.trang, 10)));
    });
}
