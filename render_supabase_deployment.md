# Panduan Deploy Laravel Gratis: Render + Supabase

Panduan ini menjelaskan langkah demi langkah mendeploy aplikasi keuangan Al-Marjan ke **Render** (sebagai server web gratis) dan **Supabase** (sebagai database PostgreSQL gratis).

Kombinasi ini **100% gratis** dan **tidak memerlukan kartu kredit / kartu debit** sama sekali saat pendaftaran.

---

## Tahap 1: Membuat Database PostgreSQL di Supabase

Supabase menyediakan database PostgreSQL gratis (Free Tier) yang sangat stabil dan andal.

1. **Daftar / Login**: Masuk ke [Supabase](https://supabase.com/) dan masuk menggunakan akun GitHub atau Email Anda.
2. **Buat Proyek Baru**:
   * Klik tombol **New Project**.
   * Pilih nama organisasi Anda.
   * **Name**: `almarjan-finance-db`
   * **Database Password**: Ketik kata sandi yang aman dan **catat password ini** karena akan digunakan nanti.
   * **Region**: Pilih `Singapore (ap-southeast-1)` (wilayah paling dekat dengan Indonesia agar koneksi cepat).
   * **Pricing Plan**: Pilih **Free**.
   * Klik **Create new project** dan tunggu 2-3 menit hingga database siap.
3. **Ambil Kredensial Database**:
   * Setelah proyek siap, klik ikon **Settings (Gerigi)** di menu kiri bawah.
   * Pilih menu **Database**.
   * Gulir ke bawah ke bagian **Connection Parameters**. Catat nilai berikut:
     * **Host**: `db.xxxxxxxxxx.supabase.co`
     * **Port**: `5432`
     * **Database Name**: `postgres`
     * **User**: `postgres`
     * **Password**: (Password yang Anda buat sebelumnya)

---

## Tahap 2: Mengunggah Kode ke GitHub (Private Repository)

Render memerlukan kode Anda berada di GitHub agar bisa di-deploy secara otomatis.

1. Buat repositori baru di [GitHub](https://github.com/) bersifat **Private** (Pribadi) agar data keamanan Anda tidak terlihat publik.
2. Push seluruh folder proyek `almarjan-finance` Anda ke repositori GitHub tersebut.

---

## Tahap 3: Deploy Server Laravel di Render.com

Render akan bertindak sebagai server web yang menjalankan aplikasi PHP/Laravel Anda.

1. **Daftar / Login**: Masuk ke [Render](https://render.com/) menggunakan akun **GitHub** Anda.
2. **Buat Web Service Baru**:
   * Klik tombol **New +** di pojok kanan atas, lalu pilih **Web Service**.
   * Pilih opsi **Build and deploy from a Git repository**.
   * Hubungkan repositori GitHub pribadi Anda (`almarjan-finance`) yang baru saja Anda buat.
3. **Konfigurasi Web Service**:
   * **Name**: `almarjan-finance`
   * **Region**: Pilih **Singapore** (paling dekat dengan Indonesia).
   * **Branch**: `main` (atau branch utama Anda).
   * **Runtime**: Pilih **PHP**.
   * **Build Command**:
     ```bash
     composer install --no-dev --optimize-autoloader && npm install && npm run build
     ```
   * **Start Command**: (Gunakan Apache bawaan Render untuk PHP)
     ```bash
     heroku-php-apache2 public/
     ```
     *(Catatan: Perintah di atas akan mengarahkan server Apache Render ke folder `public/` Laravel secara otomatis).*
   * **Instance Type**: Pilih **Free** (Gratis).

---

## Tahap 4: Mengisi Environment Variables di Render

Aplikasi Laravel memerlukan konfigurasi lingkungan (yang biasanya ada di file `.env`) untuk terhubung ke database Supabase dan mengaktifkan enkripsi keamanan.

1. Di dashboard Render proyek Anda, masuk ke menu **Environment** di sisi kiri.
2. Klik **Add Environment Variable** dan masukkan kunci & nilai berikut satu per satu:

| Key | Value | Keterangan |
| :--- | :--- | :--- |
| `APP_KEY` | `base64:xxxxxx...` | Salin persis dari file `.env` lokal Anda |
| `APP_ENV` | `production` | Menandakan aplikasi berjalan di produksi |
| `APP_DEBUG` | `false` | Mematikan debug error agar aman dari hacker |
| `DB_CONNECTION` | `pgsql` | Mengubah driver database ke PostgreSQL |
| `DB_HOST` | *(Host dari Supabase)* | Contoh: `db.xxxxxxxx.supabase.co` |
| `DB_PORT` | `5432` | Port PostgreSQL standar |
| `DB_DATABASE` | `postgres` | Nama database bawaan Supabase |
| `DB_USERNAME` | `postgres` | User bawaan Supabase |
| `DB_PASSWORD` | *(Password Supabase Anda)* | Sandi yang Anda buat di Tahap 1 |

3. Klik **Save Changes**. Render akan otomatis melakukan restart dan memulai proses pembangunan (*building*) aplikasi.

---

## Tahap 5: Menjalankan Migrasi Database

Setelah Render selesai mem-build web Anda (statusnya berubah menjadi *Live*), Anda harus membuat tabel-tabel di database Supabase menggunakan perintah migrasi Laravel:

1. Di dashboard Render, masuk ke menu **Shell** di menu navigasi sebelah kiri.
2. Tunggu terminal shell terhubung, lalu ketik perintah berikut dan tekan Enter:
   ```bash
   php artisan migrate --force
   ```
3. Proses ini akan mengeksekusi semua file migrasi ke database Supabase Anda. Tabel-tabel keuangan dan siswa sekarang sudah siap.

Aplikasi Anda kini sudah selesai di-deploy dan dapat diakses dari mana saja melalui tautan gratis `https://almarjan-finance.onrender.com`!
