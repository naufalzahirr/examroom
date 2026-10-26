<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="{{ $page === 'confirm-deletion' ? 'noindex, nofollow' : 'index, follow' }}">
    <title>{{ ['privacy' => 'Kebijakan Privasi', 'deletion' => 'Hapus Akun dan Data', 'support' => 'Bantuan', 'confirm-deletion' => 'Konfirmasi Hapus Akun'][$page] }} — ExamRoom</title>
    <link rel="icon" href="/assets/mark.png"><link rel="stylesheet" href="/assets/examroom.css">
</head>
<body>
<div class="legal-shell">
    <header class="brand"><a href="/teacher"><img src="/assets/mark.png" alt="ExamRoom"></a><div><b>ExamRoom</b><small>Privasi dan bantuan</small></div></header>
    <nav class="legal-nav" aria-label="Informasi layanan"><a href="/teacher">Teacher Room</a><a href="{{ route('privacy') }}">Kebijakan privasi</a><a href="{{ route('account-deletion') }}">Hapus akun dan data</a><a href="{{ route('support') }}">Bantuan</a></nav>
    <main class="legal-content">
    @if ($page === 'privacy')
        <span class="badge">PRIVASI ANDA</span><h1>Kebijakan Privasi ExamRoom</h1>
        <p>Terakhir diperbarui: 26 Oktober 2026. Berlaku untuk aplikasi Android ExamRoom (<code>id.examroom.app</code>) dan web pada domain examroom.smkn4tpi.sch.id.</p>
        <section><h2>Pengelola dan kontak</h2><p>ExamRoom menyediakan browser ujian dan ruang guru untuk membuat serta memantau sesi Google Forms. Pengelola layanan ExamRoom dapat dihubungi melalui <a href="mailto:{{ config('examroom.support_email') }}">{{ config('examroom.support_email') }}</a>. Kebijakan ini menjelaskan pemrosesan oleh layanan ExamRoom; sekolah dan pembuat formulir bertanggung jawab atas kegiatan ujian serta formulir yang mereka kelola.</p></section>
        <section><h2>Data yang kami proses</h2><ul>
            <li><strong>Akun guru:</strong> nama, alamat email, nama sekolah, identitas akun, pengaturan keamanan, kata sandi dan PIN pengawas. Kata sandi serta PIN disimpan sebagai hash. Login Google memproses ID akun Google, nama dan email untuk autentikasi.</li>
            <li><strong>Sesi ujian:</strong> judul, tautan Google Forms, kode, durasi, waktu berlaku, nama guru/sekolah dan kebijakan penguncian.</li>
            <li><strong>Peserta:</strong> nama dan nomor peserta, sesi yang diikuti, status aktivitas, mode penguncian serta waktu bergabung, pembaruan status dan keluar. Siswa tidak membuat akun ExamRoom, tetapi informasi tersebut tetap diproses.</li>
            <li><strong>Data teknis:</strong> token akses, data pemulihan akun dan alamat IP untuk membatasi penyalahgunaan. Server hosting dapat memproses catatan koneksi dan kesalahan untuk menjalankan serta mengamankan layanan.</li>
        </ul><p>Fitur native ExamRoom tidak meminta akses kamera, mikrofon, lokasi atau kontak. Browser ujian tidak mendukung unggah berkas.</p></section>
        <section><h2>Tujuan dan siapa yang dapat melihat data</h2><p>Kami menggunakan data untuk mengelola akun, memulihkan akses, membuat sesi, mengenali peserta, menampilkan status ujian dan menjaga keamanan layanan. Guru pemilik sesi dapat melihat nama, nomor dan status peserta pada sesi tersebut. Pastikan informasi guru/sekolah pada layar konfirmasi sesuai sebelum bergabung.</p><p>Hosting dan layanan pengiriman email memproses data yang diperlukan untuk mengoperasikan ExamRoom. Google memproses data ketika Anda memilih Google Sign-In atau membuka serta mengisi Google Forms. ExamRoom tidak menampilkan iklan pada antarmuka aplikasinya.</p></section>
        <section><h2>Google Forms dan jawaban ujian</h2><p>Jawaban dikirim melalui Google Forms kepada Google dan pemilik formulir; jawaban serta nilai tidak disimpan dalam database ExamRoom. Jenis pertanyaan, akses jawaban dan retensi formulir ditentukan pembuatnya. Hubungi guru/pemilik formulir untuk penghapusan jawaban. Lihat <a href="https://policies.google.com/privacy" rel="noreferrer">Kebijakan Privasi Google</a>. Menghapus akun ExamRoom tidak menghapus akun Google atau jawaban Forms.</p></section>
        <section><h2>Keamanan dan penyimpanan lokal</h2><p>Build release menggunakan HTTPS. Aplikasi melindungi token lokal dengan Android Keystore. Nama serta nomor peserta dienkripsi pada database; sandi dan PIN disimpan sebagai hash. Web menyimpan bearer token login di memori halaman. Cookie sesi web digunakan untuk keamanan formulir, dan WebView dapat menggunakan penyimpanan/cookie yang diperlukan halaman Google. Tidak ada jaminan keamanan mutlak.</p><p>Penguncian perangkat membantu pelaksanaan ujian dan bergantung pada konfigurasi Android. Status yang ditampilkan berasal dari laporan aplikasi, bukan bukti seluruh aktivitas perangkat.</p></section>
        <section><h2>Berapa lama data disimpan</h2><p>Data peserta dijadwalkan dibersihkan setelah {{ config('examroom.participant_retention_days') }} hari tanpa pembaruan, melalui pembersihan harian server. Akun guru dan data sesi tetap disimpan untuk menjalankan layanan sampai akun dihapus. Masa berlaku kode ujian tidak otomatis menghapus data sesi.</p><p>Penghapusan akun menghapus profil, sesi dan peserta terkait dari database aktif serta mencabut token akses. Catatan operasional, email yang sudah dikirim dan salinan cadangan infrastruktur tidak termasuk penghapusan langsung database aktif. Untuk status retensi atau penghapusan salinan tersebut, hubungi dukungan. Data pada layanan Google mengikuti kebijakan serta pengaturan layanan dan pemilik formulir.</p></section>
        <section><h2>Permintaan penghapusan dan pertanyaan privasi</h2><p>Guru dapat <a href="{{ route('account-deletion') }}">meminta hapus akun dan data terkait</a> tanpa login. Kami mengirim tautan verifikasi ke email akun; penghapusan dijalankan setelah Anda mengonfirmasi. Peserta tanpa akun dapat meminta penghapusan data melalui email dukungan dengan menyebutkan guru/sekolah, sesi, nama dan nomor peserta yang relevan. Kami dapat meminta informasi minimum untuk memverifikasi permintaan; jangan mengirim sandi, PIN atau jawaban ujian.</p></section>
        <section><h2>Pengguna usia sekolah</h2><p>Layanan digunakan dalam kegiatan pendidikan dengan arahan guru/sekolah. Guru perlu memberikan informasi tentang data dan formulir yang digunakan serta memastikan pemberitahuan dan persetujuan yang diperlukan sesuai usia peserta. Orang tua atau wali dapat menghubungi dukungan untuk pertanyaan atau permintaan terkait data anak.</p></section>
        <section><h2>Perubahan kebijakan</h2><p>Perubahan kebijakan akan ditampilkan pada halaman ini dengan tanggal pembaruan. Pertanyaan mengenai kebijakan atau pemrosesan data dapat dikirim ke <a href="mailto:{{ config('examroom.support_email') }}">{{ config('examroom.support_email') }}</a>.</p></section>
    @elseif ($page === 'deletion')
        <span class="badge">KONTROL DATA</span><h1>Hapus akun dan data ExamRoom</h1>
        @if (session('deleted'))<div class="card success" role="status"><h2>Akun telah dihapus</h2><p>Profil guru, sesi ujian, peserta terkait dan token akses di database aktif ExamRoom telah dihapus. Anda dapat menutup halaman ini.</p></div>@endif
        <p>Anda dapat memulai permintaan tanpa masuk ke akun. Masukkan email akun guru, lalu ikuti tautan konfirmasi pada email. Membuka email atau tautan saja belum menghapus data.</p>
        <div class="card"><h2>Yang akan dihapus</h2><ul><li>Akun dan profil guru, pengaturan keamanan serta identitas Google yang tersimpan di ExamRoom.</li><li>Seluruh sesi ujian milik akun beserta nama, nomor dan status peserta terkait.</li><li>Token login dan data pemulihan akun.</li></ul><p><strong>Penghapusan tidak dapat dibatalkan dan sesi ujian terkait akan berhenti dapat diakses.</strong> Jangan melanjutkan ketika ada ujian yang masih berjalan.</p><p>Penghapusan berlangsung setelah verifikasi email dan konfirmasi terakhir. Tautan berlaku selama 1 jam.</p></div>
        <div class="card"><h2>Data di luar database aktif</h2><p>Akun Google dan jawaban di Google Forms tetap dikelola Google/pemilik formulir. Log operasional, email terkirim dan backup infrastruktur tidak terhapus langsung bersama akun. Hubungi dukungan untuk menanyakan atau meminta penanganan salinan tersebut; lihat <a href="{{ route('privacy') }}">kebijakan privasi</a>.</p></div>
        @if (session('status'))<p class="card success" role="status">{{ session('status') }}</p>@endif
        @if ($errors->any())<p class="card danger" role="alert">{{ $errors->first() }}</p>@endif
        <form method="post" action="{{ route('account-deletion.request') }}" class="card">@csrf
            <label for="email">Email akun guru</label><input id="email" name="email" type="email" autocomplete="email" maxlength="254" value="{{ old('email') }}" required>
            <button class="action" type="submit">Kirim tautan konfirmasi</button>
        </form>
        <section><h2>Peserta tanpa akun atau kehilangan akses email?</h2><p>Kirim permintaan ke <a href="mailto:{{ config('examroom.support_email') }}?subject=Permintaan%20penghapusan%20data%20ExamRoom">{{ config('examroom.support_email') }}</a>. Sebutkan data yang ingin dihapus dan informasi sesi/guru terkait. Kami akan memverifikasi kepemilikan sebelum menindaklanjuti. Jangan mengirim kata sandi atau PIN pengawas.</p></section>
    @elseif ($page === 'confirm-deletion')
        <span class="badge">KONFIRMASI TERAKHIR</span><h1>Hapus akun ExamRoom secara permanen?</h1>
        <p>Akun guru yang menerima tautan ini, seluruh sesi ujian dan data peserta terkait akan dihapus dari database aktif. Token akses akan dicabut. Ujian yang masih berjalan tidak dapat dilanjutkan melalui sesi tersebut.</p>
        <p>Jawaban Google Forms, email terkirim, log dan backup infrastruktur tidak termasuk penghapusan langsung ini. <a href="{{ route('privacy') }}">Baca kebijakan privasi</a>.</p>
        @if ($errors->any())<p class="card danger" role="alert">Ketik HAPUS untuk mengonfirmasi.</p>@endif
        <form method="post" action="{{ request()->fullUrl() }}" class="card">@csrf
            <label for="confirmation">Ketik HAPUS untuk melanjutkan</label><input id="confirmation" name="confirmation" autocomplete="off" pattern="HAPUS" required>
            <button type="submit" class="action destructive">Hapus akun dan seluruh data terkait</button>
        </form><a class="link" href="/teacher">Batal, kembali ke Teacher Room</a>
    @else
        <span class="badge">KAMI SIAP MEMBANTU</span><h1>Bantuan ExamRoom</h1>
        <p>Hubungi <a href="mailto:{{ config('examroom.support_email') }}">{{ config('examroom.support_email') }}</a> untuk bantuan akun, privasi dan kendala aplikasi.</p>
        <section class="card"><h2>Kendala saat ujian</h2><p>Hubungi guru atau pengawas terlebih dahulu untuk kode kedaluwarsa, PIN keluar, sesi dijeda dan konfigurasi perangkat. ExamRoom tidak menyediakan PIN darurat yang ditanam dalam aplikasi.</p></section>
        <section class="card"><h2>Laporkan masalah</h2><p>Sertakan versi aplikasi, model perangkat, versi Android, waktu kejadian dan pesan kesalahan. Gunakan data contoh; jangan mengirim kata sandi, PIN, token, jawaban ujian atau data pribadi peserta lain.</p></section>
        <section class="card"><h2>Privasi dan penghapusan</h2><p>Baca <a href="{{ route('privacy') }}">kebijakan privasi</a> atau <a href="{{ route('account-deletion') }}">mulai permintaan hapus akun/data</a>. Peserta tanpa akun dan orang tua/wali juga dapat menghubungi email dukungan.</p></section>
    @endif
    </main>
    <footer class="legal-footer">ExamRoom · <a href="mailto:{{ config('examroom.support_email') }}">{{ config('examroom.support_email') }}</a></footer>
</div>
</body></html>
