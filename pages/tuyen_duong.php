<?php
require_once __DIR__ . '/../includes/auth_check.php';
$nguoiDung = nguoiDungHienTai();

if ($nguoiDung['vai_tro'] !== 'admin') {
    echo "Trang này chỉ dành cho quản trị viên.";
    exit;
}

$trangAdminHienTai = 'tuyen-duong';
$tieuDeTrangAdmin  = 'Tuyến đường vận chuyển';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="nvc-toolbar">
    <div class="nvc-search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="nvcTimKiem" placeholder="Tìm theo tên kho đi/đến...">
    </div>
    <button class="nvc-btn-accent" id="nvcBtnThemMoi">
        <i class="bi bi-plus-lg"></i> Thêm tuyến đường
    </button>
</div>

<div class="nvc-table-card">
    <div class="table-responsive">
        <table class="nvc-table">
            <thead>
                <tr>
                    <th>Tuyến đường</th>
                    <th>Khoảng cách</th>
                    <th>Thời gian dự kiến</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody id="nvcTbodyTuyen">
                <tr><td colspan="6" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Phân trang -->
<div id="nvcPhanTrang"></div>

<!-- Modal Thêm/Sửa tuyến đường -->
<div class="modal fade" id="nvcModalTuyen" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content nvc-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nvcModalTieuDe"><i class="bi bi-signpost-split me-2"></i>Thêm tuyến đường</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="nvcFormTuyen">
                <div class="modal-body">
                    <input type="hidden" name="id" id="fId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Kho đi <span class="text-danger">*</span></label>
                            <select class="form-select" name="kho_di_id" id="fKhoDiId" required>
                                <option value="">-- Chọn kho đi --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Kho đến <span class="text-danger">*</span></label>
                            <select class="form-select" name="kho_den_id" id="fKhoDenId" required>
                                <option value="">-- Chọn kho đến --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Khoảng cách (km)</label>
                            <input type="number" step="0.1" min="0" class="form-control" name="khoang_cach_km" id="fKhoangCach">
                        </div>
                        <div class="col-md-6">
                            <label>Thời gian dự kiến (giờ)</label>
                            <input type="number" step="0.5" min="0" class="form-control" name="thoi_gian_du_kien_gio" id="fThoiGian">
                        </div>
                        <div class="col-md-6">
                            <label>Trạng thái</label>
                            <select class="form-select" name="trang_thai" id="fTrangThai">
                                <option value="hoat_dong">Hoạt động</option>
                                <option value="tam_ngung">Tạm ngừng</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label>Ghi chú</label>
                            <input type="text" class="form-control" name="ghi_chu" id="fGhiChu">
                        </div>
                    </div>
                    <div id="nvcFormLoi" class="text-danger small mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="nvc-btn-accent">Lưu tuyến đường</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

<script>
const NHAN_TT_TUYEN = { hoat_dong: 'Hoạt động', tam_ngung: 'Tạm ngừng' };

let modalTuyen;
let danhSachTuyenHienTai = [];
let danhSachKhoHienTai = [];

document.addEventListener('DOMContentLoaded', function () {
    modalTuyen = new bootstrap.Modal(document.getElementById('nvcModalTuyen'));
    taiDanhSachKho();
    taiDanhSachTuyen();

    document.getElementById('nvcBtnThemMoi').addEventListener('click', moModalThem);
    document.getElementById('nvcFormTuyen').addEventListener('submit', luuTuyen);

    let timerTimKiem;
    document.getElementById('nvcTimKiem').addEventListener('input', () => {
        clearTimeout(timerTimKiem);
        timerTimKiem = setTimeout(() => taiDanhSachTuyen(1), 350);
    });
});

function taiDanhSachKho() {
    fetch('../ajax/tuyen_duong_ajax.php?action=kho')
        .then(r => r.json())
        .then(data => {
            if (!data.thanh_cong) return;
            danhSachKhoHienTai = data.du_lieu;
            const options = '<option value="">-- Chọn kho --</option>' +
                data.du_lieu.map(k => `<option value="${k.id}">${k.ten_kho}</option>`).join('');
            document.getElementById('fKhoDiId').innerHTML = options;
            document.getElementById('fKhoDenId').innerHTML = options;
        });
}

let trangHienTaiTuyen = 1;
const SO_TUYEN_MOI_TRANG = 10;

function taiDanhSachTuyen(trang) {
    if (trang) trangHienTaiTuyen = trang;

    const tk = encodeURIComponent(document.getElementById('nvcTimKiem').value);
    const tbody = document.getElementById('nvcTbodyTuyen');
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>`;

    fetch(`../ajax/tuyen_duong_ajax.php?action=danh_sach&q=${tk}&trang=${trangHienTaiTuyen}&so_dong=${SO_TUYEN_MOI_TRANG}`)
        .then(r => r.json())
        .then(data => {
            if (!data.thanh_cong) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${data.thong_bao}</td></tr>`;
                document.getElementById('nvcPhanTrang').innerHTML = '';
                return;
            }
            if (data.du_lieu.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">Chưa có tuyến đường nào.</td></tr>`;
                document.getElementById('nvcPhanTrang').innerHTML = '';
                return;
            }
            danhSachTuyenHienTai = data.du_lieu;

            tbody.innerHTML = data.du_lieu.map(t => `
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span>${t.ten_kho_di}</span>
                            <i class="bi bi-arrow-right text-muted"></i>
                            <span>${t.ten_kho_den}</span>
                        </div>
                    </td>
                    <td>${t.khoang_cach_km ? Number(t.khoang_cach_km).toLocaleString('vi-VN') + ' km' : '—'}</td>
                    <td>${t.thoi_gian_du_kien_gio ? Number(t.thoi_gian_du_kien_gio) + ' giờ' : '—'}</td>
                    <td><span class="nvc-badge badge-${t.trang_thai === 'hoat_dong' ? 'hoat_dong' : 'tam_ngung'}">${NHAN_TT_TUYEN[t.trang_thai]}</span></td>
                    <td class="text-muted small">${t.ghi_chu || '—'}</td>
                    <td class="text-end">
                        <button class="action-btn" title="Sửa" onclick="moModalSuaTheoId(${t.id})"><i class="bi bi-pencil-square"></i></button>
                        <button class="action-btn danger" title="Xóa" onclick="xoaTuyenTheoId(${t.id})"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
            `).join('');

            renderPhanTrang(data.phan_trang, taiDanhSachTuyen);
        })
        .catch(() => {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Không thể kết nối máy chủ.</td></tr>`;
            document.getElementById('nvcPhanTrang').innerHTML = '';
        });
}

function moModalSuaTheoId(id) {
    const t = danhSachTuyenHienTai.find(x => x.id === id);
    if (t) moModalSua(t);
}
function xoaTuyenTheoId(id) {
    const t = danhSachTuyenHienTai.find(x => x.id === id);
    if (t) xoaTuyen(t.id, t.ten_kho_di, t.ten_kho_den);
}

function moModalThem() {
    document.getElementById('nvcFormTuyen').reset();
    document.getElementById('fId').value = '';
    document.getElementById('nvcModalTieuDe').innerHTML = '<i class="bi bi-signpost-split me-2"></i>Thêm tuyến đường';
    document.getElementById('nvcFormLoi').textContent = '';
    modalTuyen.show();
}

function moModalSua(t) {
    document.getElementById('nvcFormTuyen').reset();
    document.getElementById('fId').value = t.id;
    document.getElementById('fKhoDiId').value = t.kho_di_id;
    document.getElementById('fKhoDenId').value = t.kho_den_id;
    document.getElementById('fKhoangCach').value = t.khoang_cach_km || '';
    document.getElementById('fThoiGian').value = t.thoi_gian_du_kien_gio || '';
    document.getElementById('fTrangThai').value = t.trang_thai;
    document.getElementById('fGhiChu').value = t.ghi_chu || '';
    document.getElementById('nvcModalTieuDe').innerHTML = `<i class="bi bi-pencil-square me-2"></i>Sửa tuyến: ${t.ten_kho_di} → ${t.ten_kho_den}`;
    document.getElementById('nvcFormLoi').textContent = '';
    modalTuyen.show();
}

function luuTuyen(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('nvcFormTuyen'));
    const id = document.getElementById('fId').value;
    formData.append('action', id ? 'sua' : 'them');

    fetch('../ajax/tuyen_duong_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.thanh_cong) { modalTuyen.hide(); taiDanhSachTuyen(id ? undefined : 1); }
            else document.getElementById('nvcFormLoi').textContent = data.thong_bao;
        })
        .catch(() => { document.getElementById('nvcFormLoi').textContent = 'Không thể kết nối máy chủ.'; });
}

function xoaTuyen(id, khoDi, khoDen) {
    if (!confirm(`Bạn có chắc muốn xóa tuyến "${khoDi} → ${khoDen}"?`)) return;

    const formData = new FormData();
    formData.append('action', 'xoa');
    formData.append('id', id);

    fetch('../ajax/tuyen_duong_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => { if (data.thanh_cong) taiDanhSachTuyen(); else alert(data.thong_bao); });
}
</script>
