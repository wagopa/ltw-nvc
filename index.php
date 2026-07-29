<?php
/**
 * TRANG CHỦ
 * Nếu đã đăng nhập, hiển thị header với thông tin tài khoản (không tự động
 * chuyển hướng đi nữa) để người dùng vẫn xem được trang giới thiệu công ty.
 */
session_start();
require_once __DIR__ . '/includes/helpers.php';

$nguoiDung = null;
if (isset($_SESSION['nguoi_dung_id'])) {
    $nguoiDung = [
        'ho_ten'  => $_SESSION['ho_ten'],
        'vai_tro' => $_SESSION['vai_tro'],
    ];
}

$trangHienTai = 'trang-chu';
$tieuDeTrang  = 'Trang chủ';
$urlGoc = '';

// Cấu hình nhúng Google Map và Facebook Fanpage — thay bằng địa chỉ/link thật của bạn.
$diaChiCongTyMap = 'Số 1 Đường Vận Chuyển, Quận Tân Bình, TP.HCM';
$urlFacebookFanpage = 'https://www.facebook.com/META'; // TODO: thay bằng link fanpage thật

require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="nvc-hero">
    <div class="container nvc-hero-inner">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="eyebrow"><i class="bi bi-truck"></i> Nhà Vận Chuyển</span>
                <h1>Giao hàng nhanh chóng,<br>theo dõi <span style="color:var(--accent)">minh bạch</span> từng chặng đường.</h1>
                <p class="lead">
                    Chúng tôi kết nối mọi miền đất nước với dịch vụ vận chuyển hàng hóa uy tín —
                    từ nội thành đến liên tỉnh, từ kiện hàng nhỏ đến hàng hóa cồng kềnh.
                </p>
                <div class="nvc-hero-cta d-flex flex-wrap gap-3">
                    <?php if ($nguoiDung): ?>
                        <a href="pages/thong_tin_giao_hang.php" class="btn-accent-lg"><i class="bi bi-box-seam me-1"></i> Xem đơn hàng của tôi</a>
                    <?php else: ?>
                        <a href="login.php" class="btn-accent-lg"><i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập theo dõi đơn hàng</a>
                    <?php endif; ?>
                    <a href="#dich-vu" class="btn-outline-lg">Xem dịch vụ</a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-flex justify-content-center">
                <i class="bi bi-truck nvc-hero-truck"></i>
            </div>
        </div>
    </div>
</section>

<!-- SỐ LIỆU NỔI -->
<div class="container nvc-stats-wrap">
    <div class="row g-3 nvc-reveal-stagger">
        <div class="col-6 col-lg-3">
            <div class="nvc-stat-card">
                <div class="num"><span data-count-to="10000" data-count-suffix="+">0</span></div>
                <div class="label">Đơn hàng đã giao</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="nvc-stat-card">
                <div class="num"><span data-count-to="63">0</span></div>
                <div class="label">Tỉnh thành phủ sóng</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="nvc-stat-card">
                <div class="num"><span data-count-to="98" data-count-suffix="%">0</span></div>
                <div class="label">Đơn hàng đúng hẹn</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="nvc-stat-card">
                <div class="num">24/7</div>
                <div class="label">Hỗ trợ khách hàng</div>
            </div>
        </div>
    </div>
</div>

<!-- DỊCH VỤ -->
<section id="dich-vu" class="nvc-section">
    <div class="container">
        <div class="text-center mx-auto mb-5 nvc-reveal" style="max-width:600px;">
            <div class="nvc-section-eyebrow">Dịch vụ của chúng tôi</div>
            <h2 class="nvc-section-title">Giải pháp vận chuyển cho mọi nhu cầu</h2>
            <p class="nvc-section-sub mx-auto">Từ những kiện hàng nhỏ trong thành phố đến các lô hàng lớn xuyên tỉnh, chúng tôi có phương án phù hợp cho bạn.</p>
        </div>

        <div class="row g-4 nvc-reveal-stagger">
            <div class="col-md-6 col-lg-3">
                <div class="nvc-service-card">
                    <div class="ic"><i class="bi bi-bicycle"></i></div>
                    <h5>Vận chuyển nội thành</h5>
                    <p>Giao nhận nhanh trong ngày cho các đơn hàng nội thành, phù hợp với hàng hóa cần giao gấp.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="nvc-service-card">
                    <div class="ic"><i class="bi bi-truck"></i></div>
                    <h5>Vận chuyển liên tỉnh</h5>
                    <p>Kết nối 63 tỉnh thành với mạng lưới trung chuyển rộng khắp, thời gian giao hàng ổn định.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="nvc-service-card">
                    <div class="ic"><i class="bi bi-box-seam"></i></div>
                    <h5>Hàng hóa cồng kềnh</h5>
                    <p>Giải pháp riêng cho hàng nặng, hàng dễ vỡ với quy trình đóng gói và vận chuyển chuyên biệt.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="nvc-service-card">
                    <div class="ic"><i class="bi bi-building"></i></div>
                    <h5>Dịch vụ kho bãi</h5>
                    <p>Lưu trữ và trung chuyển hàng hóa an toàn tại hệ thống kho đạt chuẩn trên toàn quốc.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- VÌ SAO CHỌN CHÚNG TÔI -->
<section class="nvc-why-section nvc-section-tight">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 nvc-reveal">
                <div class="nvc-section-eyebrow">Vì sao chọn chúng tôi</div>
                <h2 class="nvc-section-title" style="color:#fff;">Cam kết chất lượng trong từng chuyến hàng</h2>
                <p style="color:#94a3b8;">Hơn cả một đơn vị vận chuyển, chúng tôi đồng hành cùng bạn với sự minh bạch và tận tâm trong từng chặng đường.</p>
            </div>
            <div class="col-lg-7">
                <div class="row nvc-reveal-stagger">
                    <div class="col-md-6">
                        <div class="nvc-why-item">
                            <span class="ic"><i class="bi bi-shield-check"></i></span>
                            <div>
                                <h6>An toàn tuyệt đối</h6>
                                <p>Hàng hóa được đóng gói và giám sát chặt chẽ trong suốt hành trình.</p>
                            </div>
                        </div>
                        <div class="nvc-why-item">
                            <span class="ic"><i class="bi bi-clock-history"></i></span>
                            <div>
                                <h6>Giao hàng đúng hẹn</h6>
                                <p>Cam kết thời gian giao nhận rõ ràng ngay từ khi tạo vận đơn.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="nvc-why-item">
                            <span class="ic"><i class="bi bi-geo-alt"></i></span>
                            <div>
                                <h6>Theo dõi minh bạch</h6>
                                <p>Cập nhật trạng thái và lịch sử vận chuyển theo thời gian thực.</p>
                            </div>
                        </div>
                        <div class="nvc-why-item">
                            <span class="ic"><i class="bi bi-headset"></i></span>
                            <div>
                                <h6>Hỗ trợ 24/7</h6>
                                <p>Đội ngũ chăm sóc khách hàng luôn sẵn sàng giải đáp mọi thắc mắc.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- LIÊN HỆ -->
<section id="lien-he" class="nvc-section">
    <div class="container">
        <div class="text-center mx-auto mb-5 nvc-reveal" style="max-width:600px;">
            <div class="nvc-section-eyebrow">Liên hệ</div>
            <h2 class="nvc-section-title">Bạn cần hỗ trợ?</h2>
            <p class="nvc-section-sub mx-auto">Để lại lời nhắn hoặc liên hệ trực tiếp, đội ngũ của chúng tôi sẽ phản hồi trong thời gian sớm nhất.</p>
        </div>

        <div class="row g-4 nvc-reveal-stagger">
            <div class="col-lg-5">
                <div class="nvc-contact-card-info">
                    <div class="item">
                        <span class="ic"><i class="bi bi-geo-alt-fill"></i></span>
                        <div>
                            <div class="lbl">Địa chỉ</div>
                            <div class="val">Số 1 Đường Vận Chuyển, Q. Tân Bình, TP.HCM</div>
                        </div>
                    </div>
                    <div class="item">
                        <span class="ic"><i class="bi bi-telephone-fill"></i></span>
                        <div>
                            <div class="lbl">Hotline</div>
                            <div class="val">1900 6868</div>
                        </div>
                    </div>
                    <div class="item">
                        <span class="ic"><i class="bi bi-envelope-fill"></i></span>
                        <div>
                            <div class="lbl">Email</div>
                            <div class="val">cskh@nhavanchuyen.vn</div>
                        </div>
                    </div>
                    <div class="item mb-0">
                        <span class="ic"><i class="bi bi-clock-fill"></i></span>
                        <div>
                            <div class="lbl">Giờ làm việc</div>
                            <div class="val">24/7 tất cả các ngày trong tuần</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="nvc-contact-form-card">
                    <div id="nvcLienHeThanks" class="nvc-contact-alert-thanks d-none mb-3">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.</span>
                    </div>
                    <form id="nvcFormLienHe">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label>Họ tên</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label>Số điện thoại</label>
                                <input type="text" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label>Nội dung</label>
                                <textarea class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="nvc-btn-accent" style="padding:11px 26px;">
                                    <i class="bi bi-send me-1"></i> Gửi liên hệ
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Google Map + Facebook Fanpage -->
        <div class="row g-4 mt-1 nvc-reveal-stagger">
            <div class="col-lg-7">
                <div class="nvc-embed-card">
                    <div class="nvc-embed-title"><i class="bi bi-geo-alt-fill"></i> Vị trí văn phòng</div>
                    <div class="nvc-map-wrap">
                        <iframe
                            src="https://www.google.com/maps?q=<?= urlencode($diaChiCongTyMap) ?>&output=embed"
                            width="100%" height="100%" style="border:0;"
                            allowfullscreen="" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="nvc-embed-card">
                    <div class="nvc-embed-title"><i class="bi bi-facebook"></i> Theo dõi Fanpage</div>
                    <div class="nvc-fb-wrap">
                        <iframe
                            src="https://www.facebook.com/plugins/page.php?href=<?= urlencode($urlFacebookFanpage) ?>&tabs=timeline&width=380&height=360&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=true&appId"
                            width="100%" height="360" style="border:none;overflow:hidden;"
                            scrolling="no" frameborder="0"
                            allowfullscreen="true"
                            allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$urlGoc = '';
require_once __DIR__ . '/includes/footer.php';
?>

<script>
// Đây là demo giao diện: chưa nối với backend gửi email, chỉ hiển thị lời cảm ơn.
document.getElementById('nvcFormLienHe').addEventListener('submit', function (e) {
    e.preventDefault();
    document.getElementById('nvcLienHeThanks').classList.remove('d-none');
    this.reset();
});
</script>
