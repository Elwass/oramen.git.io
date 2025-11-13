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
- Opsional: Python 3 dengan paket `qrcode[pil]` untuk generator QR (jalankan `pip install qrcode[pil]`).

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

5. **Generate QR Code (opsional pertama kali)**
   - Login ke dashboard (`http://localhost/project-ramen1/admin/login.php`).
   - Username: `admin`, Password: `admin123`.
   - Buka menu **Meja & QR**, klik **Generate QR Semua Meja**.
   - Cetak QR untuk ditempel di setiap meja.

6. **Akses Aplikasi**
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
- Jika generator QR tidak berjalan, pastikan Python + modul `qrcode` telah terpasang atau ganti dengan solusi lain sesuai kebutuhan.

## Lisensi
Proyek ini disediakan untuk kebutuhan edukasi/demonstrasi. Silakan modifikasi sesuai kebutuhan bisnis Anda.
