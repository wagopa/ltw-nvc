<?php
require_once __DIR__ . '/../includes/auth_check.php';
$nguoiDung = nguoiDungHienTai();

if ($nguoiDung['vai_tro'] !== 'admin') {
    echo "Trang này chỉ dành cho quản trị viên.";
    exit;
}

$trangAdminHienTai = 'khach-hang';
$tieuDeTrangAdmin  = 'Quản lý tài khoản khách hàng';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="nvc-toolbar">
    <div class="nvc-search-box">
        <i class="bi bi-search"></i>
        <input type="text" id="nvcTimKiem" placeholder="Tìm theo tên, tài khoản, SĐT, email...">
    </div>
    <button class="nvc-btn-accent" id="nvcBtnThemMoi">
        <i class="bi bi-person-plus"></i> Thêm khách hàng
    </button>
</div>

<div class="nvc-table-card">
    <div class="table-responsive">
        <table class="nvc-table">
            <thead>
                <tr>
                    <th>Họ tên</th>
                    <th>Tài khoản</th>
                    <th>SĐT</th>
                    <th>Email</th>
                    <th>Số vận đơn</th>
                    <th>Ngày tạo</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody id="nvcTbodyKhachHang">
                <tr><td colspan="7" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Phân trang -->
<div id="nvcPhanTrang"></div>

<!-- Modal Thêm/Sửa khách hàng -->
<div class="modal fade" id="nvcModalKhachHang" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content nvc-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nvcModalTieuDe"><i class="bi bi-person-plus me-2"></i>Thêm khách hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="nvcFormKhachHang">
                <div class="modal-body">
                    <input type="hidden" name="id" id="fId">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Họ tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="ho_ten" id="fHoTen" required>
                        </div>
                        <div class="col-md-6">
                            <label>Tên tài khoản (đăng nhập) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" id="fUsername" required>
                        </div>

                        <div class="col-md-6">
                            <label>Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="sdt" id="fSdt" required>
                        </div>
                        <div class="col-md-6">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" id="fEmail">
                        </div>

                        <div class="col-12">
                            <label>Mật khẩu <span class="text-danger" id="fMatKhauSao">*</span></label>
                            <input type="password" class="form-control" name="mat_khau" id="fMatKhau" placeholder="Tối thiểu 6 ký tự">
                            <div class="form-text" id="fMatKhauGhiChu">Bắt buộc nhập khi tạo tài khoản mới.</div>
                        </div>
                    </div>
                    <div id="nvcFormLoi" class="text-danger small mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="nvc-btn-accent">Lưu khách hàng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

<script>
let modalKhachHang;
document.addEventListener('DOMContentLoaded', function () {
    modalKhachHang = new bootstrap.Modal(document.getElementById('nvcModalKhachHang'));
    taiDanhSachKhachHang();

    document.getElementById('nvcBtnThemMoi').addEventListener('click', moModalThem);
    document.getElementById('nvcFormKhachHang').addEventListener('submit', luuKhachHang);

    let timerTimKiem;
    document.getElementById('nvcTimKiem').addEventListener('input', () => {
        clearTimeout(timerTimKiem);
        timerTimKiem = setTimeout(() => taiDanhSachKhachHang(1), 350);
    });
});

function dinhDangNgay(chuoi) {
    if (!chuoi) return '—';
    const d = new Date(chuoi.replace(' ', 'T'));
    return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

let trangHienTaiKH = 1;
const SO_KH_MOI_TRANG = 10;

function taiDanhSachKhachHang(trang) {
    if (trang) trangHienTaiKH = trang;

    const tk = encodeURIComponent(document.getElementById('nvcTimKiem').value);
    const tbody = document.getElementById('nvcTbodyKhachHang');
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>`;

    fetch(`../ajax/khach_hang_ajax.php?action=danh_sach&q=${tk}&trang=${trangHienTaiKH}&so_dong=${SO_KH_MOI_TRANG}`)
        .then(r => r.json())
        .then(data => {
            if (!data.thanh_cong) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${data.thong_bao}</td></tr>`;
                document.getElementById('nvcPhanTrang').innerHTML = '';
                return;
            }
            if (data.du_lieu.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">Chưa có khách hàng nào.</td></tr>`;
                document.getElementById('nvcPhanTrang').innerHTML = '';
                return;
            }

            danhSachKhachHangHienTai = data.du_lieu;

            tbody.innerHTML = data.du_lieu.map(kh => `
                <tr>
                    <td>${kh.ho_ten}</td>
                    <td class="ma-van-don">${kh.username}</td>
                    <td>${kh.sdt || '—'}</td>
                    <td>${kh.email || '—'}</td>
                    <td>${kh.so_van_don}</td>
                    <td>${dinhDangNgay(kh.ngay_tao)}</td>
                    <td class="text-end">
                        <button class="action-btn" title="Sửa" onclick="moModalSuaTheoId(${kh.id})"><i class="bi bi-pencil-square"></i></button>
                        <button class="action-btn danger" title="Xóa" onclick="xoaKhachHangTheoId(${kh.id})"><i class="bi bi-trash3"></i></button>
                    </td>
                </tr>
            `).join('');

            renderPhanTrang(data.phan_trang, taiDanhSachKhachHang);
        })
        .catch(() => {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Không thể kết nối máy chủ.</td></tr>`;
            document.getElementById('nvcPhanTrang').innerHTML = '';
        });
}

// Lưu danh sách khách hàng hiện tại để tra cứu khi Sửa/Xóa (an toàn hơn nhúng
// trực tiếp dữ liệu vào HTML onclick khi tên/địa chỉ có dấu nháy đơn, v.v.)
let danhSachKhachHangHienTai = [];

function moModalSuaTheoId(id) {
    const kh = danhSachKhachHangHienTai.find(k => k.id === id);
    if (kh) moModalSua(kh);
}

function xoaKhachHangTheoId(id) {
    const kh = danhSachKhachHangHienTai.find(k => k.id === id);
    if (kh) xoaKhachHang(kh.id, kh.ho_ten, kh.so_van_don);
}

function moModalThem() {
    document.getElementById('nvcFormKhachHang').reset();
    document.getElementById('fId').value = '';
    document.getElementById('fUsername').disabled = false;
    document.getElementById('fMatKhauSao').style.display = 'inline';
    document.getElementById('fMatKhauGhiChu').textContent = 'Bắt buộc nhập khi tạo tài khoản mới.';
    document.getElementById('nvcModalTieuDe').innerHTML = '<i class="bi bi-person-plus me-2"></i>Thêm khách hàng';
    document.getElementById('nvcFormLoi').textContent = '';
    modalKhachHang.show();
}

function moModalSua(kh) {
    document.getElementById('nvcFormKhachHang').reset();
    document.getElementById('fId').value = kh.id;
    document.getElementById('fHoTen').value = kh.ho_ten;
    document.getElementById('fUsername').value = kh.username;
    document.getElementById('fSdt').value = kh.sdt || '';
    document.getElementById('fEmail').value = kh.email || '';
    document.getElementById('fMatKhauSao').style.display = 'none';
    document.getElementById('fMatKhauGhiChu').textContent = 'Để trống nếu không muốn đổi mật khẩu.';
    document.getElementById('nvcModalTieuDe').innerHTML = `<i class="bi bi-pencil-square me-2"></i>Sửa khách hàng: ${kh.ho_ten}`;
    document.getElementById('nvcFormLoi').textContent = '';
    modalKhachHang.show();
}

function luuKhachHang(e) {
    e.preventDefault();
    const form = document.getElementById('nvcFormKhachHang');
    const formData = new FormData(form);
    const id = document.getElementById('fId').value;
    formData.append('action', id ? 'sua' : 'them');

    fetch('../ajax/khach_hang_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.thanh_cong) {
                modalKhachHang.hide();
                taiDanhSachKhachHang(id ? undefined : 1);
            } else {
                document.getElementById('nvcFormLoi').textContent = data.thong_bao;
            }
        })
        .catch(() => { document.getElementById('nvcFormLoi').textContent = 'Không thể kết nối máy chủ.'; });
}

function xoaKhachHang(id, hoTen, soVanDon) {
    let canhBao = `Bạn có chắc muốn xóa tài khoản của "${hoTen}"?`;
    if (soVanDon > 0) {
        canhBao += `\n\nCảnh báo: khách hàng này đang có ${soVanDon} vận đơn — toàn bộ sẽ bị xóa theo!`;
    }
    if (!confirm(canhBao)) return;

    const formData = new FormData();
    formData.append('action', 'xoa');
    formData.append('id', id);

    fetch('../ajax/khach_hang_ajax.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.thanh_cong) taiDanhSachKhachHang();
            else alert(data.thong_bao);
        });
}
</script>
