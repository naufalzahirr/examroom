# Konfigurasi ExamRoom

## Backend dan panel web Laravel

Proyek: `/Users/naufalzahir/Laravel Env/examroom`. Sumber salinan yang diuji juga tersedia di `Android Env/backend`. Pilih satu sebagai sumber pengembangan berikutnya agar perubahan tidak berbeda. Proyek ini terpisah dari aplikasi Laravel lainnya.

Persyaratan: PHP 8.2+, Composer, ekstensi OpenSSL, PDO SQLite atau MySQL, mbstring, XML, cURL. Node/npm tidak diperlukan untuk panel web ini.

```sh
cd '/Users/naufalzahir/Laravel Env/examroom'
composer install
cp .env.example .env
php artisan key:generate
# SQLite lokal: buat berkas database/database.sqlite jika belum ada.
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8091
```

Jika `.env` sudah ada, pertahankan APP_KEY dan jangan menimpanya. APP_KEY mengenkripsi identitas peserta; mengganti kunci akan membuat data lama tidak terbaca. `.env` dan database lokal tidak untuk dibagikan/di-commit. Panel guru berada di `/teacher`; `/` membuka panel yang sama. API berada di `/api`. Database awal tidak memiliki guru, sekolah, kode aktif, maupun PIN default. Daftar akun, isi profil, lalu atur PIN enam angka sebelum membuat ujian.

Untuk hosting nanti: arahkan document root ke `public/`, gunakan HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-anda`, database dan cache persisten bersama (Redis disarankan jika beberapa instance). Konfigurasikan trusted proxy sesuai infrastruktur sendiri. Jalankan `composer install --no-dev --optimize-autoloader`, migrasi dan `php artisan config:cache` pada proses deployment yang disetujui. Jangan menyalin APP_KEY lokal menjadi kunci produksi baru tanpa keputusan pengelolaan data. Retensi peserta default 30 hari sejak pembaruan terakhir melalui scheduler; atur `PARTICIPANT_RETENTION_DAYS` sesuai kebijakan penyelenggara dan jalankan cron `php artisan schedule:run` setiap menit. Siapkan backup terenkripsi dan pemberitahuan privasi sebelum penggunaan publik.

## Google Sign-In

1. Buat/pilih proyek Google Cloud milik penyelenggara, konfigurasikan OAuth consent screen dan test users selama mode pengujian.
2. Buat OAuth client **Web application**. Simpan client ID publik pada `GOOGLE_WEB_CLIENT_ID` di `.env`. Client secret tidak diperlukan untuk pertukaran ID token ini dan tidak ditanam dalam APK.
3. Buat OAuth client **Android** dengan package `id.ruangujian.app` dan SHA-1 dari sertifikat yang benar. Untuk debug dapat dilihat dengan `./gradlew signingReport`. Release memerlukan client Android sesuai sertifikat release/Play App Signing.
4. Gunakan Web client ID yang sama pada build Android melalui `-PgoogleWebClientId=...apps.googleusercontent.com`.
5. Untuk panel web, tambahkan authorized JavaScript origin yang tepat, misalnya `http://localhost:8091` untuk lokal dan domain HTTPS hosting untuk produksi. Alur web memakai Google Identity Services; Android memakai Credential Manager. Backend memverifikasi tanda tangan, audience, issuer, masa berlaku, email terverifikasi dan nonce sekali pakai.
6. Jalankan pengujian end-to-end dengan akun Google nyata dan konfigurasi tersebut. Pengujian ini belum dapat dibuktikan tanpa client ID proyek Anda. Akun email yang sudah ada tidak otomatis digabung dengan Google; gunakan metode email untuk akun tersebut.

Referensi resmi: https://developer.android.com/identity/sign-in/credential-manager-siwg dan https://developers.google.com/identity/gsi/web/guides/verify-google-id-token

## Pemulihan kata sandi

Atur `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME=ExamRoom` dan TLS sesuai penyedia. APP_URL harus mengarah ke web yang dapat diakses guru. Default lokal memakai log, sehingga **belum mengirim email sungguhan**. Tautan membuka `/reset-password/{token}` dan reset mencabut token API guru. Respons permintaan email bersifat umum untuk mengurangi pengungkapan akun.

## Android / APK debug

JDK 17+; gunakan JBR Android Studio pada komputer ini. Build QA emulator:

```sh
cd '/Users/naufalzahir/Workspace/Android Env'
JAVA_HOME='/Applications/Android Studio.app/Contents/jbr/Contents/Home' ./gradlew assembleDebug testDebugUnitTest lintDebug -PexamroomApiUrl=http://127.0.0.1:8091/api
```

Output standar `app/build/outputs/apk/debug/app-debug.apk`. Salinan bernama ExamRoom tersedia di `artifacts/ExamRoom-3.0-debug-local.apk`. Ini APK debug untuk backend lokal emulator, **bukan build untuk distribusi publik**. Build QA ini memakai `127.0.0.1` melalui `adb reverse tcp:8091 tcp:8091`. Alamat host standar `10.0.2.2` juga hanya untuk emulator; tidak bekerja pada konfigurasi emulator awal sesi ini. HP fisik atau penggunaan hosting membutuhkan build dengan URL HTTPS backend yang sebenarnya:

```sh
./gradlew assembleDebug -PexamroomApiUrl=https://domain-anda/api -PgoogleWebClientId=CLIENT_ID_WEB.apps.googleusercontent.com
```

Tanpa URL API, UI menampilkan bahwa layanan belum dikonfigurasi. Release menolak HTTP; pengecualian HTTP debug terbatas pada localhost, 127.0.0.1, dan 10.0.2.2. Tidak ada alamat produksi palsu atau secret bawaan.

## Pengujian

```sh
cd backend
php artisan test
# Dari root Android:
./gradlew testDebugUnitTest lintDebug
```

`ExamRoomInstrumentation` adalah runner khusus emulator yang memerlukan build dengan API lokal. Ia membuat akun/sesi QA di backend lokal, membersihkan hanya preferences aplikasi pada emulator yang dituju, dan mengambil screenshot dengan melepas FLAG_SECURE sementara **dalam proses tes**. Jangan jalankan pada perangkat peserta. Tidak mengisi atau mengirim Google Forms. Runner lama SafeExam dipertahankan sebagai riwayat dan tidak menjadi validasi ExamRoom.

```sh
adb -s emulator-5596 reverse tcp:8091 tcp:8091
adb -s emulator-5596 install -r app/build/outputs/apk/debug/app-debug.apk
adb -s emulator-5596 install -r app/build/outputs/apk/androidTest/debug/app-debug-androidTest.apk
adb -s emulator-5596 shell am instrument -w id.ruangujian.app.test/id.ruangujian.app.ExamRoomInstrumentation
```

Gunakan emulator kosong terpisah untuk uji device owner. Menetapkan device owner pada HP pribadi bukan bagian dari setup publik dan dapat memerlukan perangkat yang belum diprovisikan/factory reset; gunakan pengelolaan Android Enterprise yang sesuai untuk perangkat sekolah.
