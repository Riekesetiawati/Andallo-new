document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('menu-toggle');
    const menu = document.getElementById('menu-ponsel');
    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            const open = menu.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    document.querySelectorAll('.js-lokasi').forEach((form) => {
        const pesan = form.querySelector('.js-pesan');
        form.querySelector('[data-clear]')?.addEventListener('click', () => {
            form.querySelector('.js-source').value = 'clear';
        });
        form.querySelector('.js-gps')?.addEventListener('click', () => {
            pesan.hidden = false;
            if (!navigator.geolocation) {
                pesan.textContent = 'Peramban tidak mendukung lokasi. Pilih kota secara manual.';
                return;
            }
            pesan.textContent = 'Meminta izin lokasi...';
            navigator.geolocation.getCurrentPosition((pos) => {
                form.querySelector('.js-source').value = 'device';
                form.querySelector('.js-lat').value = pos.coords.latitude;
                form.querySelector('.js-lng').value = pos.coords.longitude;
                form.querySelector('.js-label').value = 'Lokasi perangkat';
                form.submit();
            }, () => {
                pesan.textContent = 'Izin lokasi ditolak. Pilih kota secara manual.';
            }, { enableHighAccuracy: false, timeout: 8000 });
        });
    });

    document.querySelectorAll('form[data-loading]').forEach((item) => {
        item.addEventListener('submit', () => {
            const button = item.querySelector('[type="submit"]');
            if (button) {
                button.disabled = true;
                button.dataset.label = button.textContent;
                button.textContent = 'Memproses...';
            }
        });
    });

    const search = document.getElementById('search-form');
    const results = document.getElementById('search-results');
    if (search && results) {
        let timer;
        const run = () => {
            results.classList.add('is-loading');
            const params = new URLSearchParams(new FormData(search));
            fetch(`${search.action}?${params.toString()}`, {
                headers: { 'X-Andallo-Partial': '1', 'Accept': 'text/html' },
            }).then((response) => {
                if (!response.ok) throw new Error('gagal');
                return response.text();
            }).then((html) => {
                results.innerHTML = html;
                history.replaceState(null, '', `${search.action}?${params.toString()}`);
            }).catch(() => {
                results.innerHTML = '<div class="error-state" role="alert">Hasil pencarian gagal dimuat. Periksa koneksi lalu coba lagi.</div>';
            }).finally(() => results.classList.remove('is-loading'));
        };
        search.addEventListener('change', () => {
            clearTimeout(timer);
            timer = setTimeout(run, 200);
        });
        const keyword = search.querySelector('[name="q"]');
        keyword?.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(run, 350);
        });
        search.addEventListener('submit', (event) => {
            event.preventDefault();
            run();
        });
        document.getElementById('reset-filter')?.addEventListener('click', () => {
            search.querySelectorAll('input').forEach((input) => { input.value = ''; });
            search.querySelectorAll('select').forEach((select) => { select.selectedIndex = 0; });
            run();
        });
    }

    const slotDate = document.getElementById('scheduled_date');
    const slotBox = document.getElementById('slot-list');
    if (slotDate && slotBox) {
        const load = () => {
            slotBox.innerHTML = '<p>Memuat jadwal...</p>';
            fetch(`${slotBox.dataset.url}?date=${encodeURIComponent(slotDate.value)}`, { headers: { 'Accept': 'application/json' } })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.slots?.length) {
                        slotBox.innerHTML = '<p class="empty-state">Tidak ada jadwal kosong pada tanggal ini.</p>';
                        return;
                    }
                    slotBox.innerHTML = data.slots.map((slot) => `
                        <label class="inline">
                            <input type="radio" name="start_time" value="${slot.start}" required>
                            ${slot.start}–${slot.end}
                        </label>
                    `).join('');
                })
                .catch(() => {
                    slotBox.innerHTML = '<p class="error-state">Jadwal gagal dimuat.</p>';
                });
        };
        slotDate.addEventListener('change', load);
    }

    document.querySelectorAll('[data-map]').forEach((node) => {
        if (typeof L === 'undefined') return;
        const lat = parseFloat(node.dataset.lat);
        const lng = parseFloat(node.dataset.lng);
        if (Number.isNaN(lat) || Number.isNaN(lng)) return;
        const map = L.map(node).setView([lat, lng], 14);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);
        L.marker([lat, lng]).addTo(map);
    });
});
