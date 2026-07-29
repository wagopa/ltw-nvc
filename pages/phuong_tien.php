<?php
require_once __DIR__ . '/../includes/auth_check.php';
$nguoiDung = nguoiDungHienTai();

if ($nguoiDung['vai_tro'] !== 'admin') {
    echo "Trang này chỉ dành cho quản trị viên.";
    exit;
}

$trangAdminHienTai = 'phuong-tien';
$tieuDeTrangAdmin  = 'Quản lý phương tiện';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="nvc-toolbar">
    <div class="nvc-search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="nvcTimKiem" placeholder="Tìm theo biển số, tên tài xế, SĐT...">
    </div>
    <button class="nvc-btn-accent" id="nvcBtnThemMoi">
        <i class="bi bi-plus-lg"></i> Thêm phương tiện
    </button>
</div>

<div class="nvc-table-card">
    <div class="table-responsive">
        <table class="nvc-table">
            <thead>
                <tr>
                    <th>Biển số</th>
                    <th>Loại xe</th>
                    <th>Tài xế</th>
                    <th>Kho phụ trách</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody id="nvcTbodyPT">
                <tr><td colspan="6" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Phân trang -->
<div id="nvcPhanTrang"></div>

<!-- Modal Thêm/Sửa phương tiện -->
<div class="modal fade" id="nvcModalPT" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content nvc-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nvcModalTieuDe"><i class="bi bi-truck me-2"></i>Thêm phương tiện</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="nvcFormPT">
                <div class="modal-body">
                    <input type="hidden" name="id" id="fId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Biển số <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="bien_so" id="fBienSo" placeholder="VD: 59A1-12345" required>
                        </div>
                        <div class="col-md-6">
                            <label>Loại xe <span class="text-danger">*</span></label>
                            <select class="form-select" name="loai_xe" id="fLoaiXe" required>
                                <option value="xe_may">Xe máy</option>
                                <option value="xe_tai_nho">Xe tải nhỏ</option>
                                <option value="xe_tai_lon">Xe tải lớn</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Tên tài xế <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ten_tai_xe" id="fTenTaiXe" required>
                        </div>
                        <div class="col-md-6">
                            <label>SĐT tài xế <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="sdt_tai_xe" id="fSdtTaiXe" required>
                        </div>
                        <div class="col-md-6">
                            <label>Kho phụ trách</label>
                            <select class="form-select" name="kho_id" id="fKhoId">
                                <option value="">-- Chưa gán kho --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Trạng thái</label>
                            <select class="form-select" name="trang_thai" id="fTrangThai">
                                <option value="hoat_dong">Hoạt động</option>
                                <option value="bao_tri">Bảo trì</option>
                                <option value="ngung_hoat_dong">Ngừng hoạt động</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label>Ghi chú</label>
                            <textarea class="form-control" name="ghi_chu" id="fGhiChu" rows="2"></textarea>
                        </div>
                    </div>
                    <div id="nvcFormLoi" class="text-danger small mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="nvc-btn-accent">Lưu phương tiện</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

<script>
const NHAN_LOAI_XE = { xe_may: 'Xe máy', xe_tai_nho: 'Xe tải nhỏ', xe_tai_lon: 'Xe tải lớn' };
const ICON_LOAI_XE = { xe_may: 'bi-bicycle', xe_tai_nho: 'bi-truck', xe_tai_lon: 'bi-truck-flatbed' };
const NHAN_TT_PT = { hoat_dong: 'Hoạt động', bao_tri: 'Bảo trì', ngung_hoat_dong: 'Ngừng hoạt động' };

let modalPT;
let danhSachPTHienTai = [];

document.addEventListener('DOMContentLoaded', function () {
    modalPT = new bootstrap.Modal(document.getElementById('nvcModalPT'));
    taiDanhSachKho();
    taiDanhSachPT();

    document.getElementById('nvcBtnThemMoi').addEventListener('click', moModalThem);
    document.getElementById('nvcFormPT').addEventListener('submit', luuPT);

    let timerTimKiem;
    document.getElementById('nvcTimKiem').addEventListener('input', () => {
        clearTimeout(timerTimKiem);
        timerTimKiem = setTimeout(() => taiDanhSachPT(1), 350);
    });
});

function taiDanhSachKho() {
    fetch('../ajax/phuong_tien_ajax.php?action=kho')
        .then(r => r.json())
        .then(data => {
            if (!data.thanh_cong) return;
            const sel = document.getElementById('fKhoId');
            sel.innerHTML = '<option value="">-- Chưa gán kho --</option>' +
                data.du_lieu.map(k => `<option value="${k.id}">${k.ten_kho}</option>`).join('');
        });
}

let trangHienTaiPT = 1;
const SO_PT_MOI_TRANG = 10;

function taiDanhSachPT(trang) {
    if (trang) trangHienTaiPT = trang;

    const tk = encodeURIComponent(document.getElementById('nvcTimKiem').value);
    const tbody = document.getElementById('nvcTbodyPT');
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>`;

    fetch(`../ajax/phuong_tien_ajax.php?action=danh_sach&q=${tk}&trang=${trangHienTaiPT}&so_dong=${SO_PT_MOI_TRANG}`)
        .then(r => r.json())
        .then(data => {
            if (!data.thanh_cong) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${data.thong_bao}</td></tr>`;
                document.getElementById('nvcPhanTrang').innerHTML = '';
                return;
            }
            if (data.du_lieu.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Chưa có phương tiện nào.</td></tr>`;
                document.getElementById('nvcPhanTrang').innerHTML = '';
                return;
            }
            danhSachPTHienTai = data.du_lieu;

            tbody.innerHTML = data.du_lieu.map(p => `
                <tr>
                    <td class="ma-van-don">${p.bien_so}</td>
                    <td><i class="bi ${ICON_LOAI_XE[p.loai_xe]} me-1"></i>${NHAN_LOAI_XE[p.loai_xe]}</td>
                    <td>${p.ten_tai_xe}<br><span class="text-muted small">${p.sdt_tai_xe}</span></td>
                    <td>${p.ten_kho || '<span class="text-muted">Chưa gán kho</span>'}</td>
                    <td><span class="nvc-badge badge-${p.trang_thai}">${NHAN_TT_PT[p.trang_thai]}</span></td>
                    <td class="text-end">
                        <button class="action-btn" title="Sửa" onclick="moModalSuaTheoId(${p.id})"><i class="bi bi-pencil-square"></i></button>
                        <button class="action-btn danger" title="Xóa" onclick="xoaPTTheoId(${p.id})"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
            `).join('');

            renderPhanTrang(data.phan_trang, taiDanhSachPT);
        })
        .catch(() => {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Không thể kết nối máy chủ.</td></tr>`;
            document.getElementById('nvcPhanTrang').innerHTML = '';
        });
}

function moModalSuaTheoId(id) {
    const p = danhSachPTHienTai.find(x => x.id === id);
    if (p) moModalSua(p);
}
function xoaPTTheoId(id) {
    const p = danhSachPTHienTai.find(x => x.id === id);
    if (p) xoaPT(p.id, p.bien_so);
}

function moModalThem() {
    document.getElementById('nvcFormPT').reset();
    document.getElementById('fId').value = '';
    document.getElementById('nvcModalTieuDe').innerHTML = '<i class="bi bi-truck me-2"></i>Thêm phương tiện';
    document.getElementById('nvcFormLoi').textContent = '';
    modalPT.show();
}

function moModalSua(p) {
    document.getElementById('nvcFormPT').reset();
    document.getElementById('fId').value = p.id;
    document.getElementById('fBienSo').value = p.bien_so;
    document.getElementById('fLoaiXe').value = p.loai_xe;
    document.getElementById('fTenTaiXe').value = p.ten_tai_xe;
    document.getElementById('fSdtTaiXe').value = p.sdt_tai_xe;
    document.getElementById('fKhoId').value = p.kho_id || '';
    document.getElementById('fTrangThai').value = p.trang_thai;
    document.getElementById('fGhiChu').value = p.ghi_chu || '';
    document.getElementById('nvcModalTieuDe').innerHTML = `<i class="bi bi-pencil-square me-2"></i>Sửa phương tiện: ${p.bien_so}`;
    document.getElementById('nvcFormLoi').textContent = '';
    modalPT.show();
}

function luuPT(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('nvcFormPT'));
    const id = document.getElementById('fId').value;
    formData.append('action', id ? 'sua' : 'them');

    fetch('../ajax/phuong_tien_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.thanh_cong) { modalPT.hide(); taiDanhSachPT(id ? undefined : 1); }
            else document.getElementById('nvcFormLoi').textContent = data.thong_bao;
        })
        .catch(() => { document.getElementById('nvcFormLoi').textContent = 'Không thể kết nối máy chủ.'; });
}

function xoaPT(id, bienSo) {
    if (!confirm(`Bạn có chắc muốn xóa phương tiện biển số "${bienSo}"?`)) return;

    const formData = new FormData();
    formData.append('action', 'xoa');
    formData.append('id', id);

    fetch('../ajax/phuong_tien_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => { if (data.thanh_cong) taiDanhSachPT(); else alert(data.thong_bao); });
}
</script>
