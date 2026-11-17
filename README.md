# Sistem Informasi Manajemen Keuangan Kas Berbasis Web dengan Integrasi Artificial Intelligence (AI)

**Proyek Rekayasa Perangkat Lunak — Kelompok 4**

| Identitas | Keterangan |
|---|---|
| Nama | Linsky Molisa Ramandhani |
| NIM | 41257202 |
| Kelompok | 4 |
| Repository | [GitHub Repository](https://github.com/linsky-wq/sistem-informasi-keuangan-kas-ai) |

---

## 1. Deskripsi Proyek

Sistem Informasi Manajemen Keuangan Kas Berbasis Web dengan Integrasi Artificial Intelligence (AI) merupakan aplikasi untuk membantu pengelolaan keuangan kas secara lebih terstruktur melalui satu sistem berbasis web.

Aplikasi mencakup pengelolaan anggota, pencatatan transaksi pemasukan dan pengeluaran, pembayaran iuran, kategori transaksi, laporan keuangan, serta AI Assistant (Chatbot++) untuk membantu pengguna memperoleh informasi melalui percakapan berdasarkan konteks dan data yang tersedia pada sistem.

## 2. Tujuan Proyek

- Mempermudah pencatatan pemasukan dan pengeluaran kas.
- Membantu pengelolaan data anggota dan pembayaran iuran.
- Menyediakan informasi keuangan dan laporan dalam satu aplikasi.
- Menghadirkan AI Assistant untuk membantu menjawab pertanyaan terkait informasi sistem.
- Mendukung pengembangan yang terstruktur melalui version control dan pemeriksaan kode otomatis.

## 3. Fitur Utama

- **Login dan autentikasi:** akses pengguna ke dalam aplikasi.
- **Dashboard:** menampilkan ringkasan informasi.
- **Manajemen anggota:** pengelolaan data anggota.
- **Transaksi:** pencatatan pemasukan dan pengeluaran kas.
- **Pembayaran:** pengelolaan informasi pembayaran iuran.
- **Kategori:** pengelolaan kategori transaksi.
- **Laporan:** penyajian informasi laporan keuangan.
- **AI Assistant / Chatbot++:** membantu menjawab pertanyaan mengenai informasi yang tersedia di sistem.
- **Pengaturan:** pengelolaan pengaturan aplikasi.

## 4. Prototype UI/UX (Figma)

Prototype dibuat menggunakan Figma untuk memperlihatkan rancangan tampilan dan alur navigasi sebelum implementasi aplikasi.

Prototype terdiri dari sepuluh halaman:

1. Login
2. Dashboard Admin
3. Anggota
4. Transaksi
5. Pembayaran
6. Laporan
7. Kategori
8. AI Assistant
9. Pengaturan
10. Dashboard Anggota

**Link Prototype Figma:**

[Klik di sini untuk melihat Prototype UI/UX Sistem Informasi Manajemen Keuangan Kas](https://www.figma.com/proto/i2HCgaFQ0DuMnBLhr2wiZ2/Prototype-UIUX---Sistem-Informasi-Manajemen-Keuangan-Kas-Berbasis-Web-dengan-Integrasi-AI?node-id=11-45&t=4UqqUwVrGb4oKTOh-1)

## 5. Teknologi yang Digunakan

| Bagian | Teknologi |
|---|---|
| Backend | PHP |
| Arsitektur | Model-View-Controller (MVC) |
| Database | MySQL |
| Integrasi AI | NVIDIA NIM |
| Version Control | Git dan GitHub |
| Continuous Integration | GitHub Actions |
| Runtime Pemeriksaan CI | PHP 8.3 |

## 6. Struktur Repository

```text
sistem-informasi-keuangan-kas-ai/
├── .github/
│   └── workflows/
│       └── ci.yml
├── app/
├── assets/
├── database/
├── docs/
├── scratch/
├── tests/
├── .env.example
├── .gitignore
├── .htaccess
├── index.php
└── README.md
```

Direktori `app/` berisi bagian utama aplikasi, `tests/` berisi skrip pengujian, dan konfigurasi GitHub Actions berada di `.github/workflows/ci.yml`.

## 7. Persyaratan Lingkungan

- PHP 8.3 atau versi yang kompatibel dengan source code.
- MySQL.
- Web server atau lingkungan PHP lokal yang sesuai dengan konfigurasi proyek.
- Browser modern.
- Kredensial layanan AI apabila ingin mengaktifkan integrasi NVIDIA NIM.

## 8. Instalasi dan Konfigurasi

### 8.1 Clone Repository

```bash
git clone https://github.com/linsky-wq/sistem-informasi-keuangan-kas-ai.git
cd sistem-informasi-keuangan-kas-ai
```

### 8.2 Persiapan Database

1. Jalankan layanan MySQL.
2. Buat database dengan nama `db_kas_ai`.
3. Periksa folder `database/` untuk menemukan file SQL proyek.
4. Impor file SQL yang sesuai ke database tersebut.

### 8.3 Konfigurasi Lingkungan

1. Salin `.env.example` menjadi `.env` pada lingkungan lokal.
2. Isi konfigurasi database sesuai MySQL lokal.
3. Isi konfigurasi layanan AI sesuai kebutuhan dan format konfigurasi aplikasi.
4. Simpan kredensial rahasia hanya di `.env` lokal.

**Penting:** Jangan mengunggah `.env`, API key, password database, atau kredensial rahasia ke GitHub. Repository hanya boleh berisi `.env.example` tanpa nilai rahasia sebenarnya.

### 8.4 Menjalankan Aplikasi

Jalankan aplikasi melalui web server lokal yang sesuai dengan konfigurasi proyek, misalnya Apache pada lingkungan pengembangan PHP.

Akses alamat lokal sesuai document root dan pengaturan server yang digunakan.

## 9. Pengujian

Skrip pengujian PHP tersedia di direktori `tests/`.

Salah satu skrip untuk memeriksa pemilihan provider AI dan mekanisme fallback adalah:

```bash
php tests/test_nvidia_integration.php
```

Hasil pengujian yang pernah dicatat selama proses pengembangan:

```text
3 passed | 0 failed
```

Pengujian ini memeriksa pemilihan provider dan mekanisme fallback pada kondisi tertentu. Hasil tersebut tidak dengan sendirinya membuktikan bahwa permintaan HTTP langsung ke layanan NVIDIA berhasil.

Untuk memeriksa sintaks file PHP secara lokal melalui PowerShell:

```powershell
Get-ChildItem app,tests -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

## 10. Continuous Integration (GitHub Actions)

Repository menggunakan GitHub Actions dengan file workflow:

```text
.github/workflows/ci.yml
```

Workflow berjalan pada perubahan ke branch `main` dan Pull Request sesuai konfigurasi workflow.

Pemeriksaan mencakup:

- Sintaks file PHP menggunakan PHP 8.3.
- Keberadaan file `.gitignore`.
- Pengecualian file `.env` dari repository.

Workflow ini merupakan pemeriksaan otomatis (Continuous Integration/CI), bukan deployment otomatis ke server produksi.

Status workflow dapat dilihat melalui tab **Actions** pada repository GitHub.

## 11. Dokumentasi Proyek

Dokumen pendukung proyek dapat ditempatkan pada direktori `docs/`, meliputi:

- Dokumen kebutuhan (PRD/SRS) Tahap 1.
- Dokumen perancangan sistem (SDD) Tahap 2.
- Laporan konstruksi, integrasi AI, pengujian, dan CI Tahap 3.
- Laporan akhir proyek.
- Screenshot aplikasi dan bukti pengujian.

## 12. Tautan Proyek

- **GitHub Repository:** [Buka Repository Proyek](https://github.com/linsky-wq/sistem-informasi-keuangan-kas-ai)
- **Prototype Figma:** [Lihat Prototype UI/UX](https://www.figma.com/proto/i2HCgaFQ0DuMnBLhr2wiZ2/Prototype-UIUX---Sistem-Informasi-Manajemen-Keuangan-Kas-Berbasis-Web-dengan-Integrasi-AI?node-id=11-45&t=4UqqUwVrGb4oKTOh-1)


**Pengembang:** Kelompok 4  
**Nama:** Linsky Molisa Ramandhani  
**NIM:** 41257202
