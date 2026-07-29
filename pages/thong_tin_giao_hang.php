<?php
require_once __DIR__ . '/../includes/auth_check.php';

$nguoiDung = nguoiDungHienTai();

// Trang này chỉ dành cho khách hàng; admin sẽ có khu vực quản trị riêng (chưa xây dựng)
if ($nguoiDung['vai_tro'] !== 'khach_hang') {
    echo "Trang này chỉ dành cho tài khoản khách hàng.";
    exit;
}

$trangHienTai = 'giao-hang';
$tieuDeTrang = 'Thông tin giao hàng';
$urlGoc = '../';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="nvc-page-header">
    <div class="container">
        <h1><i class="bi bi-box-seam-fill me-2"></i>Thông tin giao hàng</h1>
        <p>Xin chào <?= htmlspecialchars($nguoiDung['ho_ten']) ?>, đây là toàn bộ đơn hàng và trạng thái vận chuyển của bạn.</p>
    </div>
</div>

<div class="container nvc-shipments-wrap" id="nvcShipmentsWrap">

    <!-- Thẻ tóm tắt nhanh -->
    <div class="row g-3 mb-4" id="nvcThongKe">
        <div class="col-6 col-lg-3">
            <div class="nvc-summary-card">
                <div class="num" id="tkTongSo">--</div>
                <div class="label">Tổng số đơn</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="nvc-summary-card">
                <div class="num" style="color:var(--warning)" id="tkChoXuLy">--</div>
                <div class="label">Chờ xử lý</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="nvc-summary-card">
                <div class="num" style="color:var(--info)" id="tkDangVanChuyen">--</div>
                <div class="label">Đang vận chuyển</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="nvc-summary-card">
                <div class="num" style="color:var(--success)" id="tkDaGiao">--</div>
                <div class="label">Đã giao thành công</div>
            </div>
        </div>
    </div>

    <!-- Bộ lọc + tìm kiếm -->
    <div class="nvc-filter-bar">
        <div class="nvc-search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="nvcTimMaVanDon" placeholder="Tìm theo mã vận đơn...">
        </div>
        <select class="form-select" id="nvcLocTrangThai">
            <option value="">Tất cả trạng thái</option>
            <option value="cho_xu_ly">Chờ xử lý</option>
            <option value="da_tiep_nhan">Đã tiếp nhận</option>
            <option value="dang_van_chuyen">Đang vận chuyển</option>
            <option value="da_giao">Đã giao</option>
            <option value="huy">Đã hủy</option>
        </select>
    </div>

    <!-- Danh sách vận đơn -->
    <div id="nvcDanhSachDon">
        <!-- Skeleton loading mặc định -->
        <div class="nvc-skeleton">
            <div class="bar" style="width:40%"></div>
            <div class="bar" style="width:70%"></div>
            <div class="bar" style="width:55%"></div>
        </div>
        <div class="nvc-skeleton">
            <div class="bar" style="width:35%"></div>
            <div class="bar" style="width:65%"></div>
            <div class="bar" style="width:50%"></div>
        </div>
    </div>

    <!-- Phân trang -->
    <div id="nvcPhanTrang"></div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
const NHAN_TRANG_THAI = {
    cho_xu_ly:        'Chờ xử lý',
    da_tiep_nhan:      'Đã tiếp nhận',
    dang_van_chuyen:   'Đang vận chuyển',
    da_giao:           'Đã giao',
    huy:               'Đã hủy'
};
const ICON_TRANG_THAI = {
    cho_xu_ly: 'bi-hourglass-split',
    da_tiep_nhan: 'bi-box-seam',
    dang_van_chuyen: 'bi-truck',
    da_giao: 'bi-check-circle-fill',
    huy: 'bi-x-circle-fill'
};
// Thứ tự các bước trong hành trình bình thường (không tính "huy" — đơn hủy hiển thị banner riêng)
const CAC_BUOC = ['cho_xu_ly', 'da_tiep_nhan', 'dang_van_chuyen', 'da_giao'];
const NHAN_BUOC_NGAN = {
    cho_xu_ly: 'Chờ xử lý',
    da_tiep_nhan: 'Tiếp nhận',
    dang_van_chuyen: 'Vận chuyển',
    da_giao: 'Giao hàng'
};

function dinhDangTien(so) {
    return Number(so).toLocaleString('vi-VN') + ' đ';
}
function dinhDangNgayGio(chuoi) {
    const d = new Date(chuoi.replace(' ', 'T'));
    return d.toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
function dinhDangNgay(chuoi) {
    if (!chuoi) return '—';
    const d = new Date(chuoi);
    return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

/**
 * Trả về HTML mô tả "còn bao nhiêu ngày" dựa vào ngày dự kiến giao.
 * Không tính đếm ngược nếu đơn đã giao/đã hủy (không còn ý nghĩa).
 */
function moTaDuKien(ngayDuKien, trangThai) {
    if (!ngayDuKien) return { text: '—', cls: '' };
    if (trangThai === 'da_giao' || trangThai === 'huy') {
        return { text: dinhDangNgay(ngayDuKien), cls: '' };
    }
    const homNay = new Date(); homNay.setHours(0, 0, 0, 0);
    const dich = new Date(ngayDuKien); dich.setHours(0, 0, 0, 0);
    const soNgay = Math.round((dich - homNay) / 86400000);

    if (soNgay > 0) return { text: `${dinhDangNgay(ngayDuKien)} (còn ${soNgay} ngày)`, cls: 'con-som' };
    if (soNgay === 0) return { text: `${dinhDangNgay(ngayDuKien)} (hôm nay)`, cls: 'con-som' };
    return { text: `${dinhDangNgay(ngayDuKien)} (quá hạn ${Math.abs(soNgay)} ngày)`, cls: 'qua-han' };
}

/** Kiểm tra lần cập nhật gần nhất có nằm trong 24 giờ qua không (để gắn nhãn "Mới cập nhật"). */
function coCapNhatMoi(lichSu) {
    if (!lichSu || lichSu.length === 0) return false;
    const ganNhat = new Date(lichSu[0].thoi_gian.replace(' ', 'T'));
    return (Date.now() - ganNhat.getTime()) < 24 * 60 * 60 * 1000;
}

/** Dựng thanh tiến trình ngang dạng bước, hoặc banner nếu đơn đã hủy. */
function taoThanhTienTrinh(trangThai) {
    if (trangThai === 'huy') {
        return `<div class="nvc-cancelled-banner"><i class="bi bi-x-circle-fill"></i> Vận đơn này đã bị hủy</div>`;
    }

    const chiSoHienTai = CAC_BUOC.indexOf(trangThai);
    return `
    <div class="nvc-stepper">
        ${CAC_BUOC.map((buoc, i) => {
            const trangThaiBuoc = i < chiSoHienTai ? 'done' : (i === chiSoHienTai ? 'current' : '');
            const noiDungDot = i < chiSoHienTai ? '<i class="bi bi-check-lg"></i>' : (i + 1);
            const duongNoi = i < CAC_BUOC.length - 1
                ? `<div class="nvc-step-line ${i < chiSoHienTai ? 'done' : ''}"></div>` : '';
            return `
                <div class="nvc-step ${trangThaiBuoc}">
                    <span class="dot">${noiDungDot}</span>
                    <span class="lbl">${NHAN_BUOC_NGAN[buoc]}</span>
                </div>
                ${duongNoi}
            `;
        }).join('')}
    </div>`;
}

function taoTheVanDon(don, chiSo) {
    const lichSuHtml = don.lich_su.map((ls, i) => `
        <li>
            <div class="dot-wrap">
                <div class="dot"></div>
                ${i < don.lich_su.length - 1 ? '<div class="stem"></div>' : ''}
            </div>
            <div class="content">
                <div class="st-title"><i class="bi ${ICON_TRANG_THAI[ls.trang_thai] || 'bi-dot'} me-1"></i> ${NHAN_TRANG_THAI[ls.trang_thai] || ls.trang_thai}</div>
                <div class="st-meta">${ls.dia_diem ? ls.dia_diem + ' &middot; ' : ''}${dinhDangNgayGio(ls.thoi_gian)}${ls.ghi_chu ? '<br>' + ls.ghi_chu : ''}</div>
            </div>
        </li>
    `).join('');

    const duKien = moTaDuKien(don.ngay_du_kien, don.trang_thai);
    const nhanMoiCapNhat = coCapNhatMoi(don.lich_su)
        ? `<span class="nvc-badge-moi"><span class="cham"></span>Mới cập nhật</span>` : '';

    return `
    <div class="nvc-waybill">
        <div class="nvc-waybill-head">
            <div class="nvc-waybill-code">
                <span class="cap">Mã vận đơn</span>
                <span class="ma-row">
                    <span class="ma-text">${don.ma_van_don}</span>
                    <button type="button" class="nvc-copy-btn" data-copy="${don.ma_van_don}" title="Sao chép mã vận đơn">
                        <i class="bi bi-copy"></i>
                    </button>
                </span>
            </div>
            <div class="text-end">
                <span class="nvc-badge badge-${don.trang_thai}">${NHAN_TRANG_THAI[don.trang_thai] || don.trang_thai}</span>
                ${nhanMoiCapNhat}
            </div>
        </div>
        <div class="nvc-waybill-perforation"></div>
        <div class="nvc-waybill-body">

            ${taoThanhTienTrinh(don.trang_thai)}

            <div class="nvc-route">
                <div class="point">
                    <div class="lbl">Người gửi</div>
                    <div class="val">${don.ten_nguoi_gui}</div>
                    <div class="text-muted small">${don.dia_chi_gui}</div>
                </div>
                <div class="line"><i class="bi bi-truck"></i></div>
                <div class="point text-end">
                    <div class="lbl">Người nhận</div>
                    <div class="val">${don.ten_nguoi_nhan}</div>
                    <div class="text-muted small">${don.dia_chi_nhan}</div>
                </div>
            </div>

            <div class="nvc-meta-grid">
                <div><div class="lbl">Loại hàng</div><div class="val">${don.loai_hang || '—'}</div></div>
                <div><div class="lbl">Trọng lượng</div><div class="val">${Number(don.trong_luong)} kg</div></div>
                <div><div class="lbl">Phí vận chuyển</div><div class="val">${dinhDangTien(don.phi_van_chuyen)}</div></div>
                <div><div class="lbl">Dự kiến giao</div><div class="val ${duKien.cls}">${duKien.text}</div></div>
            </div>

            <button class="nvc-toggle-timeline" type="button" data-target="#timeline-${chiSo}">
                <i class="bi bi-chevron-down"></i> Xem lịch sử vận chuyển
            </button>

            <ul class="nvc-timeline d-none" id="timeline-${chiSo}">
                ${lichSuHtml}
            </ul>
        </div>
    </div>`;
}

let trangHienTaiVanDon = 1;
const SO_DON_MOI_TRANG = 5;
let timerTimKiemMa;

function taiDuLieuGiaoHang(trang) {
    const laDoiTrang = !!trang;
    if (trang) trangHienTaiVanDon = trang;

    const trangThaiLoc = encodeURIComponent(document.getElementById('nvcLocTrangThai').value);
    const tuKhoa = encodeURIComponent(document.getElementById('nvcTimMaVanDon').value.trim());

    fetch(`../ajax/giao_hang_khach_ajax.php?trang=${trangHienTaiVanDon}&so_dong=${SO_DON_MOI_TRANG}&trang_thai=${trangThaiLoc}&q=${tuKhoa}`)
        .then(res => res.json())
        .then(data => {
            if (!data.thanh_cong) {
                document.getElementById('nvcDanhSachDon').innerHTML =
                    `<div class="nvc-empty"><i class="bi bi-exclamation-triangle"></i><p>${data.thong_bao || 'Có lỗi xảy ra.'}</p></div>`;
                document.getElementById('nvcPhanTrang').innerHTML = '';
                return;
            }

            document.getElementById('tkTongSo').textContent = data.thong_ke.tong_so;
            document.getElementById('tkChoXuLy').textContent = data.thong_ke.cho_xu_ly;
            document.getElementById('tkDangVanChuyen').textContent = data.thong_ke.dang_van_chuyen;
            document.getElementById('tkDaGiao').textContent = data.thong_ke.da_giao;

            const wrap = document.getElementById('nvcDanhSachDon');

            if (data.don_hang.length === 0) {
                wrap.innerHTML = `<div class="nvc-empty"><i class="bi bi-inbox"></i><p>Không tìm thấy vận đơn nào phù hợp.</p></div>`;
                document.getElementById('nvcPhanTrang').innerHTML = '';
                return;
            }

            wrap.innerHTML = data.don_hang.map((don, i) => taoTheVanDon(don, i)).join('');

            // Gắn sự kiện mở/đóng timeline cho từng thẻ
            document.querySelectorAll('.nvc-toggle-timeline').forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = document.querySelector(btn.dataset.target);
                    target.classList.toggle('d-none');
                    btn.classList.toggle('open');
                    btn.innerHTML = target.classList.contains('d-none')
                        ? '<i class="bi bi-chevron-down"></i> Xem lịch sử vận chuyển'
                        : '<i class="bi bi-chevron-down"></i> Ẩn lịch sử vận chuyển';
                });
            });

            // Gắn sự kiện sao chép mã vận đơn
            document.querySelectorAll('.nvc-copy-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const ma = btn.dataset.copy;
                    const xongViec = () => {
                        const icon = btn.querySelector('i');
                        btn.classList.add('copied');
                        icon.className = 'bi bi-check-lg';
                        setTimeout(() => {
                            btn.classList.remove('copied');
                            icon.className = 'bi bi-copy';
                        }, 1200);
                    };
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(ma).then(xongViec).catch(() => {});
                    } else {
                        const o = document.createElement('textarea');
                        o.value = ma; document.body.appendChild(o); o.select();
                        document.execCommand('copy'); document.body.removeChild(o);
                        xongViec();
                    }
                });
            });

            renderPhanTrang(data.phan_trang);

            // Cuộn lên đầu danh sách khi đổi trang / lọc / tìm kiếm (không cuộn ở lần tải đầu tiên)
            if (laDoiTrang) {
                document.getElementById('nvcShipmentsWrap').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        })
        .catch(() => {
            document.getElementById('nvcDanhSachDon').innerHTML =
                `<div class="nvc-empty"><i class="bi bi-wifi-off"></i><p>Không thể kết nối máy chủ. Vui lòng thử lại.</p></div>`;
            document.getElementById('nvcPhanTrang').innerHTML = '';
        });
}

function renderPhanTrang(pt) {
    const el = document.getElementById('nvcPhanTrang');

    if (pt.tong_so_trang <= 1) {
        el.innerHTML = pt.tong_so_don > 0 ? `<div class="nvc-pagination"><span class="nvc-page-info">Tổng ${pt.tong_so_don} vận đơn</span></div>` : '';
        return;
    }

    let nutHtml = '';
    for (let i = 1; i <= pt.tong_so_trang; i++) {
        nutHtml += `<button type="button" class="nvc-page-btn ${i === pt.trang_hien_tai ? 'active' : ''}" onclick="taiDuLieuGiaoHang(${i})">${i}</button>`;
    }

    el.innerHTML = `
        <div class="nvc-pagination">
            <button type="button" class="nvc-page-nav" ${pt.trang_hien_tai <= 1 ? 'disabled' : ''} onclick="taiDuLieuGiaoHang(${pt.trang_hien_tai - 1})">
                <i class="bi bi-chevron-left"></i>
            </button>
            ${nutHtml}
            <button type="button" class="nvc-page-nav" ${pt.trang_hien_tai >= pt.tong_so_trang ? 'disabled' : ''} onclick="taiDuLieuGiaoHang(${pt.trang_hien_tai + 1})">
                <i class="bi bi-chevron-right"></i>
            </button>
            <span class="nvc-page-info">Tổng ${pt.tong_so_don} vận đơn</span>
        </div>
    `;
}

document.addEventListener('DOMContentLoaded', function () {
    taiDuLieuGiaoHang();

    document.getElementById('nvcLocTrangThai').addEventListener('change', () => taiDuLieuGiaoHang(1));

    document.getElementById('nvcTimMaVanDon').addEventListener('input', () => {
        clearTimeout(timerTimKiemMa);
        timerTimKiemMa = setTimeout(() => taiDuLieuGiaoHang(1), 350);
    });
});
</script>
