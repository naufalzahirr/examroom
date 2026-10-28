# Teacher Room — Sneat UI

This replaces the previous Figma V6 web layout with the official Sneat Free Bootstrap template, as requested by the owner on 28 October 2026.

## Foundation

- Sneat Free 3.0.0, upstream commit `de094c2bf89e602054b7c14f46c9a3a5b9f6cac9`: https://github.com/themeselection/sneat-bootstrap-html-admin-template-free
- Unmodified compiled theme at `public/assets/vendor/sneat/core.css`, including Bootstrap 5.3.3.
- Public Sans variable font is hosted locally. Theme, Bootstrap and font licenses are included with the assets.
- Real Sneat/Bootstrap button, input, select, card and menu components are used. Application layout adaptations live in `public/assets/examroom.css`; existing ExamRoom JavaScript handles the workflow. No template demo JavaScript, analytics or CDN dependencies are introduced.

## Application changes

- Consistent purple Sneat palette, light canvas, elevated cards, readable typography and spacing.
- Sidebar navigation with a school identity panel, active menu, help and account logout. The brand button returns to the dashboard without losing the authenticated session.
- Floating account header; dashboard welcome panel and four icon-based statistics cards.
- Centered authentication card for login, registration, forgotten password and reset.
- Exam list with status tabs, search, sorting, refresh and an overflow menu; participant search, status and reset controls remain operational.
- Responsive cards for exam and participant lists on small screens, plus an accessible navigation drawer. Narrow desktop forms use a single column.
- Public help, privacy and deletion pages share the theme while retaining their existing text and behavior.
- Existing backend behavior is preserved: reactivation rotates the entry code and keeps participant history, exit/resume codes remain separate per exam and private, and no managed-device requirement is reintroduced.
- CSS and JS cache version: `20261028-sneat-1`.

## Verification

- Browser checks at 1600, 1440, 1280, 1024, 820, 768, 390 and 320 px. Authentication, dashboard, exam listing, creation, generated codes, participants, profile and public pages were inspected. No horizontal document overflow or JavaScript errors.
- 14 access-flow regression checks passed, including code copy privacy, reactivation, retained participant history, invalidated entry codes and resuming after exit.
- Laravel suite: 44 tests, 359 assertions passed.

## Hosting

Pull `main`, then run `php artisan optimize:clear`. Include the new `public/assets/vendor/sneat` directory when deploying. This UI update adds no database migrations or package-install steps. If the earlier 3.1.0 release has not been deployed, follow its existing deployment guide for the prior migration. Hosting deployment remains manual.


## Pembaruan Figma dan reset kode — 28 Oktober 2026

Header publik mengikuti susunan logo dan caption Figma terbaru. Pencarian, dropdown, dan tombol detail peserta pada layar HP memakai batas dan ukuran yang konsisten dengan desain.

Guru dapat mereset kode masuk, keluar aplikasi, atau lanjut ujian secara terpisah melalui detail ujian, termasuk saat masih aktif. Menu tindakan daftar ujian dan halaman kode baru juga menyediakan reset kode masuk. Konfirmasi menyebutkan bahwa kode lama langsung tidak berlaku. Reset tidak memperpanjang masa berlaku, mengaktifkan ujian, mengubah peserta, atau mengulang waktu ujian. Aktivasi ulang ujian kedaluwarsa tetap memakai alur tersendiri.

API pemilik: `POST /api/exams/{id}/reset-code`, JSON `kind: entry|exit|resume`. Respons berisi `session` dan `action_codes`, hanya untuk pemilik. Kode pengawas tetap terenkripsi dan diverifikasi menggunakan hash. Pengecekan kode pengawas dan rotasi memakai kunci baris sesi; peserta yang bergabung juga memeriksa ulang kode masuk setelah memperoleh kunci.

Validasi: 46 pengujian backend / 429 assertions; 62 pemeriksaan tampilan pada 320–1600 px; alur reset ketiga kode dan pembatalan dialog pada desktop/390/320 px, tanpa galat JavaScript. Tidak membutuhkan migrasi baru. Setelah pull, jalankan `php artisan optimize:clear` agar route dan view terbaru dipakai.
