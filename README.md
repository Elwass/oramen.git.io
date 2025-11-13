# Ramen 1 Ordering System

Aplikasi web fullstack pemesanan dan pembayaran restoran ala Ramen 1. Dibangun menggunakan PHP native, MySQL, Bootstrap 5, dan JavaScript (vanilla) dengan dukungan QR code untuk setiap meja.

## Fitur
- Menu digital responsif dengan keranjang belanja real-time.
- Pengiriman pesanan langsung ke dapur/kasir.
- Dashboard kasir untuk memantau status pesanan dan proses pembayaran.
- Manajemen menu (kategori, CRUD item, upload gambar).
- Manajemen meja dengan generator QR code siap cetak.
- Notifikasi suara otomatis ketika pesanan baru masuk (dashboard).

## Prasyarat
- PHP 8+
- MySQL 5.7+ / MariaDB 10+
- Ekstensi PHP: `mysqli`, `gd`
- Web server lokal (XAMPP/Laragon/dll.)
- GD extension sudah aktif secara default pada XAMPP/Laragon modern untuk proses render gambar menu & QR.

## Instalasi
1. **Clone/Salin Proyek**
   ```bash
   git clone https://github.com/your-account/project-ramen1.git
   cd project-ramen1
   ```

2. **Konfigurasi Web Server**
   - Pindahkan folder proyek ke direktori htdocs (contoh: `C:/xampp/htdocs/project-ramen1`).
   - Pastikan folder `uploads/` dapat ditulisi server web.

3. **Import Database**
   - Buka phpMyAdmin.
   - Buat database baru bernama `ramen1`.
   - Import file `database.sql` yang ada di root proyek.

4. **Sesuaikan Koneksi Database**
   - Edit `config.php` bila user/password MySQL berbeda.

5. **Generate Asset Dummy (opsional)**
   - Jalankan `php scripts/generate_sample_images.php` untuk membuat ulang foto placeholder menu.
   - Jalankan `php scripts/generate_sample_qr.php` untuk menyiapkan QR code meja 1-10 secara otomatis.
   - Kedua skrip di atas memakai GD bawaan PHP sehingga bisa dieksekusi dari terminal atau `php.exe` pada Windows.

6. **Generate/Regenerasi QR via Dashboard (opsional)**
   - Login ke dashboard (`http://localhost/project-ramen1/admin/login.php`).
   - Username: `admin`, Password: `admin123`.
   - Buka menu **Meja & QR**, klik **Generate QR Semua Meja** bila ingin membuat ulang dari antarmuka.
   - Cetak QR untuk ditempel di setiap meja.

7. **Akses Aplikasi**
   - **Menu Digital:** `http://localhost/project-ramen1/order.php?table=1`
   - **Dashboard Kasir:** `http://localhost/project-ramen1/admin/login.php`

## Struktur Direktori
```
project-ramen1/
├── admin/
│   ├── includes/        # Layout dan proteksi login
│   ├── menu/            # CRUD menu & kategori
│   ├── orders/          # Detail, update status, struk
│   ├── tables/          # Manajemen meja dan QR code
│   └── login.php        # Form login kasir/admin
├── api/                 # Endpoint JSON (polling notifikasi)
├── assets/              # CSS & JavaScript frontend
├── libs/phpqrcode/      # Library generator QR
├── uploads/             # Penyimpanan gambar menu dan QR
├── order.php            # Halaman pemesanan customer
├── submit_order.php     # Endpoint simpan pesanan
├── config.php           # Koneksi database & helper
├── database.sql         # Struktur + data dummy
└── README.md
```

## Keamanan & Catatan
- Session-based authentication untuk dashboard.
- Gunakan HTTPS di lingkungan produksi.
- Validasi dasar menggunakan prepared statement (`mysqli`).
- Jangan lupa mengganti password default admin pada tabel `users`.
- QR code digambar sepenuhnya menggunakan PHP/GD, jadi tidak memerlukan dependensi eksternal tambahan.

## Dummy Assets
- Folder `uploads/sample/` kosong di repositori (kecuali `.gitkeep`) agar pull request bebas dari file biner. Jalankan `php scripts/generate_sample_images.php` setelah cloning untuk membuat 10 foto placeholder otomatis.
- Folder `uploads/qr/` juga hanya menyertakan `.gitkeep`. Pakai `php scripts/generate_sample_qr.php` atau menu **Generate QR Semua Meja** di dashboard untuk membuat PNG QR meja 1-10.
- Kedua skrip menyimpan file di dalam `uploads/` dan aman dijalankan berulang kali jika ingin menyegarkan placeholder.

## Lisensi
Proyek ini disediakan untuk kebutuhan edukasi/demonstrasi. Silakan modifikasi sesuai kebutuhan bisnis Anda.
