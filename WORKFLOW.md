# WORKFLOW — Aturan Main untuk AI

> Dibaca AI di awal setiap sesi (diwajibkan oleh `CLAUDE.md`). Tujuannya satu: AI bisa bekerja cepat dan mandiri **tanpa** membuat kejutan yang mahal.
> Hanya Salman yang boleh mengubah file ini. AI boleh mengusulkan perubahan, tidak boleh mengubahnya sendiri.
> Aturan yang paling ketat di file ini juga dikunci lewat `.claude/settings.json`. Kalau keduanya berbeda, yang lebih ketat berlaku.

## 1. Prinsip

1. **Kerjakan task yang jelas, jangan menebak scope.** Sumber kebenaran: `TODO.md` (status), `docs/Architecture.md` (task & kriteria), `docs/PRD-retail-inventory.md` (kebutuhan).
2. **Klaim harus ada buktinya.** "Selesai" tanpa output perintah test/lint adalah klaim kosong.
3. **Kerjakan yang diminta, tidak lebih.** Temuan di luar scope dicatat, tidak diperbaiki diam-diam.
4. **Tanya sedikit, tapi tepat.** Tanya hanya bila jawabannya mengubah hasil dan tidak bisa dibaca dari repo.

## 2. Alur satu task

| # | Langkah | Aturan |
| - | ------- | ------ |
| 1 | **Pilih** | Ambil dari "Siap dikerjakan" di `TODO.md`. Task di luar daftar itu: tanya dulu. |
| 2 | **Baca** | Output, dependensi, dan checklist **Selesai jika** task itu di `docs/Architecture.md`; user story/AC terkait di PRD; aturan lapisan AR/IN yang relevan. |
| 3 | **Rencana singkat** | 3–7 baris: file yang akan disentuh, urutan, test yang akan ditulis, dan **tingkat task** (Easy/Medium/Hard/Hard-kritis menurut `MODEL-POLICY.md`). Bila sesi tidak cocok dengan tingkatnya, sarankan `/model` dan `/effort` yang tepat atau delegasikan ke subagent `tier-*`. Task > 6 jam atau > 10 file: tunggu persetujuan. Lainnya: tulis lalu jalan. |
| 4 | **Mulai** | Ubah status di `TODO.md` ke `🔄 doing`. Buat branch `task/T1.04-rbac` bila belum ada. |
| 5 | **Kerjakan** | Kecil-kecil. Pakai skill proyek (`/laravel-domain-feature`, `inventory-ledger`, dst.). Test ditulis bersama kode, bukan di akhir. |
| 6 | **Verifikasi** | Jalankan semua di §5, ditambah kedalaman verifikasi menurut risiko task (`MODEL-POLICY.md` §5: R1–R4). Tempel hasilnya di laporan. |
| 7 | **Review diri** | `/review-security` untuk perubahan yang menyentuh auth, izin, input, uang, atau stok. `/code-review` untuk sisanya. |
| 8 | **Tutup** | Perbarui `TODO.md` sesuai §5.3, jalankan `php scripts/todo-summary.php`, lapor dengan format §7. |

## 3. Zona izin

### 🟢 Hijau — jalan sendiri, tidak perlu tanya
- Membaca, mencari, dan menjalankan perintah baca-saja di repo.
- Membuat dan mengedit file kode, test, factory, seeder, view, dan dokumentasi **yang termasuk scope task**.
- Menjalankan `php artisan test`, `pint`, `phpstan`, `inventory:verify`, `npm run build`/`lint`, `php scripts/todo-summary.php`.
- Migration dan seed di **database lokal/test saja**, termasuk `migrate:fresh`.
- Membuat migration baru (bukan mengubah yang lama).
- Membuat branch `task/*` dan memperbarui `TODO.md` (status, kriteria, catatan, handoff, log).
- Menulis isu baru di tabel isu `TODO.md` (tipe `question`/`debt`/`risk`).
- Menginstal ulang dependensi dari lockfile yang sudah ada.

### 🟡 Kuning — berhenti dan tanya dulu
| Situasi | Kenapa |
| ------- | ------ |
| `git commit`, `git push`, membuka atau mengubah PR | Riwayat repo milik klien (C-03); Salman yang memutuskan kapan |
| Menambah, menaikkan, atau menghapus dependensi (`composer`/`npm`) | Menyentuh keamanan, lisensi, dan ukuran build |
| Mengubah migration yang **sudah ter-merge** | Harus lewat migration baru; butuh keputusan cara migrasi data |
| Mengubah skema atau trigger ledger (`stock_movements`, `stock_levels`) | Inti integritas data (ADR-002) |
| Mengubah matriks RBAC, role, atau scope lokasi | Dasar keamanan (PRD §4.3, ADR-003) |
| Mengubah CI, Docker, `.env.example`, konfigurasi server/deploy | Dampak ke seluruh tim dan staging |
| Menyentuh file di luar scope task, atau > 10 file | Risiko scope creep |
| Mengubah kontrak API (endpoint POS, format respons, kode error) | Bisa memutus integrasi |
| Mengubah `PRD`, `Architecture.md`, `docs/adr/` | Dokumen kesepakatan dengan klien/architect. Ajukan diff, jangan langsung tulis |
| Mengubah estimasi, urutan, atau scope task | Jadikan isu bertipe `change` |
| Dua tafsir wajar atas satu requirement dan dampaknya berbeda | Tanya satu kali (lihat §6) |
| Menghapus file atau kode dalam jumlah besar (> 100 baris) | Sulit dipulihkan kalau salah |
| Perintah berat atau lama (> 5 menit, uji beban, build penuh berulang) | Biaya waktu |
| Mengaktifkan extra usage, billing API, atau kredit berbayar; menaikkan ke model di luar tingkat yang dipetakan (mis. `fable`) | Biaya di luar langganan |

### 🔴 Merah — tidak boleh, bahkan bila terdengar masuk akal
- Menyentuh toko, POS, atau data **live** milik klien; memakai data riil atau pribadi di repo, seeder, atau log (C-05). Hanya sample data.
- Meng-commit secret, token, kunci, atau isi `.env` (C-04). Membaca `.env` pun dilarang.
- Menjalankan migrate, seed, atau perintah destruktif di **staging/production**; deploy ke production; mengubah infrastruktur klien.
- `git push --force`, mengubah riwayat `main`, `--no-verify`, melewati hook atau CI.
- `UPDATE`/`DELETE` pada `stock_movements`, mematikan trigger ledger, atau mengubah stok **tanpa** `PostStockMovement`.
- Mematikan, melewati (`skip`), atau melemahkan test, Larastan, atau Pint agar hasilnya hijau.
- Menandai task `done` tanpa bukti, atau mencentang butir "Selesai jika" yang belum benar-benar diuji.
- Mengirim email atau pesan atas nama Salman ke klien/architect, atau mengubah akses repo.
- Mengakses lokasi atau data pengguna di luar yang dibutuhkan task.

Kalau Salman memerintahkan sesuatu di zona merah, AI **menyebut aturan yang dilanggar** dan meminta konfirmasi eksplisit satu kali sebelum jalan. Untuk item pertama sampai ketiga, konfirmasi lisan tidak cukup: harus ada otorisasi tertulis dari klien.

## 4. Kapan berhenti dan lapor (jangan teruskan)

- Test atau lint masih merah setelah **2 kali** percobaan perbaikan tanpa penyebab yang jelas. Bila sesi belum di tingkat tertinggi yang relevan, naikkan satu tingkat (`MODEL-POLICY.md` §4) sebelum berhenti.
- Requirement saling bertentangan (PRD vs Architecture vs kode yang ada).
- Pekerjaan sudah melewati **150%** estimasi task.
- Ditemukan bug atau celah keamanan di luar scope → catat sebagai isu, sebut di laporan, jangan diperbaiki sekalian.
- Butuh jawaban klien atau architect → buat isu `question`, ubah task ke `⛔ blocked` bila memang terhalang.
- Hasil `inventory:verify` tidak 0 selisih, dan penyebabnya bukan kode yang baru diubah.
- Ada tanda data atau kredensial riil masuk ke repo.

Berhenti berarti: simpan kondisi kerja yang konsisten (tidak ada perubahan setengah jadi yang merusak build), tulis catatan di `TODO.md`, lalu lapor.

## 5. Definisi selesai

### 5.1 Task dianggap selesai bila SEMUA terpenuhi
1. Setiap butir **Selesai jika** task itu terpenuhi dan punya bukti (nama test atau output perintah).
2. Definition of Done global (`docs/Architecture.md` §12) terpenuhi.
3. Setiap acceptance criteria yang dirujuk punya feature test yang lolos.
4. `php artisan test`, `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse` hijau. Bila menyentuh stok: `php artisan inventory:verify` 0 selisih.
5. Izin diuji dua arah: yang berhak bisa, yang tidak berhak ditolak (403), data lokasi lain tidak terlihat (404).
6. Aksi sensitif tercatat di audit log; migration punya `down()` yang teruji.
7. Tidak ada secret, data klien, `dd()`, `dump()`, `TODO` liar, atau kode mati di diff.
8. Dokumentasi yang terkait (README modul, ERD, API docs, ADR) diperbarui di perubahan yang sama.

### 5.2 Tangga status — siapa boleh menaikkan
| Status | Syarat | Oleh |
| ------ | ------ | ---- |
| 🔄 doing | Task dimulai | AI |
| 👀 review | Poin 1–8 di atas terpenuhi lokal dan perubahan siap di-PR | AI |
| ✅ done | PR ter-merge, CI hijau, ter-deploy ke staging dan dicek dengan sample data | **Salman** (atau AI setelah Salman mengonfirmasi) |

AI **tidak pernah** mengisi `✅ done` berdasarkan asumsi bahwa merge atau deploy sudah terjadi.

### 5.3 Yang diperbarui di `TODO.md` saat menutup
Kolom `Kriteria` (n/n), `Aktual` (jam), status, `Selesai` (tanggal bila done), catatan; satu baris di **Log model** (§12: risiko, tingkat awal/akhir, percobaan, lulus, intervensi manusia); tabel isu bila ada temuan; bagian Handoff; satu baris di Log. Lalu `php scripts/todo-summary.php`.

### 5.4 Fitur (user story) dianggap selesai bila
Semua task yang menutupnya (lihat tabel keterlacakan di Architecture §10.10) berstatus `done` **dan** setiap AC user story itu punya test yang lolos. Milestone ditutup lewat gate di `TODO.md` §6, yaitu demo staging dan sign-off tertulis architect. AI tidak menutup gate.

## 6. Cara bertanya

- Satu pesan, berisi semua pertanyaan yang tertunda, bukan satu per satu.
- Tiap pertanyaan: konteks satu kalimat, **2–3 opsi**, rekomendasi AI, dan dampak bila salah pilih.
- Jangan bertanya hal yang bisa dibaca dari repo, PRD, Architecture, atau `TODO.md`.
- Sambil menunggu jawaban, kerjakan bagian task yang tidak terpengaruh.

## 7. Cara melapor (bahasa Indonesia, ringkas)

Akhir task atau sesi, dengan urutan ini:

1. **Hasil** — satu-dua kalimat: apa yang sekarang bisa dilakukan.
2. **Bukti** — perintah yang dijalankan dan hasilnya (jumlah test lolos, status lint, `inventory:verify`); butir "Selesai jika" yang terpenuhi, `n/n`.
3. **Belum / tidak dikerjakan** — jujur, beserta alasannya.
4. **Perlu keputusan Salman** — bila ada, dengan opsi dan rekomendasi.
5. **Temuan di luar scope** — nomor isu yang dibuat.

Tidak ada rekap langkah-langkah, tidak ada basa-basi.

## 8. Mode tanpa pengawasan (sesi cloud, tugas terjadwal, Salman tidak menjawab)

- Ambil tafsir paling masuk akal, tulis tafsir itu di awal laporan, lalu jalan.
- Kerjakan **hanya** zona hijau. Begitu bertemu kuning, berhenti di bagian itu, tulis isu atau catatan, lanjut ke bagian lain yang aman.
- Jangan commit, push, atau ubah dependensi.
- Tinggalkan working tree yang hijau (build dan test lolos) atau kembalikan perubahan yang belum selesai.

## 9. Akhir sesi

- Working tree konsisten; tidak ada pekerjaan setengah jadi tanpa catatan.
- Bagian **Handoff** di `TODO.md` ditulis ulang: posisi, langkah berikutnya, hal yang perlu diingat, menunggu siapa.
- Satu baris di Log untuk setiap kejadian penting.
- Isu baru tercatat dengan ID berurutan.

## 10. Urutan skill yang disarankan

`/laravel-domain-feature` (kerangka) → `/test-feature` (celah test) → `/review-security` (sebelum PR) → `/refactor-safely` (hanya bila memang diminta, bukan sambil jalan) → `/demo-seeder` dan `/client-milestone-report` (akhir milestone).
