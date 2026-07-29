<?php
require_once __DIR__ . '/../includes/auth_check.php';
$nguoiDung = nguoiDungHienTai();

if ($nguoiDung['vai_tro'] !== 'admin') {
    echo "Trang này chỉ dành cho quản trị viên.";
    exit;
}

$trangAdminHienTai = 'dashboard';
$tieuDeTrangAdmin  = 'Dashboard thống kê';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Thẻ tổng quan -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="nvc-dash-card">
            <div class="nvc-dash-summary">
                <span class="ic" style="background:var(--info-bg);color:var(--info);"><i class="bi bi-box-seam-fill"></i></span>
                <div>
                    <div class="num" id="dTongVanDon">--</div>
                    <div class="label">Tổng số vận đơn</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="nvc-dash-card">
            <div class="nvc-dash-summary">
                <span class="ic" style="background:var(--success-bg);color:var(--success);"><i class="bi bi-people-fill"></i></span>
                <div>
                    <div class="num" id="dTongKhachHang">--</div>
                    <div class="label">Tổng số khách hàng</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="nvc-dash-card">
            <div class="nvc-dash-summary">
                <span class="ic" style="background:var(--warning-bg);color:#92610a;"><i class="bi bi-cash-coin"></i></span>
                <div>
                    <div class="num" id="dTongDoanhThu">--</div>
                    <div class="label">Tổng phí vận chuyển thu được</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Biểu đồ -->
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="nvc-dash-card">
            <h6>Số vận đơn tạo mới (7 ngày gần nhất)</h6>
            <canvas id="bieuDo7Ngay" height="110"></canvas>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="nvc-dash-card">
            <h6>Tỉ lệ theo trạng thái</h6>
            <canvas id="bieuDoTrangThai" height="150"></canvas>
        </div>
    </div>
</div>

<!-- Vận đơn mới nhất + Top khách hàng -->
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="nvc-dash-card">
            <h6>Vận đơn mới nhất</h6>
            <div id="dsMoiNhat"><p class="text-muted small mb-0">Đang tải...</p></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="nvc-dash-card">
            <h6>Top khách hàng nhiều vận đơn nhất</h6>
            <ul class="nvc-rank-list" id="dsTopKhachHang">
                <li class="text-muted small">Đang tải...</li>
            </ul>
        </div>
    </div>
</div>

<!-- Vận hành: Phương tiện + Kho trung chuyển -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="nvc-dash-card">
            <div class="nvc-dash-summary">
                <span class="ic" style="background:var(--info-bg);color:var(--info);"><i class="bi bi-truck"></i></span>
                <div>
                    <div class="num" id="dTongPhuongTien">--</div>
                    <div class="label">Tổng số phương tiện</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="nvc-dash-card">
            <div class="nvc-dash-summary">
                <span class="ic" style="background:var(--success-bg);color:var(--success);"><i class="bi bi-building"></i></span>
                <div>
                    <div class="num" id="dKhoHoatDong">--</div>
                    <div class="label">Kho đang hoạt động</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="nvc-dash-card">
            <h6>Phương tiện theo trạng thái</h6>
            <div id="dsPTTrangThai"><p class="text-muted small mb-0">Đang tải...</p></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="nvc-dash-card">
            <h6>Phân bổ phương tiện theo kho</h6>
            <div id="dsPhanBoKho"><p class="text-muted small mb-0">Đang tải...</p></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const NHAN_TRANG_THAI = {
    cho_xu_ly: 'Chờ xử lý', da_tiep_nhan: 'Đã tiếp nhận', dang_van_chuyen: 'Đang vận chuyển',
    da_giao: 'Đã giao', huy: 'Đã hủy'
};
const MAU_TRANG_THAI = {
    cho_xu_ly: '#f0a202', da_tiep_nhan: '#0ea5e9', dang_van_chuyen: '#0ea5e9',
    da_giao: '#16a34a', huy: '#dc2626'
};
const NHAN_TT_PT = { hoat_dong: 'Hoạt động', bao_tri: 'Bảo trì', ngung_hoat_dong: 'Ngừng hoạt động' };
const MAU_TT_PT = { hoat_dong: '#16a34a', bao_tri: '#f0a202', ngung_hoat_dong: '#dc2626' };
const NHAN_KHU_VUC = { mien_bac: 'Miền Bắc', mien_trung: 'Miền Trung', mien_nam: 'Miền Nam' };

function dinhDangTien(so) { return Number(so).toLocaleString('vi-VN') + ' đ'; }
function dinhDangNgay(chuoi) {
    const d = new Date(chuoi.replace(' ', 'T'));
    return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

document.addEventListener('DOMContentLoaded', function () {
    fetch('../ajax/thong_ke_ajax.php')
        .then(r => r.json())
        .then(data => {
            if (!data.thanh_cong) {
                alert(data.thong_bao || 'Không tải được dữ liệu thống kê.');
                return;
            }

            // Thẻ tổng quan
            document.getElementById('dTongVanDon').textContent = data.tong_quan.tong_van_don;
            document.getElementById('dTongKhachHang').textContent = data.tong_quan.tong_khach_hang;
            document.getElementById('dTongDoanhThu').textContent = dinhDangTien(data.tong_quan.tong_doanh_thu);

            // Biểu đồ 7 ngày
            new Chart(document.getElementById('bieuDo7Ngay'), {
                type: 'bar',
                data: {
                    labels: data.theo_ngay.nhan,
                    datasets: [{
                        label: 'Vận đơn mới',
                        data: data.theo_ngay.so_luong,
                        backgroundColor: '#ff6b35',
                        borderRadius: 6,
                        maxBarThickness: 36
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });

            // Biểu đồ trạng thái
            const nhanTT = Object.keys(data.theo_trang_thai);
            new Chart(document.getElementById('bieuDoTrangThai'), {
                type: 'doughnut',
                data: {
                    labels: nhanTT.map(k => NHAN_TRANG_THAI[k]),
                    datasets: [{
                        data: nhanTT.map(k => data.theo_trang_thai[k]),
                        backgroundColor: nhanTT.map(k => MAU_TRANG_THAI[k]),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
                }
            });

            // Vận đơn mới nhất
            const dsMoiNhat = document.getElementById('dsMoiNhat');
            if (data.moi_nhat.length === 0) {
                dsMoiNhat.innerHTML = '<p class="text-muted small mb-0">Chưa có vận đơn nào.</p>';
            } else {
                dsMoiNhat.innerHTML = data.moi_nhat.map(don => `
                    <div class="nvc-mini-row">
                        <div>
                            <div class="ma">${don.ma_van_don}</div>
                            <div class="sub">${don.ten_khach_hang} &rarr; ${don.ten_nguoi_nhan}</div>
                        </div>
                        <div class="text-end">
                            <span class="nvc-badge badge-${don.trang_thai}">${NHAN_TRANG_THAI[don.trang_thai]}</span>
                            <div class="sub">${dinhDangNgay(don.ngay_tao)}</div>
                        </div>
                    </div>
                `).join('');
            }

            // Top khách hàng
            const dsTop = document.getElementById('dsTopKhachHang');
            if (data.top_khach_hang.length === 0) {
                dsTop.innerHTML = '<li class="text-muted small">Chưa có dữ liệu.</li>';
            } else {
                dsTop.innerHTML = data.top_khach_hang.map((kh, i) => `
                    <li>
                        <span class="rank-no">${i + 1}</span>
                        <span class="name">${kh.ho_ten}</span>
                        <span class="count">${kh.so_van_don} vận đơn</span>
                    </li>
                `).join('');
            }

            // Thẻ tổng quan phương tiện + kho
            document.getElementById('dTongPhuongTien').textContent = data.phuong_tien.tong_so;
            document.getElementById('dKhoHoatDong').textContent = `${data.kho.hoat_dong}/${data.kho.tong_so}`;

            // Phương tiện theo trạng thái (mini bar)
            const dsPT = document.getElementById('dsPTTrangThai');
            const tongPT = data.phuong_tien.tong_so;
            if (tongPT === 0) {
                dsPT.innerHTML = '<p class="text-muted small mb-0">Chưa có phương tiện nào.</p>';
            } else {
                dsPT.innerHTML = Object.entries(data.phuong_tien.theo_trang_thai).map(([k, soLuong]) => {
                    const phanTram = tongPT > 0 ? Math.round((soLuong / tongPT) * 100) : 0;
                    return `
                        <div class="nvc-pt-status-row">
                            <span class="dot" style="background:${MAU_TT_PT[k]}"></span>
                            <span class="lbl">${NHAN_TT_PT[k]}</span>
                            <span class="bar-track"><span class="bar-fill" style="width:${phanTram}%;background:${MAU_TT_PT[k]}"></span></span>
                            <span class="count">${soLuong}</span>
                        </div>
                    `;
                }).join('');
            }

            // Phân bổ phương tiện theo kho
            const dsKho = document.getElementById('dsPhanBoKho');
            if (data.kho.phuong_tien_theo_kho.length === 0) {
                dsKho.innerHTML = '<p class="text-muted small mb-0">Chưa có kho nào.</p>';
            } else {
                dsKho.innerHTML = data.kho.phuong_tien_theo_kho.map(k => `
                    <div class="nvc-kho-row">
                        <div>
                            <div class="ten">${k.ten_kho}</div>
                            <div class="khu-vuc">${NHAN_KHU_VUC[k.khu_vuc]}</div>
                        </div>
                        <div class="so">${k.so_phuong_tien} phương tiện</div>
                    </div>
                `).join('');
            }
        })
        .catch(() => alert('Không thể kết nối máy chủ.'));
});
</script>
