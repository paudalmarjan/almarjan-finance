# Sistem Manajemen Keuangan PAUD Al-Marjan

Aplikasi berbasis web sederhana untuk mengelola pencatatan keuangan (penerimaan SPP bulanan, biaya tahunan, pengeluaran kas operasional, administrasi siswa, dan ekspor berkas LPJ) di PAUD Al-Marjan.

Aplikasi ini dibangun menggunakan **Laravel** (Backend), **Bootstrap 5 & Vanilla CSS** (Frontend), serta **Tom Select & AlpineJS** (Peningkatan UI/UX).

---

## Fitur Utama

1. **Penerimaan & SPP Bulanan**:
   * Pencatatan pembayaran SPP bulanan (terkonsolidasi SPP + Komite).
   * Fitur **Alokasi Pembayaran Cepat (FIFO)** untuk mendistribusikan cicilan biaya tahunan siswa dari atas ke bawah secara otomatis.
   * Pencarian siswa instan menggunakan fitur autocomplete pencarian.

2. **Administrasi Data Siswa**:
   * Filter lengkap berdasarkan **Jenjang (Level)**, **Kelompok Kelas**, dan **Tipe Pendaftaran (Siswa Baru / Siswa Lama)**.
   * Mendukung pencatatan **Nama Panggilan (Nickname)** untuk mempermudah pencarian nama akrab siswa oleh guru.
   * Fitur **Impor Massal data siswa via Excel** (disertai unduhan template resmi).
   * Kustomisasi pengecualian tagihan tahunan siswa secara mandiri.

3. **Pengeluaran Kas Sekolah**:
   * Pencatatan pengeluaran kas operasional per kategori.
   * Kewajiban menyertakan unggahan bukti fisik transaksi (foto/PDF kuitansi).

4. **Laporan & LPJ**:
   * Grafik tren arus kas masuk/keluar bulanan secara visual (ApexCharts).
   * Laporan daftar siswa menunggak (tunggakan aktif) seketika.
   * Fitur **Unduh LPJ** berupa ekspor rekap pengeluaran bulanan beserta arsip berkas bukti fisik (zip file).

5. **Akses Cepat (UX)**:
   * **Global Search (`Ctrl + K`)**: Buka popup pencarian dari halaman mana saja untuk langsung membayar tagihan atau mengedit profil siswa.
   * **Badges Status Tunggakan**: Menampilkan status keuangan siswa secara visual langsung pada tabel daftar siswa (Lunas / nominal total tunggakan).

---

## Cara Menjalankan Aplikasi di Komputer Lokal

### 1. Persyaratan Sistem
Pastikan komputer Anda sudah terpasang:
* PHP >= 8.2 (dengan ekstensi SQLite dan ZIP aktif)
* Composer
* Node.js & NPM
* Git

### 2. Langkah Instalasi
1. Clone repositori ini ke komputer Anda:
   ```bash
   git clone https://github.com/username/repository-anda.git
   cd repository-anda
   ```
2. Instal semua dependensi PHP & Javascript:
   ```bash
   composer install
   ```
   ```bash
   npm install
   ```
3. Salin berkas `.env.example` menjadi `.env`:
   ```bash
   copy .env.example .env
   ```
4. Buat kunci keamanan aplikasi (*Application Key*):
   ```bash
   php artisan key:generate
   ```
5. Buat berkas database SQLite kosong (bawaan lokal):
   * Buat file bernama `database.sqlite` di dalam folder `database/`.
6. Jalankan migrasi tabel beserta data contoh awal (*seed*):
   ```bash
   php artisan migrate --seed
   ```
7. Kompilasi aset frontend dan nyalakan server lokal:
   * **Terminal 1** (untuk server Laravel):
     ```bash
     php artisan serve
     ```
   * **Terminal 2** (untuk kompilasi aset CSS/JS):
     ```bash
     npm run dev
     ```

Buka browser Anda dan akses halaman `http://127.0.0.1:8000`. 
* **User default admin**: `admin@almarjan.sch.id` (Password: `password`)
* **User default guru**: `guru@almarjan.sch.id` (Password: `password`)

---

## Panduan Deployment Ke Internet

Untuk mendeploy aplikasi ini ke internet secara **100% gratis** dan **tanpa memerlukan kartu kredit** sama sekali, Anda bisa merujuk ke panduan khusus berikut:

* **[Panduan Deploy Render & Supabase](render_supabase_deployment.md)** (Rekomendasi deploy gratis termudah).
* **[Panduan Deploy Fly.io](fly_supabase_deployment.md)** (Rekomendasi deploy gratis dengan performa tercepat).

---

## Lisensi
Aplikasi ini berlisensi **MIT License** bebas digunakan dan dikembangkan untuk keperluan dunia pendidikan.
