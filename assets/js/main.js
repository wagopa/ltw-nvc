/**
 * main.js — Các hiệu ứng tương tác dùng chung cho trang công khai
 * (scroll reveal, đếm số chạy, navbar đổi kiểu khi cuộn, nút lên đầu trang).
 * An toàn khi phần tử không tồn tại trên trang (mỗi hàm tự kiểm tra trước khi chạy).
 */
(function () {
    'use strict';

    const giamChuyenDong = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Navbar đổi kiểu khi cuộn ---------- */
    function khoiTaoNavbarCuon() {
        const navbar = document.querySelector('.nvc-navbar');
        if (!navbar) return;

        function capNhat() {
            navbar.classList.toggle('scrolled', window.scrollY > 20);
        }
        window.addEventListener('scroll', capNhat, { passive: true });
        capNhat();
    }

    /* ---------- Xuất hiện mượt khi cuộn tới (scroll reveal) ---------- */
    function khoiTaoScrollReveal() {
        const cacPhanTu = document.querySelectorAll('.nvc-reveal, .nvc-reveal-stagger');
        if (!cacPhanTu.length) return;

        if (giamChuyenDong || !('IntersectionObserver' in window)) {
            cacPhanTu.forEach(el => el.classList.add('nvc-reveal-in'));
            return;
        }

        const quanSat = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('nvc-reveal-in');
                    quanSat.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });

        cacPhanTu.forEach(el => quanSat.observe(el));
    }

    /* ---------- Đếm số chạy cho các thẻ số liệu ---------- */
    function khoiTaoDemSo() {
        const cacSo = document.querySelectorAll('[data-count-to]');
        if (!cacSo.length) return;

        function chayDem(el) {
            const dich = parseInt(el.dataset.countTo, 10);
            const hauTo = el.dataset.countSuffix || '';
            if (isNaN(dich)) return;

            if (giamChuyenDong) {
                el.textContent = dich.toLocaleString('vi-VN') + hauTo;
                return;
            }

            const thoiLuong = 1400;
            const batDau = performance.now();

            function ve(hienTai) {
                const tiLe = Math.min((hienTai - batDau) / thoiLuong, 1);
                const easeOut = 1 - Math.pow(1 - tiLe, 3);
                const giaTri = Math.round(dich * easeOut);
                el.textContent = giaTri.toLocaleString('vi-VN') + hauTo;
                if (tiLe < 1) requestAnimationFrame(ve);
            }
            requestAnimationFrame(ve);
        }

        if (!('IntersectionObserver' in window)) {
            cacSo.forEach(chayDem);
            return;
        }

        const quanSat = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    chayDem(entry.target);
                    quanSat.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        cacSo.forEach(el => quanSat.observe(el));
    }

    /* ---------- Nút cuộn lên đầu trang ---------- */
    function khoiTaoNutLenDau() {
        const nut = document.getElementById('nvcScrollTop');
        if (!nut) return;

        function capNhat() {
            nut.classList.toggle('show', window.scrollY > 500);
        }
        window.addEventListener('scroll', capNhat, { passive: true });
        capNhat();

        nut.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: giamChuyenDong ? 'auto' : 'smooth' });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        khoiTaoNavbarCuon();
        khoiTaoScrollReveal();
        khoiTaoDemSo();
        khoiTaoNutLenDau();
    });
})();
