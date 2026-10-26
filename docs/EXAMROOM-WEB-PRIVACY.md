# Halaman privasi, bantuan dan penghapusan akun

Dibuat 26 Oktober 2026. Kontak publik: support@examroom.smkn4tpi.sch.id.

## URL publik setelah deployment

- https://examroom.smkn4tpi.sch.id/privacy — Kebijakan Privasi
- https://examroom.smkn4tpi.sch.id/delete-account — permintaan hapus akun dan data, tanpa login
- https://examroom.smkn4tpi.sch.id/support — bantuan

Tautan tersedia pada footer Teacher Room, termasuk sebelum login. Implementasi halaman: `resources/views/privacy.blade.php`. URL di atas belum dianggap aktif sampai deployment diverifikasi. Pemeriksaan hosting pada 26 Oktober 2026 sebelum deployment masih mendapat 404 untuk `/privacy`.

## Alur penghapusan

1. Pemohon mengisi email pada halaman publik, dengan CSRF dan pembatasan 5 permintaan/menit per IP serta 3/jam per hash email.
2. Respons sama untuk akun ada/tidak ada. Akun yang ditemukan mendapat email dengan URL bertanda tangan yang berlaku satu jam. Tidak ada data dihapus pada tahap ini.
3. Membuka URL hanya menampilkan penjelasan. Pemohon harus mengetik HAPUS dan mengirim formulir konfirmasi dengan CSRF; POST memeriksa tanda tangan/masa berlaku lagi.
4. Transaksi menghapus akun, token Sanctum dan reset sandi, serta sesi login database jika driver itu digunakan. Foreign key cascade menghapus sesi ujian dan data peserta milik akun. Akun guru lain tetap utuh.
5. Jawaban Google Forms, akun Google, email terkirim, log dan backup infrastruktur tidak ikut dihapus oleh transaksi database aplikasi. Permintaan terkait salinan tersebut ditangani melalui dukungan.

Penghapusan membuat sesi ujian terkait tidak dapat diakses. Email dan halaman konfirmasi memberi penjelasan ini sebelum pengguna menyetujui. Tidak ada permintaan atau penghapusan akun produksi dilakukan selama pengujian.

## Pengaturan hosting

- Pertahankan `.env` dan `APP_KEY` produksi. Jangan mengunggah `.env` lokal atau database QA.
- `APP_URL=https://examroom.smkn4tpi.sch.id` diperlukan agar URL email menunjuk HTTPS/domain yang benar. Konfigurasikan trusted proxy sesuai terminasi HTTPS hosting.
- Opsional `EXAMROOM_SUPPORT_EMAIL=support@examroom.smkn4tpi.sch.id`; nilai ini sudah menjadi default.
- SMTP harus benar-benar mengirim email; driver `log` atau `array` hanya untuk pengujian. Verifikasi pengiriman dengan akun uji milik sendiri sebelum menyatakan alur produksi siap. Jangan kirim pengujian ke email pengguna lain.
- Retensi peserta memakai `PARTICIPANT_RETENTION_DAYS` (default 30) dan scheduler yang ada. Aktifkan `php artisan schedule:run` setiap menit melalui cron. Kebijakan halaman membaca nilai konfigurasi yang sama dengan scheduler.
- Operator hosting perlu menetapkan dan menerangkan jangka waktu log/backup yang sebenarnya. Halaman menyatakan bahwa penghapusan database aktif tidak langsung menghapus salinan infrastruktur; jangan menjanjikan penghapusan semua backup tanpa proses yang tersedia.
- Tidak perlu migrasi baru: menggunakan tabel dan foreign key yang sudah ada.

## Deploy

Salin berkas perubahan sesuai `manifest.json` di paket patch ke root Laravel aplikasi yang sudah ada (folder berisi `artisan`), bukan ke document root `public/` saja. Jika hosting memisahkan `public_html`, dua file dalam `public/assets/` harus masuk ke direktori aset publik yang digunakan situs. Cadangkan berkas yang ditimpa. Jangan ubah data atau kunci produksi.

Setelah upload, jalankan dengan PHP yang sesuai di hosting:

```sh
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan route:cache
```

Periksa ketiga URL dengan mode tanpa login; validasi form kosong/email tidak valid; uji alur email dengan akun dummy dan konfirmasi bahwa membuka tautan tidak menghapus akun. Hapus hanya akun dummy ketika menguji konfirmasi akhir. Pastikan scheduler aktif dan periksa log error jika email tidak diterima. Simpan backup file agar dapat rollback dengan menyalin versi sebelumnya dan mengulang perintah cache.

## Verifikasi lokal

`php artisan test` lulus: 29 tes, 194 assertions. Lima tes baru mencakup halaman publik, validasi/non-enumerasi email, konfirmasi dan cascade pemilik, penolakan tanda tangan kedaluwarsa/diubah, dan pembatasan permintaan. Database uji SQLite in-memory; tidak menyentuh akun produksi. Pengiriman SMTP sungguhan belum diuji.

## Play Console dan Android

Isi URL kebijakan privasi dan hapus akun setelah halaman aktif. Perbarui praktik Data safety berdasarkan operasi produksi, Google Identity dan Forms. Build Android 3.0.2 yang sudah ada belum memuat tautan privasi atau alur permintaan hapus akun di aplikasi; tambahkan akses dari aplikasi dan build versi baru sebelum menyatakan persyaratan in-app terpenuhi. Halaman web saja tidak menggantikan jalur dari aplikasi untuk aplikasi yang menawarkan pembuatan akun.
