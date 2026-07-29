</main>

<footer class="nvc-footer">
    <div class="container py-5">
        <div class="row g-4">

            <div class="col-lg-4 col-md-6">
                <div class="nvc-footer-brand mb-3">
                    <i class="bi bi-truck me-2" style="color:var(--accent);"></i>Nhà Vận Chuyển
                </div>
                <p class="small mb-3">
                    Đơn vị vận chuyển hàng hóa uy tín, kết nối mọi miền đất nước với dịch vụ
                    giao nhận nhanh chóng, an toàn và minh bạch trong từng chặng đường.
                </p>
                <div class="nvc-social">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-youtube"></i></a>
                    <a href="#"><i class="bi bi-tiktok"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h6>Liên kết</h6>
                <ul>
                    <li><a href="<?= $urlGoc ?>index.php">Trang chủ</a></li>
                    <li><a href="<?= $urlGoc ?>pages/thong_tin_giao_hang.php">Thông tin giao hàng</a></li>
                    <li><a href="<?= $urlGoc ?>index.php#dich-vu">Bảng giá dịch vụ</a></li>
                    <li><a href="<?= $urlGoc ?>index.php#lien-he">Câu hỏi thường gặp</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6>Dịch vụ</h6>
                <ul>
                    <li><a href="<?= $urlGoc ?>index.php#dich-vu">Vận chuyển nội thành</a></li>
                    <li><a href="<?= $urlGoc ?>index.php#dich-vu">Vận chuyển liên tỉnh</a></li>
                    <li><a href="<?= $urlGoc ?>index.php#dich-vu">Vận chuyển hàng hóa nặng</a></li>
                    <li><a href="<?= $urlGoc ?>index.php#dich-vu">Dịch vụ kho bãi</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6>Liên hệ</h6>
                <div class="nvc-contact-item">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>Số 1 Đường Vận Chuyển, Q. Tân Bình, TP.HCM</span>
                </div>
                <div class="nvc-contact-item">
                    <i class="bi bi-telephone-fill"></i>
                    <span>1900 6868</span>
                </div>
                <div class="nvc-contact-item">
                    <i class="bi bi-envelope-fill"></i>
                    <span>cskh@nhavanchuyen.vn</span>
                </div>
            </div>

        </div>
    </div>

    <div class="nvc-footer-bottom">
        <div class="container d-flex flex-wrap justify-content-between gap-2">
            <span>&copy; <?= date('Y') ?> Nhà Vận Chuyển. Dự án thực hành PHP thuần.</span>
            <span>
                <a href="#">Chính sách bảo mật</a> &middot; <a href="#">Điều khoản dịch vụ</a>
            </span>
        </div>
    </div>
</footer>

<!--
    Widget liên hệ nhanh (nổi góc dưới màn hình).
    TODO: thay số điện thoại demo bên dưới bằng số Zalo thật của bạn.
-->
<?php $zaloSdtDemo = '0900000000'; ?>
<div class="nvc-quick-contact">
    <a href="https://zalo.me/<?= $zaloSdtDemo ?>" target="_blank" rel="noopener"
       class="nvc-quick-btn nvc-quick-zalo" title="Chat qua Zalo">
        <span class="nvc-quick-label">Chat qua Zalo</span>
        <svg viewBox="0 0 48 48" width="26" height="26" style="width:26px;height:26px;" fill="#fff">
            <path d="M24 4C12.95 4 4 12.06 4 22c0 5.6 2.86 10.6 7.33 13.9-.24 1.9-1.13 4.3-2.03 5.9-.2.36.13.78.52.68 3.05-.78 6.1-2.4 7.9-3.5A22.9 22.9 0 0 0 24 40c11.05 0 20-8.06 20-18S35.05 4 24 4z"/>
        </svg>
    </a>
    <a href="tel:19006868" class="nvc-quick-btn nvc-quick-call" title="Gọi ngay: 1900 6868">
        <span class="nvc-quick-label">Gọi ngay: 1900 6868</span>
        <i class="bi bi-telephone-fill"></i>
    </a>
</div>

<!-- Nút cuộn lên đầu trang -->
<button type="button" id="nvcScrollTop" class="nvc-scroll-top" title="Lên đầu trang" aria-label="Lên đầu trang">
    <i class="bi bi-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $urlGoc ?>assets/js/main.js"></script>
</body>
</html>
