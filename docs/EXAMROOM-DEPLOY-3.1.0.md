# Deploy web dan API ExamRoom 3.1.0

Versi ini memperbarui UI Teacher Room, aktivasi ulang ujian dengan kode masuk baru, serta kode keluar dan kode lanjut yang berbeda untuk setiap ujian. Android 3.1.0 membutuhkan API versi ini.

Pada folder aplikasi Laravel hosting (folder berisi artisan):

```sh
git pull --ff-only origin main
php artisan down --retry=60
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

Jika sudah menjalankan pull melalui cPanel Git, lanjutkan dari perintah artisan. Gunakan PHP 8.2+ sesuai konfigurasi hosting. Tidak ada perubahan dependency atau kebutuhan build npm.

Pertahankan `.env`, APP_KEY, database dan storage produksi. Siapkan backup database hosting sebelum migrasi. Jangan memakai database maupun APP_KEY dari komputer pengembangan.

Migrasi `2026_10_27_000001_exam_action_codes` menambahkan kode pengawas terenkripsi dan hash verifikasi untuk setiap sesi existing, serta menghapus kewajiban perangkat terkelola. PIN global lama tidak lagi dipakai. Guru dapat melihat kode pengawas baru melalui detail ujian. Riwayat peserta, kode masuk existing dan waktu ujian dipertahankan oleh migrasi.

Jika folder publik Laravel terpisah dari document root hosting, salin isi `public/assets/` ke direktori aset yang memang disajikan oleh domain. Pastikan CSS, JS dan folder `assets/web/` sudah tersedia.

Periksa:
- `/teacher` menampilkan desain baru dan memuat `examroom.css?v=20261027-access-v1`.
- `/privacy`, `/support` dan `/delete-account` dapat diakses publik.
- Detail ujian guru menampilkan kode pengawas; lookup/join siswa tidak memuat kode pengawas atau hash.
- Ujian nonaktif/kedaluwarsa dapat diaktifkan ulang dengan kode masuk baru.
- Cache produksi harus persisten (database/Redis) agar rate limit efektif lintas request. SMTP dan cron tetap memakai konfigurasi hosting yang sudah ada.

Apabila deployment gagal, jangan menjalankan migrate:fresh atau mengganti APP_KEY. Pulihkan code dan backup database yang sesuai sebelum membuka maintenance. Aplikasi Android baru harus dirilis setelah API ini terverifikasi.
