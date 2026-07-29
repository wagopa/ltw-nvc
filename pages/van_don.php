<?php
require_once __DIR__ . '/../includes/auth_check.php';
$nguoiDung = nguoiDungHienTai();

if ($nguoiDung['vai_tro'] !== 'admin') {
    echo "Trang này chỉ dành cho quản trị viên.";
    exit;
}

$trangAdminHienTai = 'van-don';
$tieuDeTrangAdmin  = 'Quản lý vận đơn';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="nvc-toolbar">
    <div class="nvc-search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="nvcTimKiem" placeholder="Tìm theo mã vận đơn, người nhận, khách hàng...">
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <select id="nvcLocTrangThai" class="form-select form-select-sm" style="width:180px;">
            <option value="">Tất cả trạng thái</option>
            <option value="cho_xu_ly">Chờ xử lý</option>
            <option value="da_tiep_nhan">Đã tiếp nhận</option>
            <option value="dang_van_chuyen">Đang vận chuyển</option>
            <option value="da_giao">Đã giao</option>
            <option value="huy">Đã hủy</option>
        </select>
        <button class="nvc-btn-accent" id="nvcBtnThemMoi">
            <i class="bi bi-plus-lg"></i> Thêm vận đơn
        </button>
    </div>
</div>

<div class="nvc-table-card">
    <div class="table-responsive">
        <table class="nvc-table">
            <thead>
                <tr>
                    <th>Mã vận đơn</th>
                    <th>Khách hàng</th>
                    <th>Người nhận</th>
                    <th>Loại hàng</th>
                    <th>Phí VC</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody id="nvcTbodyVanDon">
                <tr><td colspan="8" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Phân trang -->
<div id="nvcPhanTrang"></div>

<!-- Modal Thêm/Sửa vận đơn -->
<div class="modal fade" id="nvcModalVanDon" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content nvc-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nvcModalTieuDe"><i class="bi bi-box-seam me-2"></i>Thêm vận đơn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="nvcFormVanDon">
                <div class="modal-body">
                    <input type="hidden" name="id" id="fId">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Khách hàng (chủ vận đơn) <span class="text-danger">*</span></label>
                            <select class="form-select" name="nguoi_dung_id" id="fNguoiDungId" required>
                                <option value="">-- Chọn khách hàng --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Trạng thái</label>
                            <select class="form-select" name="trang_thai" id="fTrangThai">
                                <option value="cho_xu_ly">Chờ xử lý</option>
                                <option value="da_tiep_nhan">Đã tiếp nhận</option>
                                <option value="dang_van_chuyen">Đang vận chuyển</option>
                                <option value="da_giao">Đã giao</option>
                                <option value="huy">Đã hủy</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label>Tên người gửi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ten_nguoi_gui" id="fTenNguoiGui" required>
                        </div>
                        <div class="col-md-6">
                            <label>Địa chỉ gửi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="dia_chi_gui" id="fDiaChiGui" required>
                        </div>

                        <div class="col-md-4">
                            <label>Tên người nhận <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ten_nguoi_nhan" id="fTenNguoiNhan" required>
                        </div>
                        <div class="col-md-3">
                            <label>SĐT người nhận <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="sdt_nguoi_nhan" id="fSdtNguoiNhan" required>
                        </div>
                        <div class="col-md-5">
                            <label>Địa chỉ nhận <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="dia_chi_nhan" id="fDiaChiNhan" required>
                        </div>

                        <div class="col-md-4">
                            <label>Loại hàng</label>
                            <input type="text" class="form-control" name="loai_hang" id="fLoaiHang" placeholder="VD: Quần áo, điện tử...">
                        </div>
                        <div class="col-md-4">
                            <label>Trọng lượng (kg)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="trong_luong" id="fTrongLuong">
                        </div>
                        <div class="col-md-4">
                            <label>Phí vận chuyển (đ)</label>
                            <input type="number" step="1000" min="0" class="form-control" name="phi_van_chuyen" id="fPhiVanChuyen">
                        </div>

                        <div class="col-md-6">
                            <label>Ngày dự kiến giao</label>
                            <input type="date" class="form-control" name="ngay_du_kien" id="fNgayDuKien">
                        </div>
                    </div>
                    <div id="nvcFormLoi" class="text-danger small mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="nvc-btn-accent">Lưu vận đơn</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

<script>
const NHAN_TRANG_THAI = {
    cho_xu_ly: 'Chờ xử lý', da_tiep_nhan: 'Đã tiếp nhận', dang_van_chuyen: 'Đang vận chuyển',
    da_giao: 'Đã giao', huy: 'Đã hủy'
};

let modalVanDon;
document.addEventListener('DOMContentLoaded', function () {
    modalVanDon = new bootstrap.Modal(document.getElementById('nvcModalVanDon'));
    taiDanhSachKhachHang();
    taiDanhSachVanDon();

    document.getElementById('nvcBtnThemMoi').addEventListener('click', moModalThem);
    document.getElementById('nvcFormVanDon').addEventListener('submit', luuVanDon);

    let timerTimKiem;
    document.getElementById('nvcTimKiem').addEventListener('input', () => {
        clearTimeout(timerTimKiem);
        timerTimKiem = setTimeout(() => taiDanhSachVanDon(1), 350);
    });
    document.getElementById('nvcLocTrangThai').addEventListener('change', () => taiDanhSachVanDon(1));
});

function dinhDangTien(so) { return Number(so).toLocaleString('vi-VN') + ' đ'; }
function dinhDangNgay(chuoi) {
    if (!chuoi) return '—';
    const d = new Date(chuoi.replace(' ', 'T'));
    return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function taiDanhSachKhachHang() {
    fetch('../ajax/van_don_ajax.php?action=khach_hang')
        .then(r => r.json())
        .then(data => {
            if (!data.thanh_cong) return;
            const sel = document.getElementById('fNguoiDungId');
            sel.innerHTML = '<option value="">-- Chọn khách hàng --</option>' +
                data.du_lieu.map(kh => `<option value="${kh.id}">${kh.ho_ten} (${kh.sdt || 'chưa có SĐT'})</option>`).join('');
        });
}

let trangHienTaiVanDon = 1;
const SO_DON_MOI_TRANG = 10;

function taiDanhSachVanDon(trang) {
    if (trang) trangHienTaiVanDon = trang;

    const tk = encodeURIComponent(document.getElementById('nvcTimKiem').value);
    const tt = encodeURIComponent(document.getElementById('nvcLocTrangThai').value);
    const tbody = document.getElementById('nvcTbodyVanDon');
    tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>`;

    fetch(`../ajax/van_don_ajax.php?action=danh_sach&q=${tk}&trang_thai=${tt}&trang=${trangHienTaiVanDon}&so_dong=${SO_DON_MOI_TRANG}`)
        .then(r => r.json())
        .then(data => {
            if (!data.thanh_cong) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">${data.thong_bao}</td></tr>`;
                document.getElementById('nvcPhanTrang').innerHTML = '';
                return;
            }
            if (data.du_lieu.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">Không tìm thấy vận đơn nào.</td></tr>`;
                document.getElementById('nvcPhanTrang').innerHTML = '';
                return;
            }

            danhSachVanDonHienTai = data.du_lieu;

            tbody.innerHTML = data.du_lieu.map(don => `
                <tr>
                    <td class="ma-van-don">${don.ma_van_don}</td>
                    <td>${don.ten_khach_hang}</td>
                    <td>${don.ten_nguoi_nhan}<br><span class="text-muted small">${don.sdt_nguoi_nhan}</span></td>
                    <td>${don.loai_hang || '—'}</td>
                    <td>${dinhDangTien(don.phi_van_chuyen)}</td>
                    <td>
                        <select class="trang-thai-select" onchange="doiTrangThai(${don.id}, this.value)">
                            ${Object.entries(NHAN_TRANG_THAI).map(([k, v]) =>
                                `<option value="${k}" ${k === don.trang_thai ? 'selected' : ''}>${v}</option>`).join('')}
                        </select>
                    </td>
                    <td>${dinhDangNgay(don.ngay_tao)}</td>
                    <td class="text-end">
                        <button class="action-btn" title="Sửa" onclick="moModalSuaTheoId(${don.id})"><i class="bi bi-pencil-square"></i></button>
                        <button class="action-btn danger" title="Xóa" onclick="xoaVanDonTheoId(${don.id})"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
            `).join('');

            renderPhanTrang(data.phan_trang, taiDanhSachVanDon);
        })
        .catch(() => {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Không thể kết nối máy chủ.</td></tr>`;
            document.getElementById('nvcPhanTrang').innerHTML = '';
        });
}

// Lưu danh sách vận đơn hiện tại (dùng để tra cứu khi bấm Sửa/Xóa, tránh nhúng
// dữ liệu trực tiếp vào HTML onclick — an toàn hơn khi dữ liệu có dấu nháy đơn, v.v.)
let danhSachVanDonHienTai = [];

function moModalSuaTheoId(id) {
    const don = danhSachVanDonHienTai.find(d => d.id === id);
    if (don) moModalSua(don);
}

function xoaVanDonTheoId(id) {
    const don = danhSachVanDonHienTai.find(d => d.id === id);
    if (don) xoaVanDon(don.id, don.ma_van_don);
}

function moModalThem() {
    document.getElementById('nvcFormVanDon').reset();
    document.getElementById('fId').value = '';
    document.getElementById('nvcModalTieuDe').innerHTML = '<i class="bi bi-box-seam me-2"></i>Thêm vận đơn';
    document.getElementById('nvcFormLoi').textContent = '';
    modalVanDon.show();
}

function moModalSua(don) {
    document.getElementById('nvcFormVanDon').reset();
    document.getElementById('fId').value = don.id;
    document.getElementById('fNguoiDungId').value = don.nguoi_dung_id;
    document.getElementById('fTrangThai').value = don.trang_thai;
    document.getElementById('fTenNguoiGui').value = don.ten_nguoi_gui;
    document.getElementById('fDiaChiGui').value = don.dia_chi_gui;
    document.getElementById('fTenNguoiNhan').value = don.ten_nguoi_nhan;
    document.getElementById('fSdtNguoiNhan').value = don.sdt_nguoi_nhan;
    document.getElementById('fDiaChiNhan').value = don.dia_chi_nhan;
    document.getElementById('fLoaiHang').value = don.loai_hang || '';
    document.getElementById('fTrongLuong').value = don.trong_luong;
    document.getElementById('fPhiVanChuyen').value = don.phi_van_chuyen;
    document.getElementById('fNgayDuKien').value = don.ngay_du_kien || '';
    document.getElementById('nvcModalTieuDe').innerHTML = `<i class="bi bi-pencil-square me-2"></i>Sửa vận đơn ${don.ma_van_don}`;
    document.getElementById('nvcFormLoi').textContent = '';
    modalVanDon.show();
}

function luuVanDon(e) {
    e.preventDefault();
    const form = document.getElementById('nvcFormVanDon');
    const formData = new FormData(form);
    const id = document.getElementById('fId').value;
    formData.append('action', id ? 'sua' : 'them');

    fetch('../ajax/van_don_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.thanh_cong) {
                modalVanDon.hide();
                taiDanhSachVanDon(id ? undefined : 1);
            } else {
                document.getElementById('nvcFormLoi').textContent = data.thong_bao;
            }
        })
        .catch(() => { document.getElementById('nvcFormLoi').textContent = 'Không thể kết nối máy chủ.'; });
}

function doiTrangThai(id, trangThai) {
    const formData = new FormData();
    formData.append('action', 'doi_trang_thai');
    formData.append('id', id);
    formData.append('trang_thai', trangThai);

    fetch('../ajax/van_don_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => { if (!data.thanh_cong) alert(data.thong_bao); });
}

function xoaVanDon(id, maVanDon) {
    if (!confirm(`Bạn có chắc muốn xóa vận đơn ${maVanDon}? Hành động này không thể hoàn tác.`)) return;

    const formData = new FormData();
    formData.append('action', 'xoa');
    formData.append('id', id);

    fetch('../ajax/van_don_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.thanh_cong) taiDanhSachVanDon();
            else alert(data.thong_bao);
        });
}
</script>
