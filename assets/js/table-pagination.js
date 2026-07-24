/**
 * table-pagination.js — Si-TANGKAL Admin
 *
 * Menambahkan pagination client-side otomatis ke semua tabel data di halaman
 * Admin (Data Pohon, Monitoring, Pengajuan, Users, Permohonan Bibit, Stok
 * Bibit, dll) tanpa perlu mengubah query PHP di masing-masing halaman.
 *
 * Cara kerja: script ini mencari setiap <table class="table"> yang berada di
 * dalam ".card-body" (bukan di dalam modal), lalu menyembunyikan/menampilkan
 * baris <tr> di <tbody> sesuai halaman aktif. Kontrol (jumlah baris per
 * halaman + tombol navigasi) disisipkan otomatis tepat di bawah tabel.
 *
 * Cukup di-include sekali di layouts/footer.php — otomatis aktif di semua
 * halaman admin yang punya tabel data.
 */
(function () {
    'use strict';

    const PAGE_SIZE_OPTIONS = [10, 25, 50, 100];
    const DEFAULT_PAGE_SIZE = 10;
    const MAX_PAGE_BUTTONS  = 5; // jumlah tombol nomor halaman yang tampil sekaligus

    function init() {
        const tables = document.querySelectorAll('.card-body table.table');

        tables.forEach((table) => {
            // Lewati tabel yang ada di dalam modal (mis. tabel referensi di form tambah/edit)
            if (table.closest('.modal')) return;
            // Lewati kalau sudah pernah di-setup (jaga-jaga script kepanggil 2x)
            if (table.dataset.paginated === '1') return;

            setupPagination(table);
        });
    }

    function setupPagination(table) {
        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const allRows = Array.from(tbody.querySelectorAll(':scope > tr'));

        // Deteksi baris placeholder "Belum ada data" / "Tidak ditemukan" —
        // yaitu satu-satunya baris yang selnya pakai colspan.
        const dataRows = allRows.filter((tr) => !tr.querySelector('td[colspan]'));

        // Kalau kosong atau cuma dikit, tak perlu pagination.
        if (dataRows.length === 0 || dataRows.length <= PAGE_SIZE_OPTIONS[0]) return;

        table.dataset.paginated = '1';

        const wrapper = table.closest('.table-responsive') || table.parentElement;

        const state = {
            page: 1,
            pageSize: DEFAULT_PAGE_SIZE,
        };

        const controls = buildControls();
        const nav = controls.nav;
        wrapper.insertAdjacentElement('afterend', controls.root);

        function totalPages() {
            if (state.pageSize === 'all') return 1;
            return Math.max(1, Math.ceil(dataRows.length / state.pageSize));
        }

        function render() {
            const pages = totalPages();
            if (state.page > pages) state.page = pages;
            if (state.page < 1) state.page = 1;

            let start = 0;
            let end = dataRows.length;
            if (state.pageSize !== 'all') {
                start = (state.page - 1) * state.pageSize;
                end = Math.min(start + state.pageSize, dataRows.length);
            }

            dataRows.forEach((tr, i) => {
                tr.style.display = (i >= start && i < end) ? '' : 'none';
            });

            controls.info.textContent = dataRows.length === 0
                ? 'Tidak ada data'
                : `Menampilkan ${start + 1}\u2013${end} dari ${dataRows.length} data`;

            renderPageButtons(pages);
        }

        function renderPageButtons(pages) {
            // Hapus tombol nomor halaman lama (di antara tombol prev & next)
            Array.from(nav.querySelectorAll('li[data-page-num]')).forEach((el) => el.remove());

            controls.prevBtn.disabled = state.page <= 1;
            controls.nextBtn.disabled = state.page >= pages || state.pageSize === 'all';

            if (state.pageSize === 'all' || pages <= 1) return;

            let from = Math.max(1, state.page - Math.floor(MAX_PAGE_BUTTONS / 2));
            let to = Math.min(pages, from + MAX_PAGE_BUTTONS - 1);
            from = Math.max(1, to - MAX_PAGE_BUTTONS + 1);

            const items = [];
            if (from > 1) {
                items.push(pageItem(1));
                if (from > 2) items.push(ellipsis());
            }
            for (let p = from; p <= to; p++) {
                items.push(pageItem(p));
            }
            if (to < pages) {
                if (to < pages - 1) items.push(ellipsis());
                items.push(pageItem(pages));
            }

            items.forEach((li) => nav.insertBefore(li, controls.nextLi));
        }

        function pageItem(p) {
            const li = document.createElement('li');
            li.className = 'page-item' + (p === state.page ? ' active' : '');
            li.setAttribute('data-page-num', '1');
            const a = document.createElement('a');
            a.href = '#';
            a.className = 'page-link';
            a.textContent = p;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                state.page = p;
                render();
            });
            li.appendChild(a);
            return li;
        }

        function ellipsis() {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.setAttribute('data-page-num', '1');
            li.innerHTML = '<span class="page-link">&hellip;</span>';
            return li;
        }

        controls.prevBtn.addEventListener('click', () => {
            if (state.page > 1) {
                state.page--;
                render();
            }
        });
        controls.nextBtn.addEventListener('click', () => {
            if (state.page < totalPages()) {
                state.page++;
                render();
            }
        });
        controls.sizeSelect.addEventListener('change', () => {
            const val = controls.sizeSelect.value;
            state.pageSize = val === 'all' ? 'all' : parseInt(val, 10);
            state.page = 1;
            render();
        });

        render();
    }

    function buildControls() {
        const root = document.createElement('div');
        root.className = 'd-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 border-top table-pagination-controls';

        const info = document.createElement('div');
        info.className = 'text-muted small table-pagination-info';
        root.appendChild(info);

        const right = document.createElement('div');
        right.className = 'd-flex align-items-center gap-3 flex-wrap';

        const sizeWrap = document.createElement('div');
        sizeWrap.className = 'd-flex align-items-center gap-2';
        const sizeLabel = document.createElement('label');
        sizeLabel.className = 'small text-muted mb-0';
        sizeLabel.textContent = 'Tampilkan';
        const sizeSelect = document.createElement('select');
        sizeSelect.className = 'form-select form-select-sm';
        sizeSelect.style.width = 'auto';
        PAGE_SIZE_OPTIONS.forEach((n) => {
            const opt = document.createElement('option');
            opt.value = String(n);
            opt.textContent = n;
            if (n === DEFAULT_PAGE_SIZE) opt.selected = true;
            sizeSelect.appendChild(opt);
        });
        const optAll = document.createElement('option');
        optAll.value = 'all';
        optAll.textContent = 'Semua';
        sizeSelect.appendChild(optAll);
        sizeWrap.appendChild(sizeLabel);
        sizeWrap.appendChild(sizeSelect);
        const sizeUnit = document.createElement('span');
        sizeUnit.className = 'small text-muted';
        sizeUnit.textContent = 'baris';
        sizeWrap.appendChild(sizeUnit);

        const nav = document.createElement('ul');
        nav.className = 'pagination pagination-sm mb-0';

        const prevLi = document.createElement('li');
        prevLi.className = 'page-item';
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'page-link';
        prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
        prevLi.appendChild(prevBtn);

        const nextLi = document.createElement('li');
        nextLi.className = 'page-item';
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'page-link';
        nextBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
        nextLi.appendChild(nextBtn);

        nav.appendChild(prevLi);
        nav.appendChild(nextLi);

        right.appendChild(sizeWrap);
        right.appendChild(nav);
        root.appendChild(right);

        return { root, info, sizeSelect, prevBtn, nextBtn, nav, nextLi };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();