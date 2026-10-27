# ExamRoom — Laravel API & Teacher Room Web

Laravel 12, PHP 8.2+. Panel guru di `/teacher`, API di `/api`. Tidak ada akun/PIN bawaan, institusi yang diwajibkan, atau backend mock. Google Forms adalah provider sesi yang didukung pada versi awal.

```sh
composer install
cp .env.example .env
php artisan key:generate
# Untuk SQLite: buat database/database.sqlite jika belum ada.
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8091
```

Jangan timpa `.env`/APP_KEY yang sudah dipakai. Identitas peserta terenkripsi dengan APP_KEY. Pengaturan `GOOGLE_WEB_CLIENT_ID` diperlukan untuk Google; SMTP dan APP_URL diperlukan untuk email pemulihan. Default email log lokal tidak mengirim email sungguhan.

Panel web menggunakan Blade/CSS/JavaScript lokal tanpa build npm. Bearer token web hanya disimpan dalam memori halaman; refresh meminta login kembali. Android menggunakan API yang sama dan menyimpan token melalui Android Keystore.

```sh
php artisan test
```

Lihat `docs/EXAMROOM-SETUP.md`, `docs/EXAMROOM-SECURITY.md`, dan `docs/EXAMROOM-VERIFICATION.md`. Hosting produksi memerlukan HTTPS, APP_DEBUG=false, cache persisten bersama, database produksi dan cron scheduler. Petunjuk penerapan versi 3.1.0 ada di `docs/EXAMROOM-DEPLOY-3.1.0.md`.

## Privasi dan penghapusan akun

Halaman `/privacy`, `/delete-account` dan `/support` tersedia pada source. Permintaan hapus akun diverifikasi melalui email dan memerlukan konfirmasi akhir. [Konfigurasi, deployment dan pengujian](docs/EXAMROOM-WEB-PRIVACY.md). SMTP produksi harus aktif; URL belum dianggap tayang sebelum deployment diperiksa.

## Akses ujian 3.1.0

Kode keluar dan kode lanjut dibuat otomatis dan berbeda untuk setiap ujian, dapat dilihat guru pemilik pada detail ujian. Tidak perlu mengatur PIN profil atau wajib perangkat terkelola. Ujian kedaluwarsa/nonaktif dapat diaktifkan ulang dengan kode masuk baru, mempertahankan data peserta dan waktu mulai. Jalankan migrasi saat memperbarui hosting.
