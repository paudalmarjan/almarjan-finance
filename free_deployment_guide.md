# Panduan Deploy Laravel Gratis - PAUD Al-Marjan Finance

Ada dua opsi terbaik untuk mendeploy aplikasi Laravel secara gratis. Karena aplikasi ini menggunakan **SQLite**, opsi pertama (**Fly.io**) sangat direkomendasikan karena mereka menyediakan ruang penyimpanan permanen gratis (*Persistent Volume*), sehingga data sekolah tidak akan hilang saat aplikasi melakukan restart atau deploy ulang.

---

## Opsi 1: Deploy ke Fly.io (Rekomendasi Utama untuk SQLite)

Fly.io menawarkan paket gratis (*Free Tier*) yang mencakup 3 VM ringan dan penyimpanan data (*volume*) sebesar 3 GB. Ini adalah cara termudah dan teraman untuk aplikasi berbasis SQLite.

### Langkah 1: Persiapan Akun & CLI
1. Daftar akun gratis di [Fly.io](https://fly.io/). (Catatan: Fly.io memerlukan verifikasi kartu kredit untuk mencegah penyalahgunaan, namun Anda tidak akan ditagih selama berada di batas Free Tier).
2. Instal terminal/command-line tool Flyctl di komputer Anda:
   * **Windows (PowerShell)**:
     ```powershell
     iwr https://fly.io/install.ps1 -useb | iex
     ```
3. Login melalui terminal:
   ```bash
   fly auth login
   ```

### Langkah 2: Inisialisasi Aplikasi
Jalankan perintah berikut di direktori proyek Anda:
```bash
fly launch
```
* Sistem Fly.io akan mendeteksi bahwa ini adalah aplikasi Laravel secara otomatis.
* **PENTING**: Ketika ditanya apakah ingin membuat database MySQL/Postgres, pilih **No** (karena kita akan memakai SQLite bawaan proyek).
* Pilih region server yang paling dekat dengan Indonesia (misalnya **Singapore (sin)**).
* Proses ini akan membuat berkas konfigurasi baru bernama `fly.toml` dan `Dockerfile` di proyek Anda.

### Langkah 3: Konfigurasi Penyimpanan Permanen (Volume) SQLite
Secara bawaan, file di server Fly.io bersifat sementara. Agar file database SQLite (`database.sqlite`) tidak hilang, kita harus membuat *Volume* penyimpanan permanen:

1. Buat volume berukuran 1 GB (gratis):
   ```bash
   fly volumes create almarjan_db_volume --size 1 --region sin
   ```
2. Buka file `fly.toml` di teks editor Anda, lalu tambahkan konfigurasi *mounts* di bagian bawah file agar volume tersebut ditempelkan ke server:
   ```toml
   [[mounts]]
     source = "almarjan_db_volume"
     destination = "/data"
   ```
3. Buka file `.env` produksi Anda atau set Environtment Variables Fly.io agar database diarahkan ke folder `/data` tersebut:
   ```bash
   fly secrets set DB_CONNECTION=sqlite DB_DATABASE=/data/database.sqlite
   ```

### Langkah 4: Set Security Key & Deploy
1. Buat application key Laravel dan daftarkan sebagai rahasia keamanan:
   ```bash
   # Generate key di lokal dulu jika belum ada
   php artisan key:generate --show
   # Set secret di Fly.io menggunakan key yang dihasilkan
   fly secrets set APP_KEY="base64:xxxxxxxxx..."
   ```
2. Mulai deploy aplikasi Anda ke internet:
   ```bash
   fly deploy
   ```
3. Jalankan migrasi database di server Fly.io untuk pertama kali:
   ```bash
   fly ssh console -c "php /var/www/html/artisan migrate --force"
   ```
Aplikasi Anda sekarang sudah aktif dan bisa diakses via tautan `nama-aplikasi.fly.dev`!

---

## Opsi 2: Deploy ke Render + Supabase (Menggunakan PostgreSQL)

Render.com menyediakan hosting gratis (*Web Service*), namun tidak menyediakan penyimpanan permanen gratis. Jika Anda mendeploy SQLite di Render, database akan tereset menjadi kosong setiap kali server melakukan *restart* (biasanya sekali sehari).

Solusinya: Kita ubah database aplikasi ke **PostgreSQL gratis** di **Supabase** atau **Neon.tech**, lalu websitenya di-deploy di Render.

### Langkah 1: Buat Database PostgreSQL Gratis
1. Daftar di [Supabase](https://supabase.com/) atau [Neon.tech](https://neon.tech/).
2. Buat proyek baru dan salin **Connection String** atau detail kredensial database PostgreSQL yang diberikan (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

### Langkah 2: Deploy Website di Render
1. Push kode proyek Anda ke repositori pribadi di **GitHub**.
2. Buat akun gratis di [Render](https://render.com/) dan hubungkan dengan akun GitHub Anda.
3. Klik tombol **New +** -> **Web Service**.
4. Pilih repositori proyek keuangan Al-Marjan Anda.
5. Isi detail konfigurasi berikut:
   * **Name**: `almarjan-finance`
   * **Region**: `Singapore` atau `Oregon`
   * **Branch**: `main`
   * **Runtime**: `PHP`
   * **Build Command**:
     ```bash
     composer install --no-dev --optimize-autoloader && npm install && npm run build
     ```
   * **Start Command**: (Sesuaikan dengan server web default Render)
     ```bash
     # Biasanya Render mendeteksi folder public/ secara otomatis, atau kita gunakan script start
     php artisan migrate --force && heroku-php-apache2 public/
     ```

### Langkah 3: Isi Environment Variables di Render
Di halaman konfigurasi Render dashboard, navigasi ke tab **Environment** dan tambahkan variabel-variabel berikut:
* `APP_KEY` = (Salin nilai APP_KEY dari file `.env` lokal Anda)
* `APP_ENV` = `production`
* `APP_DEBUG` = `false`
* `DB_CONNECTION` = `pgsql`
* `DB_HOST` = (Host database Supabase/Neon Anda)
* `DB_PORT` = `5432`
* `DB_DATABASE` = (Nama database)
* `DB_USERNAME` = (User database)
* `DB_PASSWORD` = (Password database)

Klik **Save Changes**, Render akan otomatis melakukan proses *build* dan aplikasi Anda akan tayang dengan tautan gratis `almarjan-finance.onrender.com`!
