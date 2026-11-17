# Sistem Informasi Manajemen Keuangan Kas Berbasis Web dengan Integrasi AI

**Kelompok 4 — Proyek Rekayasa Perangkat Lunak**

## 1. Deskripsi Proyek

Sistem Informasi Manajemen Keuangan Kas Berbasis Web dengan Integrasi Artificial Intelligence (AI) merupakan aplikasi yang dikembangkan untuk membantu proses pengelolaan keuangan kas secara lebih terstruktur.

Aplikasi ini menyediakan fitur pengelolaan anggota, pencatatan transaksi, pembayaran iuran, pengelolaan kategori, laporan keuangan, serta AI Assistant yang membantu pengguna memperoleh informasi melalui percakapan.

## 2. Tujuan Proyek

- Mempermudah pencatatan pemasukan dan pengeluaran kas.
- Membantu pengelolaan data anggota dan pembayaran iuran.
- Menyediakan informasi saldo dan laporan keuangan.
- Mengintegrasikan AI Assistant untuk membantu menjawab pertanyaan terkait informasi sistem.

## 3. Fitur Utama

- Login dan autentikasi pengguna.
- Dashboard Admin dan Dashboard Anggota.
- Pengelolaan data anggota.
- Pencatatan transaksi pemasukan dan pengeluaran.
- Pengelolaan pembayaran iuran.
- Pengelolaan kategori transaksi.
- Laporan keuangan.
- AI Assistant / Chatbot++.
- Pengaturan sistem.

## 4. Teknologi yang Digunakan

- **Backend:** PHP
- **Arsitektur:** Model-View-Controller (MVC)
- **Database:** MySQL
- **AI Provider:** NVIDIA NIM
- **Version Control:** Git dan GitHub
- **CI:** GitHub Actions

## 5. Struktur Proyek

Struktur utama direktori proyek:

```text
project/
├── app/
│   ├── config/
│   ├── controllers/
│   ├── core/
│   ├── models/
│   └── views/
├── public/
├── tests/
├── .github/
│   └── workflows/
│       └── ci.yml
├── .gitignore
└── README.md
```

## 6. Persyaratan Sistem

- PHP 8.3
- MySQL
- Web server (Apache atau lingkungan server PHP yang sesuai)
- Browser modern

## 7. Instalasi dan Konfigurasi

1. Clone repository:

```bash
git clone https://github.com/linsky-wq/sistem-informasi-keuangan-kas-ai.git
```

2. Masuk ke direktori proyek:

```bash
cd sistem-informasi-keuangan-kas-ai
```

3. Buat database MySQL dengan nama:

```text
db_kas_ai
```

4. Atur konfigurasi database dan layanan AI melalui file `.env` berdasarkan format yang tersedia pada `.env.example`.

5. Pastikan konfigurasi database dan kredensial layanan AI sudah sesuai dengan lingkungan lokal.

6. Jalankan aplikasi menggunakan web server yang sesuai dengan konfigurasi proyek.

## 8. Pengujian

Pengujian dilakukan menggunakan skrip pengujian PHP yang tersedia pada direktori `tests/`.

Contoh menjalankan pengujian integrasi NVIDIA:

```bash
php tests/test_nvidia_integration.php
```

Pengujian tersebut memeriksa pemilihan provider AI dan mekanisme fallback.

## 9. Continuous Integration (CI)

Proyek menggunakan GitHub Actions dengan konfigurasi:

```text
.github/workflows/ci.yml
```

Workflow melakukan pemeriksaan otomatis terhadap sintaks file PHP, keberadaan `.gitignore`, dan pengecualian file `.env` dari repository.

## 10. Repository

GitHub Repository:

https://github.com/linsky-wq/sistem-informasi-keuangan-kas-ai

---

**Pengembang:** Kelompok 4  
**Nama:** Linsky Molisa Ramandhani  
**NIM:** 41257202
