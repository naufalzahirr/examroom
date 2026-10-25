# Verifikasi ExamRoom — 25 Oktober 2026

## Hasil yang sudah dibuktikan

- `assembleDebug`, `assembleDebugAndroidTest`, `testDebugUnitTest`, `lintDebug`: **BUILD SUCCESSFUL** pada JBR Android Studio, SDK 36 / target 35. Tidak ada error lint; peringatan tetap tercantum di laporan, termasuk API lama untuk kompatibilitas, native Switch, dan rekomendasi dependency lebih baru.
- Android JVM: **14 tes lulus**, mencakup parsing host/URL berbahaya, provider, navigasi lintas form/platform, resource vs navigasi utama, binding pemulihan, serta regresi pengikatan ID formulir langsung sebelum navigasi pertama. Tes PIN lama tetap memverifikasi utilitas pemulihan sesi versi sebelumnya.
- Backend Laravel: **24 tes lulus, 147 assertion**, termasuk registrasi/login/logout, profil, tenant isolation, snapshot identitas, kode salah/kedaluwarsa/nonaktif, URL/provider tidak didukung, token siswa terpisah, managed/pinned policy, PIN salah/benar, exit idempotent, retensi rahasia dalam respons, enkripsi identitas, password recovery/reset/revocation, rate limit, resume setelah pause, dan cache database untuk penghitung PIN gagal. Dua tes scaffold sederhana termasuk dalam total 24 tersebut.
- PHP Pint: lulus. JavaScript panel web: `node --check` lulus. `/teacher` mengembalikan HTTP 200 dan halaman reset berhasil dirender pada tes Laravel.
- Emulator Android 15 ARM64 `emulator-5596`: alur Student Room tanpa akun; Teacher Room meminta login; login email dan dashboard menggunakan backend Laravel sungguhan; kode salah; konfirmasi sekolah/guru/judul sebelum lock; ganti kode; identitas peserta; pemeriksaan perangkat; penolakan perangkat unmanaged untuk sesi managed: **lulus**.
- **Google Forms nyata**: dokumen `docs.google.com/forms/` mencapai progress 100 dengan title tidak kosong di WebView setelah redirect HTTPS diperiksa, tanpa mengisi atau mengirim jawaban. Laporan lengkap: `artifacts/examroom/android-managed-resolved-forms.txt`.
- Emulator yang sama, sementara menjadi device owner: mode Android **LOCK_TASK_MODE_LOCKED** teramati melalui UI yang membaca ActivityManager, backend menerima `in_session`, Back membuka konfirmasi, PIN salah tidak menutup sesi, PIN benar divalidasi backend dan mengakhiri sesi menjadi `exited`: **lulus**. Device owner uji dikembalikan setelah selesai.

Backend test memakai database SQLite terisolasi; instrumentasi memakai API lokal dengan akun QA khusus. Tidak ada mock yang diklaim sebagai backend produksi. Tidak ada pengiriman jawaban ke Google Forms.

## Bukti

- `artifacts/examroom-backend-junit.xml`: laporan tes backend.
- `artifacts/examroom/reports/TEST-*.xml`: laporan unit test Android.
- `artifacts/examroom/reports/lint-results-debug.html` dan `.txt`.
- `artifacts/examroom/android-managed.txt`: output instrumentasi managed.
- `artifacts/examroom/screenshots/examroom-qa/`: screenshot beranda, login, dashboard, kode salah, konfirmasi, identitas, pemeriksaan, perangkat tidak didukung, browser dan selesai.

Screenshot QA melepas FLAG_SECURE sementara hanya dari instrumentasi. Pada konfigurasi managed yang membatasi screenshot OS, runner merender view aplikasi aktual ke bitmap untuk meninjau UI native; ini bukan bukti bahwa proteksi capture Android dapat dilewati pengguna aplikasi. Screenshot awal browser menunjukkan keadaan loading; hasil akhir diambil setelah dokumen selesai dimuat. Keduanya bukan bukti pengiriman jawaban atau nilai. Percobaan live awal terinterupsi pemasangan APK baru; laporan percobaan tersebut dipertahankan terpisah. Percobaan final lengkap lulus pada `android-managed-resolved-forms.txt`.

## Cakupan 28 layar

| Referensi | Implementasi / status bukti |
|---|---|
| 01–06 | Beranda, pemeriksaan kode, kode salah, konfirmasi, identitas, pemeriksaan perangkat; alur utama diuji emulator, state checking terikat request API. |
| 07–08 | Browser terkunci dan selesai; penguncian, backend lifecycle dan keluar diuji emulator. Tampilan soal mengikuti situs Google Forms sebenarnya. |
| 09–11 | Login, lengkapi profil, dashboard; email/dashboard diuji emulator, profil diuji backend. Google nyata menunggu konfigurasi OAuth. |
| 12–15 | Buat ujian, kode, pemantauan dan detail; endpoint/data diuji backend, kontrol native copy/share tersedia. Semua interaksi visualnya belum diuji otomatis di seluruh ukuran perangkat. |
| 16–20 | Profil sekolah, kebijakan, daftar kode, registrasi, profil tersimpan; backend/snapshot diuji. Registrasi Google menunggu OAuth. |
| 21–23 | Kedaluwarsa, tidak tersambung, lock tidak tersedia; kedaluwarsa/nonaktif diuji backend dan lock tidak tersedia diuji emulator. |
| 24–26 | Google gagal, dashboard kosong, lupa sandi; state tersedia. Pemulihan diuji melalui notification test Laravel, bukan email SMTP nyata. |
| 27–28 | Konfirmasi keluar dan keypad PIN; salah/benar dan status server diuji emulator. |

Desain diterjemahkan menjadi komponen native responsif, bukan gambar layar statis. Tidak ada klaim perbandingan pixel-perfect 28 layar pada semua perangkat. Header/footer yang semula terlalu dekat dengan bilah sistem diperbaiki berdasarkan screenshot QA.

## Belum dapat dianggap tervalidasi produksi

1. Google Sign-In end-to-end dengan client ID, SHA-1, consent screen, origin dan akun Google nyata milik proyek.
2. Pengiriman dan penerimaan email reset melalui SMTP sungguhan.
3. QA interaktif panel web dalam browser: layanan browser otomatis sesi ini tidak tersedia (`CUA_REPL_ENABLED_SURFACES is required`). Render HTTP, JavaScript syntax, endpoint dan logika backend sudah diperiksa.
4. Screen Pinning pada HP pribadi/OEM berbeda, Android 8–14/16, font besar, keyboard/perubahan orientasi, reboot/kill proses dan pengujian beban produksi menyeluruh.
5. Form wajib login organisasi, unggah berkas, kamera atau provider selain Google Forms. Form upload/kamera diblokir pada versi ini.
6. Audit penetration test, remote hardware attestation dan konfigurasi perangkat sekolah. Status yang dilaporkan aplikasi bukan bukti anti-manipulasi.
7. Hosting HTTPS, kredensial produksi, release signing dan distribusi Play Console. Tidak ada deployment atau release dilakukan.

Lihat `EXAMROOM-SETUP.md` dan `EXAMROOM-SECURITY.md` untuk konfigurasi serta batas perlindungan.
