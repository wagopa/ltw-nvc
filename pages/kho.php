<?php
require_once __DIR__ . '/../includes/auth_check.php';
$nguoiDung = nguoiDungHienTai();

if ($nguoiDung['vai_tro'] !== 'admin') {
    echo "Trang này chỉ dành cho quản trị viên.";
    exit;
}

$trangAdminHienTai = 'kho';
$tieuDeTrangAdmin  = 'Kho trung chuyển';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="nvc-toolbar">
    <div class="nvc-search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="nvcTimKiem" placeholder="Tìm theo tên kho, địa chỉ, người quản lý...">
    </div>
    <button class="nvc-btn-accent" id="nvcBtnThemMoi">
        <i class="bi bi-plus-lg"></i> Thêm kho
    </button>
</div>

<div class="nvc-table-card">
    <div class="table-responsive">
        <table class="nvc-table">
            <thead>
                <tr>
                    <th>Tên kho</th>
                    <th>Khu vực</th>
                    <th>Địa chỉ</th>
                    <th>Người quản lý</th>
                    <th>Phương tiện</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody id="nvcTbodyKho">
                <tr><td colspan="7" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Phân trang -->
<div id="nvcPhanTrang"></div>

<!-- Modal Thêm/Sửa kho -->
<div class="modal fade" id="nvcModalKho" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content nvc-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nvcModalTieuDe"><i class="bi bi-building me-2"></i>Thêm kho trung chuyển</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="nvcFormKho">
                <div class="modal-body">
                    <input type="hidden" name="id" id="fId">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label>Tên kho <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ten_kho" id="fTenKho" required>
                        </div>
                        <div class="col-md-4">
                            <label>Khu vực <span class="text-danger">*</span></label>
                            <select class="form-select" name="khu_vuc" id="fKhuVuc" required>
                                <option value="mien_bac">Miền Bắc</option>
                                <option value="mien_trung">Miền Trung</option>
                                <option value="mien_nam">Miền Nam</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label>Địa chỉ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="dia_chi" id="fDiaChi" required>
                        </div>
                        <div class="col-md-6">
                            <label>Người quản lý</label>
                            <input type="text" class="form-control" name="nguoi_quan_ly" id="fNguoiQuanLy">
                        </div>
                        <div class="col-md-6">
                            <label>SĐT quản lý</label>
                            <input type="text" class="form-control" name="sdt_quan_ly" id="fSdtQuanLy">
                        </div>
                        <div class="col-md-6">
                            <label>Trạng thái</label>
                            <select class="form-select" name="trang_thai" id="fTrangThai">
                                <option value="hoat_dong">Hoạt động</option>
                                <option value="tam_ngung">Tạm ngừng</option>
                            </select>
                        </div>
                    </div>
                    <div id="nvcFormLoi" class="text-danger small mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="nvc-btn-accent">Lưu kho</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

<script>
const NHAN_KHU_VUC = { mien_bac: 'Miền Bắc', mien_trung: 'Miền Trung', mien_nam: 'Miền Nam' };
const NHAN_TT_KHO = { hoat_dong: 'Hoạt động', tam_ngung: 'Tạm ngừng' };

let modalKho;
let danhSachKhoHienTai = [];

document.addEventListener('DOMContentLoaded', function () {
    modalKho = new bootstrap.Modal(document.getElementById('nvcModalKho'));
    taiDanhSachKho();

    document.getElementById('nvcBtnThemMoi').addEventListener('click', moModalThem);
    document.getElementById('nvcFormKho').addEventListener('submit', luuKho);

    let timerTimKiem;
    document.getElementById('nvcTimKiem').addEventListener('input', () => {
        clearTimeout(timerTimKiem);
        timerTimKiem = setTimeout(() => taiDanhSachKho(1), 350);
    });
});

let trangHienTaiKho = 1;
const SO_KHO_MOI_TRANG = 10;

function taiDanhSachKho(trang) {
    if (trang) trangHienTaiKho = trang;

    const tk = encodeURIComponent(document.getElementById('nvcTimKiem').value);
    const tbody = document.getElementById('nvcTbodyKho');
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>`;

    fetch(`../ajax/kho_ajax.php?action=danh_sach&q=${tk}&trang=${trangHienTaiKho}&so_dong=${SO_KHO_MOI_TRANG}`)
        .then(r => r.json())
        .then(data => {
            if (!data.thanh_cong) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${data.thong_bao}</td></tr>`;
                document.getElementById('nvcPhanTrang').innerHTML = '';
                return;
            }
            if (data.du_lieu.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">Chưa có kho nào.</td></tr>`;
                document.getElementById('nvcPhanTrang').innerHTML = '';
                return;
            }
            danhSachKhoHienTai = data.du_lieu;

            tbody.innerHTML = data.du_lieu.map(k => `
                <tr>
                    <td>${k.ten_kho}</td>
                    <td>${NHAN_KHU_VUC[k.khu_vuc]}</td>
                    <td>${k.dia_chi}</td>
                    <td>${k.nguoi_quan_ly || '—'}${k.sdt_quan_ly ? '<br><span class="text-muted small">' + k.sdt_quan_ly + '</span>' : ''}</td>
                    <td>${k.so_phuong_tien}</td>
                    <td><span class="nvc-badge badge-${k.trang_thai}">${NHAN_TT_KHO[k.trang_thai]}</span></td>
                    <td class="text-end">
                        <button class="action-btn" title="Sửa" onclick="moModalSuaTheoId(${k.id})"><i class="bi bi-pencil-square"></i></button>
                        <button class="action-btn danger" title="Xóa" onclick="xoaKhoTheoId(${k.id})"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
            `).join('');

            renderPhanTrang(data.phan_trang, taiDanhSachKho);
        })
        .catch(() => {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Không thể kết nối máy chủ.</td></tr>`;
            document.getElementById('nvcPhanTrang').innerHTML = '';
        });
}

function moModalSuaTheoId(id) {
    const k = danhSachKhoHienTai.find(x => x.id === id);
    if (k) moModalSua(k);
}
function xoaKhoTheoId(id) {
    const k = danhSachKhoHienTai.find(x => x.id === id);
    if (k) xoaKho(k.id, k.ten_kho, k.so_phuong_tien);
}

function moModalThem() {
    document.getElementById('nvcFormKho').reset();
    document.getElementById('fId').value = '';
    document.getElementById('nvcModalTieuDe').innerHTML = '<i class="bi bi-building me-2"></i>Thêm kho trung chuyển';
    document.getElementById('nvcFormLoi').textContent = '';
    modalKho.show();
}

function moModalSua(k) {
    document.getElementById('nvcFormKho').reset();
    document.getElementById('fId').value = k.id;
    document.getElementById('fTenKho').value = k.ten_kho;
    document.getElementById('fKhuVuc').value = k.khu_vuc;
    document.getElementById('fDiaChi').value = k.dia_chi;
    document.getElementById('fNguoiQuanLy').value = k.nguoi_quan_ly || '';
    document.getElementById('fSdtQuanLy').value = k.sdt_quan_ly || '';
    document.getElementById('fTrangThai').value = k.trang_thai;
    document.getElementById('nvcModalTieuDe').innerHTML = `<i class="bi bi-pencil-square me-2"></i>Sửa kho: ${k.ten_kho}`;
    document.getElementById('nvcFormLoi').textContent = '';
    modalKho.show();
}

function luuKho(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('nvcFormKho'));
    const id = document.getElementById('fId').value;
    formData.append('action', id ? 'sua' : 'them');

    fetch('../ajax/kho_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.thanh_cong) { modalKho.hide(); taiDanhSachKho(id ? undefined : 1); }
            else document.getElementById('nvcFormLoi').textContent = data.thong_bao;
        })
        .catch(() => { document.getElementById('nvcFormLoi').textContent = 'Không thể kết nối máy chủ.'; });
}

function xoaKho(id, tenKho, soPhuongTien) {
    let canhBao = `Bạn có chắc muốn xóa "${tenKho}"?`;
    if (soPhuongTien > 0) canhBao += `\n\nLưu ý: ${soPhuongTien} phương tiện đang gắn với kho này sẽ chuyển về trạng thái chưa gán kho.`;
    if (!confirm(canhBao)) return;

    const formData = new FormData();
    formData.append('action', 'xoa');
    formData.append('id', id);

    fetch('../ajax/kho_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => { if (data.thanh_cong) taiDanhSachKho(); else alert(data.thong_bao); });
}
</script>
