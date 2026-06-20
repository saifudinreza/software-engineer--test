# Answers

## 1. Waktu pengerjaan coding test

**Berapa lama yang saya habiskan:**
Sekitar beberapa jam fokus. Waktu tersebut saya pakai untuk:
- Memahami struktur JSON feed (`submission.json`) yang bersifat dinamis — tiap field punya `type` berbeda (`radio_button`, `checkbox`, `text` dengan `sub_type` date/amount, `long_text`).
- Membangun backend Laravel: skema database ternormalisasi + migration, importer JSON, dan REST API.
- Membuat frontend terpisah (Vite, vanilla JS) yang me-render form dari API dan submit balik.
- Menulis unit/feature test dan merapikan dokumentasi.

**Yang sudah saya selesaikan dalam waktu tersebut:**
- Backend Laravel: konsumsi feed → simpan ke DB → expose via API (`GET /api/form`, `POST /api/submissions`, `GET /api/submissions/{id}`).
- Database ternormalisasi (`form_sections` → `form_fields` → `field_options`, plus `submissions` → `submission_values`) dengan index pada foreign key, `type`, dan `position`.
- Pertimbangan **memory**: import JSON memakai *streaming parser* (`halaxa/json-machine`) sehingga seluruh file tidak pernah dimuat sekaligus ke memori; insert dilakukan secara bulk per-section.
- Frontend terpisah yang memanggil API dan submit form, dengan CORS diaktifkan di backend.
- Nice-to-have: 9 feature test (import, idempotensi, endpoint, validasi) dan migration.

**Kalau saya punya waktu lebih, yang akan saya tambahkan:**
1. **Validasi "required" yang lebih kaya** — saat ini validasi memastikan field id & option id valid serta format tanggal benar. Saya akan menambah aturan required/wajib-isi per field (jika feed punya penanda required), plus validasi `amount` sebagai numerik yang lebih ketat.
2. **Versioning definisi form** — menyimpan setiap submission dengan referensi ke versi definisi form saat itu, supaya perubahan form di masa depan tidak merusak interpretasi submission lama.
3. **Pagination & endpoint list submissions** — `GET /api/submissions` dengan pagination dan filter, penting untuk skala data besar (sekaligus menjaga memory di sisi server).
4. **Dukungan `sub_payloads` (nested fields)** — struktur JSON menyediakan `sub_payloads` (saat ini kosong di data contoh). Saya akan membuat skema & rendering rekursif agar field bersarang ikut tertangani.
5. **Dukungan upload `supporting_file`** — feed punya slot `supporting_file`; saya akan menambahkan penyimpanan file (mis. ke disk/storage) beserta validasinya.
6. **CI pipeline** — menjalankan `php artisan test` + lint otomatis di GitHub Actions.
7. **API documentation** — OpenAPI/Swagger atau minimal koleksi Postman agar kontrak API jelas bagi konsumen frontend.

**Catatan keterbatasan waktu:**
Inti requirement sudah terpenuhi end-to-end (sudah diuji: submit dari UI berhasil menyimpan ke DB dan mengembalikan submission id). Poin-poin di atas adalah peningkatan bertahap yang saya prioritaskan berdasarkan dampak: keandalan data (versioning, required) lebih dulu, lalu skalabilitas (pagination), baru kelengkapan fitur (nested fields, file upload).

---

## 2. Debugging performance issue di production

**Cara saya nge-track masalah performance di production:**

Pendekatan saya selalu **ukur dulu, jangan menebak** — perbaikan tanpa data sering salah sasaran. Urutannya:

1. **Reproduksi & tentukan scope.**
   Cari tahu: lambat di endpoint mana, sejak kapan, untuk semua user atau sebagian, dan apakah berkorelasi dengan deploy/perubahan data tertentu. Tentukan baseline & target (mis. p95 latency).

2. **Lihat observability/metrics dulu, bukan langsung baca kode.**
   - **Metrics**: latency (p50/p95/p99), throughput, error rate, CPU/memory, koneksi DB. Tools seperti Grafana/Prometheus, New Relic, Datadog, atau Laravel Telescope/Pulse untuk app Laravel.
   - **Logs**: cari error, timeout, dan request yang lambat (structured logging dengan request id memudahkan tracing).
   - **APM/Tracing** (distributed tracing): memecah satu request jadi span — berapa waktu di app, di query DB, di external API. Ini cara tercepat menemukan bottleneck.

3. **Cek tersangka paling umum** (berdasarkan pengalaman, mayoritas masalah ada di sini):
   - **Database**: query lambat & **N+1 query** (paling sering di app ORM seperti Eloquent), missing index, full table scan. Saya pakai `EXPLAIN`/`EXPLAIN ANALYZE`, slow query log, dan di Laravel bisa `DB::listen()`/Telescope untuk menghitung jumlah & durasi query per request.
   - **Memory**: memuat dataset besar sekaligus ke memori. (Di project ini sudah saya antisipasi dengan streaming parser saat import, dan menghindari `with()` yang berlebihan — endpoint submission hanya me-load field yang relevan.)
   - **N+1 / over-fetching** di API: eager-load yang tepat.
   - **External calls**: API pihak ketiga yang lambat tanpa timeout/caching.
   - **Caching**: tidak adanya cache untuk data yang jarang berubah (mis. definisi form).

4. **Perbaiki yang paling berdampak dulu, lalu ukur ulang.**
   Contoh tindakan: tambah index yang sesuai, perbaiki N+1 dengan eager loading, tambahkan caching (Redis) untuk data read-heavy, pagination untuk endpoint list, queue untuk pekerjaan berat agar tidak memblokir request, dan connection pooling. Setelah tiap perubahan, **bandingkan metrics dengan baseline** untuk memastikan benar-benar membaik dan tidak menimbulkan regresi.

5. **Cegah berulang.**
   Tambahkan alerting pada metrics (mis. p95 > threshold), load/performance test sebelum rilis, dan—kalau perlu—test yang mengecek jumlah query agar N+1 tidak masuk lagi.

**Kaitan dengan solusi ini:**
Beberapa keputusan di project ini memang berangkat dari kesadaran performa/memory:
- Skema **ternormalisasi + index** pada foreign key, `type`, dan `position` agar query cepat dan tidak full scan.
- Import via **streaming** agar konsumsi memori tetap rendah berapa pun ukuran feed.
- Endpoint submission **hanya me-load field yang dipakai** (`whereIn` + eager load `options`), bukan seluruh definisi form, untuk menghindari over-fetching.

**Apakah saya pernah mengalami/mengerjakan langsung:**
Ya, pola yang paling sering saya temui adalah **N+1 query** dan **missing index** — endpoint terasa cepat saat data sedikit lalu melambat drastis saat data tumbuh. Penyelesaiannya khas: identifikasi lewat profiling/slow query log atau jumlah query per request, lalu perbaiki dengan eager loading + index yang tepat, dan untuk endpoint list ditambah pagination. Prinsip yang saya pegang konsisten: **ukur → cari bottleneck terbesar → perbaiki satu per satu → ukur lagi.**
