# Employee Attendance Recap System

## Description
Sistem ini digunakan untuk merekap presensi pegawai harian dan menghasilkan laporan bulanan.  
Project ini menggunakan PHP, MySQL, dan dependency diatur dengan Composer.

## Features
- Rekap presensi harian pegawai
- Export data ke Excel
- Laporan bulanan

## Installation
1. Clone repository:
   ```bash
   git clone https://github.com/syahputrafathan/employee-attendance-recap-system.git
2. Masuk ke folder project:
   ```bash
   cd employee-attendance-recap-system
4. Install dependency dengan Composer:
   ```bash
   composer install
6. Atur koneksi database di file konfigurasi (db.php)
7. Jalankan project menggunakan Laragon / XAMPP
   ```bash
   Buka project di browser, misal: http://localhost/employee-attendance-recap-system/

## Usage
- Gunakan menu untuk rekap presensi, export, dan laporan.
- Export data ke Excel untuk laporan harian / bulanan
- Pastikan database sudah terkoneksi dengan benar

## Notes
- Folder vendor/ tidak termasuk di repository. Jalankan composer install agar sistem berjalan.
- Jangan upload file credentials.json, .env, atau file sensitive lainnya ke GitHub.
- Pastikan PHP versi 7.4+ dan MySQL tersedia di environment kamu.

## Screenshots
<img width="1900" height="1095" alt="Screenshot 2025-12-14 211818" src="https://github.com/user-attachments/assets/b5053802-831f-4717-a634-678641652843" />

## Author
Fathansyah Putra  
[GitHub](https://github.com/syahputrafathan)
