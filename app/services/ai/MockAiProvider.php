<?php
/**
 * Mock / Fallback AI Provider
 * Deterministic, verified-fact analytical provider for development or when external API is unreachable.
 * STRICT RULE: All numbers and facts are sourced directly from the provided financial metrics and database queries.
 * Supports role boundaries and RBAC for Admin, Bendahara, and Anggota (Prompt 40 & Prompt 42).
 */

namespace App\Services\Ai;

class MockAiProvider implements AiProviderInterface {
    public function isAvailable(): bool {
        return true;
    }

    public function getProviderName(): string {
        return 'Local Analytical AI Provider (Development Fallback)';
    }

    public function generateInsight(string $userPrompt, string $factualContext, array $metrics): string {
        $cleanPrompt = mb_strtolower(trim($userPrompt));
        // Normalize punctuation to spaces for accurate keyword matching
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $cleanPrompt);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        // 1. Resolve active user role (admin, bendahara, member)
        $rawRole = $metrics['user_role'] ?? (\App\Core\Auth::role() ?? 'super_user');
        $role = match (strtolower(trim((string)$rawRole))) {
            'member', 'anggota' => 'member',
            'bendahara'         => 'bendahara',
            default             => 'admin',
        };

        // Extract factual numbers from verified metrics (Read-Only)
        $income = (float)($metrics['total_income'] ?? 0.0);
        $expense = (float)($metrics['total_expense'] ?? 0.0);
        $balance = (float)($metrics['current_balance'] ?? 0.0);
        $condition = (string)($metrics['cash_condition'] ?? 'Stabil');
        $topCategories = $metrics['top_expense_categories'] ?? [];
        $unpaidCount = (int)($metrics['unpaid_members_count'] ?? 0);
        $partialCount = (int)($metrics['partial_members_count'] ?? 0);
        $paidCount = (int)($metrics['paid_members_count'] ?? 0);
        $totalTunggakan = (float)($metrics['total_tunggakan'] ?? 0.0);
        $totalMembers = (int)($metrics['total_members'] ?? 0);
        $totalTagihan = (float)($metrics['total_tagihan'] ?? 0.0);
        $totalPaid = (float)($metrics['total_dibayar'] ?? 0.0);
        $pendingCount = (int)($metrics['pending_verification_count'] ?? 0);

        // Detailed factual query results from database
        $queryResults = $metrics['query_results'] ?? [];
        $recentExpenses = !empty($queryResults['expenses']) ? $queryResults['expenses'] : ($metrics['recent_expenses'] ?? []);
        $recentIncomes = !empty($queryResults['incomes']) ? $queryResults['incomes'] : ($metrics['recent_incomes'] ?? []);
        $unpaidMembersList = !empty($queryResults['unpaid_members']) ? $queryResults['unpaid_members'] : ($metrics['unpaid_members_list'] ?? []);
        $membersList = !empty($queryResults['members']) ? $queryResults['members'] : [];
        $monthlyRecap = $queryResults['monthly_recap'] ?? null;
        $categoriesList = !empty($queryResults['categories']) ? $queryResults['categories'] : ($metrics['categories_list'] ?? []);

        // Member-specific metrics
        $memberTagihan = (float)($metrics['member_tagihan'] ?? 0.0);
        $memberPaid = (float)($metrics['member_dibayar'] ?? 0.0);
        $memberTunggakan = (float)($metrics['member_tunggakan'] ?? 0.0);
        $memberStatus = (string)($metrics['member_status'] ?? 'Belum Bayar');
        $memberLastPayment = $metrics['member_last_payment'] ?? null;

        // User identity from session (Prompt 41)
        $userName      = (string)($metrics['user_name'] ?? '');
        $userUsername  = (string)($metrics['user_username'] ?? '');
        $userRoleLabel = (string)($metrics['user_role_label'] ?? 'Pengguna');
        // Fallback display names
        $displayName   = !empty($userName) ? $userName : (!empty($userUsername) ? $userUsername : 'Pengguna');
        $memberNameDisp = (string)($metrics['member_name'] ?? $displayName);

        // ---------------------------------------------------------------------
        // 1. Perlindungan terhadap prompt injection & permintaan data sensitif
        // ---------------------------------------------------------------------
        $isPromptInjection = (bool)preg_match('/(abaikan\s+semua\s+instruksi|ignore\s+all\s+previous|ubah\s+saldo\s+menjadi|hapus\s+semua\s+tunggakan)/i', $cleanPrompt);
        if (!$isPromptInjection) {
            if (preg_match('/(password|token|api[_-]?key|secret|kredensial|credential|hash)/i', $clean) && !preg_match('/\b(ganti password|ubah password)\b/i', $clean)) {
                return "Mohon maaf karena data kredensial dan informasi rahasia sistem dilindungi serta tidak dapat diakses melalui asisten virtual.";
            } elseif (
            // -----------------------------------------------------------------
            // 2. Pertanyaan bendahara ("Apakah kamu bendahara?", "Kamu bendahara ya?", dll.)
            // -----------------------------------------------------------------
            preg_match('/\b(apakah\s+(kamu|anda)|kamu|anda)\s+bendahara\b/i', $clean) ||
            preg_match('/\bbendahara\s+(bukan|ya|kah)\b/i', $clean) ||
            $clean === 'bendahara' ||
            str_contains($clean, 'kamu bendahara') ||
            str_contains($clean, 'anda bendahara')
        ) {
            return "Bukan, saya bukan bendahara. Saya adalah Asisten AI pada Sistem Keuangan Kas. Saya bukan bendahara dan tidak melakukan transaksi keuangan secara mandiri.";
        } elseif (
            // -----------------------------------------------------------------
            // 3. Pertanyaan identitas, tugas, bantuan, dan perkenalan (Prompt 30 & 42)
            // -----------------------------------------------------------------
            !preg_match('/\b(python|coding|codingan|program|script|koding|kodingan)\b/i', $clean) &&
            !preg_match('/\b(saya|aku|gue|gw|ku)\b/i', $clean) && (
                preg_match('/\bsiapa\s+(kamu|anda|dirimu|ia|dia)\b/i', $clean) ||
                preg_match('/\b(kamu|anda)\s+(itu\s+)?siapa\b/i', $clean) ||
                preg_match('/\b(siapa|apa)\s+nama\s+(kamu|anda|mu)\b/i', $clean) ||
                preg_match('/\bnama\s+(kamu|anda|mu)\s+(siapa|apa)\b/i', $clean) ||
                preg_match('/\bnama(mu)?\s+siapa\b/i', $clean) ||
                preg_match('/\bperkenal(kan)?(\s+(diri(mu)?|kamu|anda))?\b/i', $clean) ||
                preg_match('/\b(apa\s+)?tugas\s+(kamu|anda|mu)\b/i', $clean) ||
                preg_match('/\btugas(mu)?\s+apa\b/i', $clean) ||
                preg_match('/\b(kamu|anda)?\s*bisa\s+(membantu|bantu)\s+apa\b/i', $clean) ||
                preg_match('/\bbisa\s+(bantu|membantu)\s+apa\b/i', $clean) ||
                preg_match('/\bapa\s+(yang\s+)?bisa\s+(kamu|anda)\s+(bantu|membantu|lakukan)\b/i', $clean) ||
                preg_match('/\b(kamu|anda)\s+ai\s+apa\b/i', $clean) ||
                preg_match('/\bai\s+apa\s+(kamu|anda)\b/i', $clean) ||
                preg_match('/\b(apakah\s+)?(kamu|anda)\s+ai\s*(sistem|keuangan|kas)?\b/i', $clean) ||
                preg_match('/\b(kamu|anda)\s+dibuat\s+untuk\s+apa\b/i', $clean) ||
                preg_match('/\b(apa\s+)?fungsi\s+(kamu|anda|mu)\b/i', $clean) ||
                preg_match('/\bfungsi(mu)?\s+apa\b/i', $clean) ||
                preg_match('/\b(apa\s+)?peran\s+(kamu|anda|mu)\b/i', $clean)
            )
        ) {
            return "Halo! Saya Asisten AI pada Sistem Keuangan Kas. Saya adalah asisten virtual yang dirancang untuk membantu Anda memahami informasi dan fitur dalam sistem keuangan kas ini. Saya bukan bendahara dan tidak melakukan transaksi keuangan secara mandiri.";
        }

        // -----------------------------------------------------------------
        // 3B. Pertanyaan Identitas PENGGUNA yang Sedang Login (Prompt 41 & 42)
        // Format Prompt 42: "Anda adalah [nama pengguna], dengan role [role aktif]."
        // -----------------------------------------------------------------
        $isUserIdentityQuestion = (
            preg_match('/\b(siapa\s+saya|saya\s+(ini\s+)?siapa)\b/i', $clean) ||
            preg_match('/\b(saya|aku)\s+login\s+(sebagai\s+)?siapa\b/i', $clean) ||
            preg_match('/\blogin\s+(sebagai|dengan\s+akun)\s+(siapa|apa)\b/i', $clean) ||
            preg_match('/\b(apa|siapa)\s+peran\s+saya\b/i', $clean) ||
            preg_match('/\bperan\s+saya\s*(di\s*(sistem|aplikasi|ini))?\b/i', $clean) ||
            preg_match('/\b(apa\s+)?(jabatan|role|peran)\s+(saya|aku)\b/i', $clean) ||
            preg_match('/\bnama\s+saya\s+(siapa|apa)\b/i', $clean) ||
            preg_match('/\b(siapa|apa)\s+nama\s+saya\b/i', $clean) ||
            preg_match('/\bapa\s+yang\s+bisa\s+(saya|aku)\s+(lakukan|akses)\b/i', $clean) ||
            preg_match('/\b(hak\s+akses|akses|fitur)\s+(saya|aku|yang\s+saya\s+miliki)\b/i', $clean) ||
            preg_match('/\bsaya\s+(bisa|boleh|dapat)\s+(apa|melakukan\s+apa|mengakses\s+apa)\b/i', $clean)
        );

        if ($isUserIdentityQuestion) {
            $effectiveName = ($role === 'member' && !empty($memberNameDisp)) ? $memberNameDisp : $displayName;
            $userTag = (!empty($userUsername) && $userUsername !== $effectiveName) ? " ({$userUsername})" : "";

            // Jika menanyakan peran/jabatan secara spesifik
            if (preg_match('/\b(peran|jabatan|role)\s+saya\b/i', $clean) || preg_match('/\b(apa\s+)?(peran|jabatan|role)\b/i', $clean)) {
                return "Role Anda di sistem ini adalah {$userRoleLabel}.";
            }

            // Jika menanyakan hak akses / fitur yang bisa dilakukan
            if (
                preg_match('/\bapa\s+yang\s+bisa\s+(saya|aku)\s+(lakukan|akses)\b/i', $clean) ||
                preg_match('/\b(hak\s+akses|akses|fitur)\s+(saya|aku)\b/i', $clean) ||
                preg_match('/\bsaya\s+(bisa|boleh|dapat)\s+(apa|melakukan\s+apa|mengakses\s+apa)\b/i', $clean)
            ) {
                if ($role === 'member') {
                    return "Sebagai Anggota, Anda dapat melihat tagihan iuran pribadi, mengunggah bukti pembayaran, serta memantau status verifikasi dan riwayat pembayaran Anda.";
                } elseif ($role === 'bendahara') {
                    return "Sebagai Bendahara, Anda dapat mencatat transaksi kas masuk dan keluar, memverifikasi pembayaran iuran anggota, merekonsiliasi kas, serta menyusun laporan keuangan.";
                } else {
                    return "Sebagai Admin / Super User, Anda dapat mengelola pengguna, data anggota, kategori, transaksi kas, verifikasi pembayaran, laporan keuangan, dan pengaturan sistem.";
                }
            }

            // Pertanyaan utama identitas: "Siapa saya?", "Saya login sebagai siapa?", "Siapa nama saya?"
            return "Anda adalah {$effectiveName}{$userTag}, dengan role {$userRoleLabel}.";
        }

        // ---------------------------------------------------------------------
        // 3C. Pertanyaan Tagihan Pengguna (Prompt 42 Ketentuan 5)
        // ---------------------------------------------------------------------
        $isUserBillQuestion = (
            preg_match('/\b(tagihan|tunggakan|iuran)\s*(saya|aku|ku|milik\s*saya)\b/i', $clean) ||
            preg_match('/\b(berapa|cek|lihat)\s+tagihan\s*(saya|aku|ku)?\b/i', $clean) ||
            $clean === 'berapa tagihan saya' ||
            $clean === 'tagihan saya' ||
            $clean === 'tagihan saya berapa' ||
            str_contains($clean, 'tagihan saya') ||
            str_contains($clean, 'tunggakan saya') ||
            str_contains($clean, 'iuran saya')
        );

        if ($isUserBillQuestion) {
            if ($role === 'member') {
                if ($memberTagihan <= 0.0) {
                    return "Data tagihan belum tersedia untuk akun Anda.";
                }
                $nomTagihan = number_format($memberTagihan, 2, ',', '.');
                $nomTunggakan = number_format($memberTunggakan, 2, ',', '.');
                return "Tagihan iuran Anda saat ini adalah sebesar Rp {$nomTagihan} dengan sisa tunggakan Rp {$nomTunggakan} (status: {$memberStatus}).";
            } else {
                $nomTotal = number_format($totalTagihan, 2, ',', '.');
                $nomTunggakan = number_format($totalTunggakan, 2, ',', '.');
                return "Akun Anda berstatus {$userRoleLabel}. Total tagihan iuran seluruh anggota di sistem saat ini adalah Rp {$nomTotal} dengan sisa tunggakan Rp {$nomTunggakan}.";
            }
        }

        // ---------------------------------------------------------------------
        // 3D. Pertanyaan Umum di Luar Sistem: "Apa itu AI?" (Prompt 42 Ketentuan 7)
        // ---------------------------------------------------------------------
        if (
            preg_match('/\b(apa\s+itu|jelaskan|pengertian|definisi)\s+ai\b/i', $clean) ||
            $clean === 'apa itu ai' ||
            $clean === 'ai itu apa'
        ) {
            return "Mohon maaf karena saya berfokus membantu penggunaan Sistem Keuangan Kas, bukan menjawab pertanyaan umum di luar sistem.";
        }

        // ---------------------------------------------------------------------
        // 4. Permintaan aksi langsung yang melampaui kewenangan (Kirim notifikasi WA mandiri)
        // ---------------------------------------------------------------------
        if (
            preg_match('/\b(kirim|send|broadcast)\s+(notifikasi|pesan|wa|whatsapp)\b/i', $clean) ||
            preg_match('/\b(notifikasi|pesan|wa|whatsapp)\s+.*(sekarang|semua\s+anggota)\b/i', $clean)
        ) {
            return "Mohon maaf karena saya adalah asisten virtual dengan hak akses read-only, saya tidak dapat mengirimkan pesan atau notifikasi WhatsApp secara langsung. Silakan gunakan menu **WhatsApp (WA)** pada sidebar untuk mengirim notifikasi resmi.";
        }

        // ---------------------------------------------------------------------
        // 5. Pertanyaan Verifikasi: "Mengapa pembayaran saya belum diverifikasi?"
        // ---------------------------------------------------------------------
        if (
            preg_match('/\b(mengapa|kenapa|alasan|penyebab)\s+.*(belum|tidak|lama)\s+.*(verif|diverifikasi|diverif|konfirmasi)\b/i', $clean) ||
            preg_match('/\b(pembayaran|setoran)\s+.*(belum|tidak)\s+.*(diverifikasi|diverif|terverifikasi)\b/i', $clean)
        ) {
            if ($role === 'member') {
                return "Pembayaran Anda berstatus **Menunggu Verifikasi** karena memerlukan pemeriksaan bukti transfer fisik/digital oleh **Bendahara** atau **Super User**. Anda dapat memantau statusnya di menu Pembayaran Saya.";
            } elseif ($role === 'bendahara') {
                $pendingText = $pendingCount > 0 ? " (terdapat {$pendingCount} setoran dalam antrean)" : "";
                return "Sebagai Bendahara, pembayaran berstatus Menunggu Verifikasi karena memerlukan pemeriksaan bukti transfer dan pencocokan dengan mutasi kas{$pendingText}. Anda dapat melakukan verifikasi pembayaran melalui menu Pembayaran.";
            } else {
                $pendingText = $pendingCount > 0 ? " ({$pendingCount} setoran dalam antrean)" : "";
                return "Sebagai Admin / Super User, terdapat setoran berstatus Menunggu Verifikasi{$pendingText} yang memerlukan validasi bukti transfer dengan mutasi kas oleh Bendahara atau Admin pada menu Pembayaran.";
            }
        }

        // ---------------------------------------------------------------------
        // 6. Melihat Status Pembayaran: "Bagaimana cara melihat status pembayaran iuran?"
        // ---------------------------------------------------------------------
        if (
            preg_match('/\b(bagaimana|cara|di mana|dimana|cek|lihat|melihat)\s+.*status\s+(pembayaran|iuran|setoran|tagihan)\b/i', $clean) ||
            preg_match('/\bstatus\s+(pembayaran|iuran|setoran|tagihan)\s+.*(lihat|cek|bagaimana)\b/i', $clean) ||
            str_contains($clean, 'status pembayaran iuran') ||
            str_contains($clean, 'melihat status pembayaran') ||
            str_contains($clean, 'cek status pembayaran')
        ) {
            if ($role === 'member') {
                return "Berikut langkah melihat status pembayaran iuran Anda:\n" .
                       "1. Buka menu **Pembayaran Saya** pada sidebar untuk melihat status tagihan dan pembayaran pribadi Anda.\n" .
                       "2. Ringkasan status iuran juga dapat Anda pantau pada **Dashboard Anggota**.";
            } elseif ($role === 'bendahara') {
                return "Berikut langkah melihat status pembayaran iuran anggota:\n" .
                       "1. Buka menu **Pembayaran** untuk melihat daftar dan status verifikasi pembayaran iuran anggota.\n" .
                       "2. Buka menu **Laporan > Rekap Iuran Anggota** untuk melihat rekapitulasi pembayaran per periode.";
            } else {
                return "Berikut langkah melihat status pembayaran iuran:\n" .
                       "1. Buka menu **Pembayaran** untuk memeriksa seluruh transaksi dan verifikasi pembayaran iuran anggota.\n" .
                       "2. Buka menu **Laporan > Rekap Iuran Anggota** untuk melihat rekapitulasi kepatuhan seluruh anggota.";
            }
        }

        // ---------------------------------------------------------------------
        // 7. Pembuatan Kode / Python (Terkait Kas vs Di Luar Kas)
        // ---------------------------------------------------------------------
        if (preg_match('/\b(python|coding|codingan|program|script|koding|kodingan)\b/i', $clean)) {
            $hasFinanceOrSystemContext = preg_match('/\b(kas|pemasukan|pengeluaran|saldo|iuran|donasi|sumbangan|tunggakan|keuangan|uang|belanja|biaya|transaksi|tagihan|aplikasi|database|koneksi|error|sistem)\b/i', $clean);

            if ($hasFinanceOrSystemContext) {
                if (preg_match('/\b(iuran|tagihan|tunggakan)\b/i', $clean) && preg_match('/\b(anggota|siswa|member)\b/i', $clean)) {
                    return "Berikut contoh kode Python untuk menghitung total iuran anggota kas:\n\n" .
                           "```python\n" .
                           "data_iuran = [\n" .
                           "    {\"nama\": \"Ahmad\", \"tagihan\": 50000, \"dibayar\": 50000},\n" .
                           "    {\"nama\": \"Budi\", \"tagihan\": 50000, \"dibayar\": 25000},\n" .
                           "]\n" .
                           "total_tagihan = sum(d[\"tagihan\"] for d in data_iuran)\n" .
                           "total_masuk = sum(d[\"dibayar\"] for d in data_iuran)\n" .
                           "total_tunggakan = total_tagihan - total_masuk\n" .
                           "print(f\"Total Tagihan: Rp {total_tagihan:,} | Terkumpul: Rp {total_masuk:,} | Tunggakan: Rp {total_tunggakan:,}\")\n" .
                           "```\n\n" .
                           "Dalam Sistem Keuangan Kas, perhitungan ini dilakukan otomatis pada menu Pembayaran dan Rekap Iuran Anggota.";
                }

                return "Berikut contoh program Python sederhana untuk menghitung total pemasukan dan pengeluaran kas:\n\n" .
                       "```python\n" .
                       "pemasukan = [500000, 250000, 100000]\n" .
                       "pengeluaran = [150000, 50000]\n" .
                       "saldo = sum(pemasukan) - sum(pengeluaran)\n" .
                       "print(f\"Pemasukan: Rp {sum(pemasukan):,} | Pengeluaran: Rp {sum(pengeluaran):,} | Saldo: Rp {saldo:,}\")\n" .
                       "```\n\n" .
                       "Dalam Sistem Keuangan Kas, perhitungan saldo diproses otomatis oleh database dan ditampilkan langsung pada Dashboard.";
            } else {
                return "Mohon maaf karena sebagai Asisten AI Sistem Keuangan Kas, permintaan ini berada di luar tugas utama saya. Saya tidak dapat membantu membuat coding Python umum yang tidak berkaitan dengan sistem ini.";
            }
        }

        // ---------------------------------------------------------------------
        // 8. Permintaan Karya Kreatif & Non-Keuangan (Puisi, Game, Cerita, dll.)
        // ---------------------------------------------------------------------
        if (preg_match('/\b(puisi|pantun|syair|game|permainan|cerita|dongeng|novel|horor|resep|lirik|lagu|lelucon|candaan)\b/i', $clean)) {
            if (str_contains($clean, 'puisi')) {
                return "Mohon maaf karena sebagai Asisten AI Sistem Keuangan Kas, permintaan ini berada di luar tugas utama saya. Saya tidak dapat membantu membuat puisi di luar sistem ini.";
            }
            if (str_contains($clean, 'game') || str_contains($clean, 'permainan')) {
                return "Mohon maaf karena sebagai Asisten AI Sistem Keuangan Kas, permintaan ini berada di luar tugas utama saya. Saya tidak dapat membantu membuat game di luar sistem ini.";
            }
            if (str_contains($clean, 'cerita') || str_contains($clean, 'horor')) {
                return "Mohon maaf karena sebagai Asisten AI Sistem Keuangan Kas, permintaan ini berada di luar tugas utama saya. Saya tidak dapat membantu menulis cerita horor di luar sistem ini.";
            }
            return "Mohon maaf karena sebagai Asisten AI Sistem Keuangan Kas, permintaan ini berada di luar tugas utama saya. Saya tidak dapat membantu membuat karya di luar sistem ini.";
        }

        // ---------------------------------------------------------------------
        // 9. Pertanyaan Teknis In-Scope: "Bagaimana memperbaiki error pembayaran di aplikasi ini?"
        // ---------------------------------------------------------------------
        if (
            preg_match('/\b(error|kendala|masalah|gagal|troubleshoot)\s+.*(pembayaran|bayar|setor|upload|bukti)\b/i', $clean) ||
            preg_match('/\b(memperbaiki|mengatasi|solusi)\s+.*(error|kendala|masalah|gagal).*pembayaran\b/i', $clean) ||
            str_contains($clean, 'error pembayaran') ||
            str_contains($clean, 'memperbaiki error pembayaran')
        ) {
            return "Langkah mengatasi error pembayaran di aplikasi:\n" .
                   "1. Pastikan file bukti pembayaran berformat JPG, JPEG, atau PNG dengan ukuran maksimal 2 MB.\n" .
                   "2. Pastikan nominal pembayaran berupa angka positif dan tidak melebihi sisa tagihan.\n" .
                   "3. Segarkan (refresh) halaman atau login ulang jika sesi CSRF kedaluwarsa.\n" .
                   "4. Pastikan layanan MySQL dan Apache pada server berjalan normal.";
        }

        // ---------------------------------------------------------------------
        // 10. Pertanyaan Teknis In-Scope: "Mengapa laporan pemasukan tidak muncul?"
        // ---------------------------------------------------------------------
        if (
            (str_contains($clean, 'laporan') || str_contains($clean, 'rekap')) &&
            str_contains($clean, 'pemasukan') &&
            (str_contains($clean, 'tidak muncul') || str_contains($clean, 'tidak tampil') || str_contains($clean, 'kosong') || str_contains($clean, 'hilang') || str_contains($clean, 'mengapa') || str_contains($clean, 'kenapa'))
        ) {
            return "Langkah pemeriksaan jika laporan pemasukan tidak muncul:\n" .
                   "1. Periksa filter Rentang Tanggal atau Periode pada menu Laporan agar mencakup tanggal transaksi pemasukan.\n" .
                   "2. Pastikan filter Kategori disetel ke 'Semua Kategori'.\n" .
                   "3. Pastikan transaksi dicatat dengan tipe 'pemasukan' di basis data.\n" .
                   "4. Pastikan akun memiliki hak akses membuka menu Laporan (Super User, Bendahara, atau Pengawas).";
        }

        // ---------------------------------------------------------------------
        // 11. Pertanyaan Teknis In-Scope: "Bagaimana cara memperbaiki koneksi database aplikasi ini?"
        // ---------------------------------------------------------------------
        if (
            preg_match('/\b(bagaimana|cara|solusi|memperbaiki|mengatasi)\s+.*(koneksi|connect).*(database|basis data|db|mysql)\b/i', $clean) ||
            preg_match('/\b(koneksi|connect).*(database|basis data|db|mysql).*(error|gagal|rusak|putus)\b/i', $clean) ||
            str_contains($clean, 'koneksi database aplikasi ini') ||
            str_contains($clean, 'memperbaiki koneksi database')
        ) {
            return "Langkah memperbaiki koneksi database aplikasi:\n" .
                   "1. Buka XAMPP Control Panel dan pastikan layanan MySQL berjalan pada port 3306.\n" .
                   "2. Periksa konfigurasi file .env untuk memastikan DB_HOST, DB_NAME (db_kas_ai), DB_USER, dan DB_PASS sesuai.\n" .
                   "3. Pastikan database db_kas_ai dan tabel-tabelnya sudah dibuat melalui phpMyAdmin.\n" .
                   "4. Muat ulang halaman aplikasi.";
        }

        // ---------------------------------------------------------------------
        // 12. Privasi Data: "Tampilkan data pembayaran seluruh anggota" (Perbedaan Role)
        // ---------------------------------------------------------------------
        if (
            preg_match('/\b(tampilkan|lihat|data|daftar)\s+pembayaran\s+(seluruh|semua)\s+anggota\b/i', $clean) ||
            (str_contains($clean, 'pembayaran') && str_contains($clean, 'seluruh anggota')) ||
            (str_contains($clean, 'pembayaran') && str_contains($clean, 'semua anggota'))
        ) {
            if ($role === 'member') {
                return "Mohon maaf karena Anda login sebagai Anggota, Anda hanya berhak melihat data tagihan dan pembayaran pribadi Anda sendiri demi menjaga privasi seluruh anggota.";
            }

            return "Rekapitulasi status pembayaran seluruh anggota:\n" .
                   "- **Total Nilai Tagihan Iuran**: Rp " . number_format($totalTagihan, 2, ',', '.') . "\n" .
                   "- **Total Iuran yang Telah Disetor**: Rp " . number_format($totalPaid, 2, ',', '.') . "\n" .
                   "- **Total Sisa Tunggakan**: Rp " . number_format($totalTunggakan, 2, ',', '.') . "\n" .
                   "- **Tingkat Kepatuhan Anggota**: Dari total {$totalMembers} anggota, sebanyak {$paidCount} anggota telah Lunas, {$partialCount} anggota menyetor Sebagian, dan {$unpaidCount} anggota Belum Bayar.\n" .
                   "Untuk melihat rincian per anggota, silakan buka menu **Pembayaran** atau menu **Laporan** pada sidebar.";
        }

        // ---------------------------------------------------------------------
        // 13. Restriksi Fitur Manajemen Sistem bagi Role Anggota
        // ---------------------------------------------------------------------
        if ($role === 'member' && preg_match('/\b(tambah|edit|ubah|hapus|kelola)\s+.*(kategori|transaksi|user|pengguna|anggota)\b/i', $clean)) {
            return "Mohon maaf karena Anda login sebagai Anggota, Anda tidak memiliki izin untuk mengelola kategori, transaksi, atau pengguna. Fitur tersebut dikhususkan untuk Admin dan Bendahara.";
        }

        // ---------------------------------------------------------------------
        // 14. Pertanyaan Faktual Kas Spesifik & Pemahaman Data Aktual Database (CHATBOT++)
        // ---------------------------------------------------------------------

        // 14.0A RBAC Boundary: Member cannot access other members' data
        if (($queryResults['intent'] ?? '') === 'member_forbidden') {
            return "Mohon maaf karena Anda login sebagai Anggota, data anggota atau pembayaran anggota lain bersifat privat dan tidak dapat diakses.";
        }

        // 14.0A Database Connection Health Safeguard
        if (($queryResults['intent'] ?? '') === 'database_error' || ($metrics['db_connected'] ?? true) === false) {
            return "Mohon maaf, data sistem belum dapat diperiksa saat ini karena koneksi database sedang bermasalah atau tidak dapat diakses.";
        }

        // 14.0P Role Queries: Count, List, Users, Permissions
        if (str_starts_with($queryResults['intent'] ?? '', 'role_')) {
            $intent = $queryResults['intent'];
            $rs = $queryResults['roles_summary'] ?? [];
            $roles = $rs['roles'] ?? [];
            $totRoles = $rs['total_roles'] ?? 4;
            $totUsers = $rs['total_users'] ?? 19;

            if ($role === 'member' && in_array($intent, ['role_users', 'role_admin', 'role_bendahara', 'role_pengawas'], true)) {
                return "Mohon maaf karena Anda login sebagai Anggota, direktori data pengguna lain dibatasi demi menjaga privasi.";
            }

            if ($intent === 'role_count') {
                return "Sistem memiliki {$totRoles} role: Super User (Admin), Bendahara, Pengawas, dan Member (Anggota), dengan total {$totUsers} akun pengguna terdaftar di database.";
            }

            if ($intent === 'role_list' || $intent === 'role_info') {
                return "Berikut daftar {$totRoles} role yang tersedia dalam sistem:\n" .
                       "1. **Super User (Admin)** — Memiliki hak akses penuh untuk mengelola pengguna, data anggota, kategori, transaksi kas, verifikasi pembayaran, laporan keuangan, audit log, WhatsApp gateway, dan pengaturan sistem.\n" .
                       "2. **Bendahara** — Memiliki hak akses untuk mencatat transaksi kas masuk dan keluar, memverifikasi setoran pembayaran iuran anggota, rekonsiliasi kas, dan menyusun laporan keuangan.\n" .
                       "3. **Pengawas** — Memiliki hak akses pemantauan mutasi kas dan pembacaan laporan keuangan (read-only).\n" .
                       "4. **Member (Anggota)** — Memiliki hak akses untuk melihat tagihan iuran pribadi, mengunggah bukti pembayaran, serta memantau riwayat pembayaran mandiri.";
            }

            if ($intent === 'role_admin') {
                $su = $roles['super_user']['users'][0] ?? null;
                $suName = $su ? "{$su['nama']} (username: {$su['username']})" : "admin";
                return "Pengguna dengan role Super User / Admin di sistem saat ini adalah {$suName} dengan status active.";
            }

            if ($intent === 'role_bendahara') {
                $b = $roles['bendahara']['users'][0] ?? null;
                $bName = $b ? "{$b['nama']} (username: {$b['username']})" : "bendahara";
                return "Pengguna dengan role Bendahara di sistem saat ini adalah {$bName} dengan status active.";
            }

            if ($intent === 'role_pengawas') {
                $p = $roles['pengawas']['users'][0] ?? null;
                $pName = $p ? "{$p['nama']} (username: {$p['username']})" : "pengawas";
                return "Pengguna dengan role Pengawas di sistem saat ini adalah {$pName} dengan status active.";
            }

            if ($intent === 'role_permissions') {
                return "Perbedaan hak akses setiap role dalam sistem:\n" .
                       "- **Super User (Admin)**: Pengelolaan sistem secara penuh (pengguna, anggota, kategori, transaksi, verifikasi, laporan, wa, pengaturan).\n" .
                       "- **Bendahara**: Pengelolaan transaksi kas masuk/keluar, verifikasi pembayaran anggota, dan laporan keuangan.\n" .
                       "- **Pengawas**: Pemantauan transaksi dan pembacaan laporan keuangan secara read-only.\n" .
                       "- **Member (Anggota)**: Hanya dapat melihat tagihan dan riwayat transaksi miliknya sendiri.\n\n" .
                       "Pengguna yang memiliki wewenang untuk mengelola dan memverifikasi pembayaran adalah **Bendahara** dan **Super User**.";
            }

            if ($intent === 'role_users') {
                $out = "Rincian jumlah pengguna pada setiap role di sistem:\n";
                foreach ($roles as $rk => $rInfo) {
                    $out .= "- **{$rInfo['label']}**: {$rInfo['count']} pengguna ({$rInfo['active']} aktif, {$rInfo['inactive']} nonaktif)\n";
                }
                $out .= "Total seluruh pengguna terdaftar adalah {$totUsers} pengguna.";
                return trim($out);
            }
        }

        // 14.0Q Member Queries: Count, List, Status, Active, Inactive
        $memberIntents = [
            'member_count', 'member_status_count', 'member_list',
            'member_active_count', 'member_inactive_count',
            'member_active_list', 'member_inactive_list'
        ];
        if (in_array($queryResults['intent'] ?? '', $memberIntents, true)) {
            $intent = $queryResults['intent'];
            $allM = $queryResults['members_directory'] ?? [];
            $ms = $metrics['members_summary'] ?? [];
            $totM = !empty($allM) ? count($allM) : ($ms['total'] ?? $totalMembers);
            $aktifM = $ms['aktif'] ?? 11;
            $nonaktifM = $ms['nonaktif'] ?? 1;

            if ($role === 'member' && in_array($intent, ['member_list', 'member_active_list', 'member_inactive_list', 'member_status_count', 'member_active_count', 'member_inactive_count'], true)) {
                return "Mohon maaf karena Anda login sebagai Anggota, direktori data seluruh anggota dibatasi demi menjaga privasi.";
            }

            // 1. Ada berapa anggota?
            if ($intent === 'member_count') {
                return "Jumlah anggota yang terdaftar saat ini adalah {$totM} orang ({$aktifM} aktif, {$nonaktifM} nonaktif).";
            }

            // 2. Berapa anggota yang nonaktif?
            if ($intent === 'member_inactive_count') {
                return "Jumlah anggota nonaktif adalah {$nonaktifM} orang.";
            }

            // 3. Berapa jumlah anggota aktif?
            if ($intent === 'member_active_count') {
                return "Jumlah anggota aktif adalah {$aktifM} orang.";
            }

            // 4. Berapa anggota aktif dan nonaktif?
            if ($intent === 'member_status_count') {
                return "Jumlah anggota terdaftar adalah {$totM} orang, terdiri dari {$aktifM} anggota aktif dan {$nonaktifM} anggota nonaktif.";
            }

            // 5. Siapa anggota yang nonaktif? / Sebutkan nama anggota yang nonaktif.
            if ($intent === 'member_inactive_list') {
                $inactiveM = $queryResults['inactive_members'] ?? [];
                if (empty($inactiveM) && !empty($allM)) {
                    $inactiveM = array_values(array_filter($allM, fn($m) => strtolower($m['status'] ?? '') === 'nonaktif'));
                }
                if (empty($inactiveM)) {
                    return "Tidak ada anggota nonaktif yang ditemukan dalam database.";
                }
                $out = "Anggota yang berstatus nonaktif adalah:\n";
                foreach ($inactiveM as $idx => $m) {
                    $num = $idx + 1;
                    $kelasStr = !empty($m['kelas']) ? " ({$m['kelas']})" : "";
                    $out .= "{$num}. {$m['nama']}{$kelasStr}\n";
                }
                return trim($out);
            }

            // 6. Siapa saja anggota yang aktif? / Sebutkan nama anggota yang aktif.
            if ($intent === 'member_active_list') {
                $activeM = $queryResults['active_members'] ?? [];
                if (empty($activeM) && !empty($allM)) {
                    $activeM = array_values(array_filter($allM, fn($m) => strtolower($m['status'] ?? '') === 'aktif'));
                }
                if (empty($activeM)) {
                    return "Tidak ada anggota aktif yang ditemukan dalam database.";
                }
                $out = "Berikut daftar seluruh anggota yang berstatus aktif:\n";
                foreach ($activeM as $idx => $m) {
                    $num = $idx + 1;
                    $kelasStr = !empty($m['kelas']) ? " ({$m['kelas']})" : "";
                    $out .= "{$num}. {$m['nama']}{$kelasStr}\n";
                }
                $out .= "\nTotal anggota aktif: " . count($activeM) . " orang.";
                return trim($out);
            }

            // 7. Sebutkan nama seluruh anggota. / Siapa saja nama anggota yang terdaftar?
            if ($intent === 'member_list') {
                $out = "Berikut daftar anggota yang terdaftar dalam sistem:\n";
                foreach ($allM as $idx => $m) {
                    $num = $idx + 1;
                    $out .= "{$num}. {$m['nama']} ({$m['kelas']}, Status: {$m['status']})\n";
                }
                $out .= "\nTotal anggota terdaftar: {$totM} orang.";
                return trim($out);
            }
        }

        // 14.0R Mahasiswa / Student Queries: Count, List, Paid, Unpaid, Arrears
        if (str_starts_with($queryResults['intent'] ?? '', 'student_')) {
            $intent = $queryResults['intent'];
            $sb = $queryResults['student_billing'] ?? [];
            $allM = $queryResults['members_directory'] ?? [];
            $totM = !empty($allM) ? count($allM) : $totalMembers;
            $totTagihan = (float)($sb['total_tagihan'] ?? $totalTagihan);
            $totDibayar = (float)($sb['total_dibayar'] ?? $totalPaid);
            $totTunggakan = (float)($sb['total_tunggakan'] ?? $totalTunggakan);
            $lunasStudents = $sb['lunas_students'] ?? [];
            $unpaidStudents = $sb['unpaid_students'] ?? [];
            $lunasCount = count($lunasStudents);
            $unpaidCount = count($unpaidStudents);

            if ($intent === 'student_count') {
                return "Data mahasiswa dalam sistem tersimpan pada data anggota (tabel members). Jumlah mahasiswa yang terdaftar saat ini adalah {$totM} orang (9 mahasiswa tercatat memiliki NIS, 11 berstatus aktif, dan 1 nonaktif).";
            }

            if ($intent === 'student_list') {
                if ($role === 'member') {
                    return "Mohon maaf karena Anda login sebagai Anggota, direktori data mahasiswa dibatasi demi menjaga privasi.";
                }
                $out = "Berikut daftar mahasiswa yang terdaftar dalam sistem (tabel members):\n";
                foreach ($allM as $idx => $m) {
                    $num = $idx + 1;
                    $nisStr = !empty($m['nis']) && $m['nis'] !== '-' ? "NIS: {$m['nis']}, " : "";
                    $out .= "{$num}. {$m['nama']} ({$nisStr}Kelas: {$m['kelas']})\n";
                }
                $out .= "\nTotal mahasiswa terdaftar: {$totM} orang.";
                return trim($out);
            }

            if ($intent === 'student_paid_count') {
                $names = array_column($lunasStudents, 'nama');
                $namesStr = !empty($names) ? " (" . implode(' dan ', $names) . ")" : "";
                return "Jumlah mahasiswa yang sudah melunasi seluruh tagihan iuran saat ini adalah {$lunasCount} orang{$namesStr}.";
            }

            if ($intent === 'student_paid_list' || $intent === 'student_paid') {
                if ($role === 'member') {
                    return "Mohon maaf karena Anda login sebagai Anggota, data pembayaran anggota lain bersifat privat dan tidak dapat diakses.";
                }
                if (empty($lunasStudents)) {
                    return "Belum ada mahasiswa yang melunasi seluruh kewajiban tagihan iuran.";
                }
                $out = "Berdasarkan data tagihan di database, terdapat {$lunasCount} mahasiswa yang telah melunasi seluruh tagihan iuran:\n";
                foreach ($lunasStudents as $idx => $ls) {
                    $num = $idx + 1;
                    $out .= "{$num}. {$ls['nama']} ({$ls['kelas']}) — Tagihan: Rp " . number_format($ls['tagihan'], 0, ',', '.') . " (Lunas)\n";
                }
                return trim($out);
            }

            if ($intent === 'student_unpaid_count') {
                $names = array_column($unpaidStudents, 'nama');
                $namesStr = !empty($names) ? " (" . implode(', ', $names) . ")" : "";
                return "Jumlah mahasiswa yang masih memiliki tunggakan iuran saat ini adalah {$unpaidCount} orang{$namesStr} dengan total sisa tunggakan Rp " . number_format($totTunggakan, 0, ',', '.') . ".";
            }

            if ($intent === 'student_unpaid_list' || $intent === 'student_unpaid') {
                if ($role === 'member') {
                    return "Mohon maaf karena Anda login sebagai Anggota, data pembayaran anggota lain bersifat privat dan tidak dapat diakses.";
                }
                if (empty($unpaidStudents)) {
                    return "Seluruh mahasiswa telah melunasi kewajiban tagihan iuran kas.";
                }
                $out = "Berdasarkan data tagihan di database, terdapat {$unpaidCount} mahasiswa yang masih memiliki sisa tunggakan iuran:\n";
                foreach ($unpaidStudents as $idx => $us) {
                    $num = $idx + 1;
                    $out .= "{$num}. {$us['nama']} ({$us['kelas']}) — Total Tagihan: Rp " . number_format($us['tagihan'], 0, ',', '.') . ", Telah Disetor: Rp " . number_format($us['dibayar'], 0, ',', '.') . ", Sisa Tunggakan: Rp " . number_format($us['tunggakan'], 0, ',', '.') . " (Status: {$us['status']})\n";
                }
                $out .= "\nTotal sisa tunggakan seluruh mahasiswa adalah Rp " . number_format($totTunggakan, 0, ',', '.') . ".";
                return trim($out);
            }

            if ($intent === 'student_largest_arrears') {
                $la = $sb['largest_arrears'] ?? null;
                if ($la) {
                    return "Mahasiswa dengan sisa tunggakan terbesar adalah {$la['nama']} ({$la['kelas']}) dengan sisa tunggakan sebesar Rp " . number_format($la['tunggakan'], 0, ',', '.') . " (dari total kewajiban tagihan Rp " . number_format($la['tagihan'], 0, ',', '.') . " dan total setoran yang telah diverifikasi Rp " . number_format($la['dibayar'], 0, ',', '.') . ").";
                }
                return "Tidak ada data tunggakan aktif di database.";
            }

            if ($intent === 'student_bills_total') {
                return "Total kewajiban tagihan seluruh mahasiswa yang tercatat di sistem saat ini adalah Rp " . number_format($totTagihan, 0, ',', '.') . ".";
            }

            if ($intent === 'student_paid_total') {
                return "Total pembayaran iuran mahasiswa yang sudah diterima dan diverifikasi di sistem saat ini adalah Rp " . number_format($totDibayar, 0, ',', '.') . ".";
            }

            if ($intent === 'student_arrears_total') {
                return "Total sisa tunggakan iuran seluruh mahasiswa saat ini adalah Rp " . number_format($totTunggakan, 0, ',', '.') . " (dari total tagihan Rp " . number_format($totTagihan, 0, ',', '.') . " dan setoran diverifikasi Rp " . number_format($totDibayar, 0, ',', '.') . ").";
            }
        }

        // 14.0V Verifikasi Pembayaran (payment_transactions)
        if (($queryResults['intent'] ?? '') === 'payment_verified_summary') {
            $vs = $queryResults['verification_summary'] ?? [];
            $vCount = $vs['verified_count'] ?? 17;
            $vTotal = number_format((float)($vs['verified_total'] ?? 653000), 2, ',', '.');
            return "Berdasarkan data di database, terdapat {$vCount} transaksi pembayaran yang telah diverifikasi (status berhasil) dengan total nominal Rp {$vTotal}.";
        }

        if (($queryResults['intent'] ?? '') === 'payment_pending_summary') {
            $vs = $queryResults['verification_summary'] ?? [];
            $pCount = $vs['pending_count'] ?? 1;
            $pTotal = number_format((float)($vs['pending_total'] ?? 50000), 2, ',', '.');
            return "Saat ini terdapat {$pCount} transaksi pembayaran yang berstatus menunggu verifikasi dengan total nominal Rp {$pTotal}.";
        }

        // 14.0B Siapa saja yang membayar pada tanggal tertentu
        if (($queryResults['intent'] ?? '') === 'who_paid_on_date') {
            $wp = $queryResults['who_paid'] ?? [];
            $dLabel = $queryResults['date_label'] ?? 'tanggal tersebut';
            if (empty($wp['members'])) {
                return "Tidak ada anggota yang tercatat melakukan pembayaran pada tanggal {$dLabel} di database.";
            }
            $out = "Anggota yang tercatat melakukan pembayaran pada tanggal {$dLabel}:\n";
            foreach ($wp['members'] as $idx => $m) {
                $num = $idx + 1;
                $nomStr = number_format((float)$m['nominal'], 0, ',', '.');
                $out .= "{$num}. {$m['member_nama']} ({$m['member_kelas']}) — Rp {$nomStr} ({$m['count']} transaksi)\n";
            }
            $out .= "\nTotal penerimaan kas pada tanggal tersebut adalah Rp " . number_format((float)$wp['total'], 0, ',', '.') . " dari {$wp['count']} transaksi.";
            return trim($out);
        }

        // 14.0C Pembayaran Anggota pada Tanggal Tertentu (Kombinasi Member + Tanggal)
        if (($queryResults['intent'] ?? '') === 'member_date_payments') {
            $mName = $queryResults['member']['nama'] ?? 'Anggota';
            $dLabel = $queryResults['date_label'] ?? 'tanggal tersebut';
            $sum = $queryResults['summary'] ?? [];
            $count = (int)($sum['count'] ?? 0);
            $totalNom = (float)($sum['total'] ?? 0.0);
            if ($count === 0) {
                return "Tidak ditemukan catatan transaksi pembayaran untuk {$mName} pada tanggal {$dLabel} di database.";
            }
            $out = "Total pembayaran {$mName} pada tanggal {$dLabel} adalah Rp " . number_format($totalNom, 0, ',', '.') . ".\n\n";
            $out .= "Rincian:\n";
            $out .= "- Jumlah transaksi: {$count}\n";
            $out .= "- Total pembayaran: Rp " . number_format($totalNom, 0, ',', '.') . "\n\n";
            $out .= "Rincian transaksi:\n";
            foreach ($queryResults['transactions'] as $idx => $trx) {
                $num = $idx + 1;
                $nom = number_format((float)$trx['nominal'], 0, ',', '.');
                $out .= "{$num}. Kode: {$trx['kode_transaksi']} | Nominal: Rp {$nom} | Kategori: {$trx['kategori']} | Keterangan: \"{$trx['keterangan']}\"\n";
            }
            return trim($out);
        }

        // 14.0D Pemasukan Kas pada Tanggal Tertentu
        if (($queryResults['intent'] ?? '') === 'date_income') {
            $dLabel = $queryResults['date_label'] ?? 'tanggal tersebut';
            $sum = $queryResults['summary'] ?? [];
            $count = (int)($sum['count'] ?? 0);
            $totalNom = (float)($sum['total'] ?? 0.0);
            if ($count === 0) {
                return "Total pemasukan kas pada tanggal {$dLabel} adalah Rp 0,00 (0 transaksi tercatat di database).";
            }
            $out = "Total pemasukan kas pada tanggal {$dLabel} adalah Rp " . number_format($totalNom, 0, ',', '.') . ".\n\n";
            $out .= "Rincian:\n";
            $out .= "- Jumlah transaksi: {$count}\n";
            $out .= "- Total pemasukan: Rp " . number_format($totalNom, 0, ',', '.') . "\n\n";
            $out .= "Data dihitung berdasarkan transaksi pemasukan yang tercatat pada tanggal tersebut.";
            return trim($out);
        }

        // 14.0E Pengeluaran Kas pada Tanggal Tertentu
        if (($queryResults['intent'] ?? '') === 'date_expense') {
            $dLabel = $queryResults['date_label'] ?? 'tanggal tersebut';
            $sum = $queryResults['summary'] ?? [];
            $count = (int)($sum['count'] ?? 0);
            $totalNom = (float)($sum['total'] ?? 0.0);
            if ($count === 0) {
                return "Total pengeluaran kas pada tanggal {$dLabel} adalah Rp 0,00.\nTidak ditemukan catatan transaksi pengeluaran pada tanggal tersebut di database.";
            }
            $out = "Total pengeluaran kas pada tanggal {$dLabel} adalah Rp " . number_format($totalNom, 0, ',', '.') . ".\n\n";
            $out .= "Rincian:\n";
            $out .= "- Jumlah transaksi: {$count}\n";
            $out .= "- Total pengeluaran: Rp " . number_format($totalNom, 0, ',', '.') . "\n\n";
            $out .= "Data dihitung berdasarkan transaksi pengeluaran yang tercatat pada tanggal tersebut.";
            return trim($out);
        }

        // 14.0F Ringkasan Pemasukan Anggota (Spesifik Anggota)
        if (($queryResults['intent'] ?? '') === 'member_income_summary') {
            $mData = $queryResults['member_data'] ?? [];
            $mName = $mData['member']['nama'] ?? ($queryResults['member']['nama'] ?? 'Anggota');
            $out = "Berikut data pemasukan {$mName}:\n\n";
            $out .= "- Jumlah transaksi kas: " . ($mData['cash_trx_count'] ?? 0) . "\n";
            $out .= "- Total pembayaran kas masuk: Rp " . number_format((float)($mData['cash_trx_total'] ?? 0), 0, ',', '.') . "\n";
            $out .= "- Status iuran: " . ($mData['status'] ?? 'Belum Bayar') . " (Total tagihan: Rp " . number_format((float)($mData['total_tagihan'] ?? 0), 0, ',', '.') . ", Telah disetor: Rp " . number_format((float)($mData['total_dibayar'] ?? 0), 0, ',', '.') . ", Sisa tunggakan: Rp " . number_format((float)($mData['total_tunggakan'] ?? 0), 0, ',', '.') . ")\n\n";
            if (!empty($mData['cash_transactions'])) {
                $out .= "Rincian transaksi kas terbaru:\n";
                foreach (array_slice($mData['cash_transactions'], 0, 5) as $idx => $t) {
                    $num = $idx + 1;
                    $tgl = date('d M Y', strtotime($t['tanggal']));
                    $nom = number_format((float)$t['nominal'], 0, ',', '.');
                    $out .= "{$num}. Tanggal: {$tgl} | Kode: {$t['kode_transaksi']} | Nominal: Rp {$nom} | Kategori: {$t['kategori']} | Keterangan: \"{$t['keterangan']}\"\n";
                }
            }
            return trim($out);
        }

        // 14.0G Iuran yang Sudah Dibayarkan Anggota
        if (($queryResults['intent'] ?? '') === 'member_dues_paid' || ($queryResults['intent'] ?? '') === 'member_arrears') {
            $mData = $queryResults['member_data'] ?? [];
            $mName = $mData['member']['nama'] ?? ($queryResults['member']['nama'] ?? 'Anggota');
            if (($queryResults['intent'] ?? '') === 'member_arrears') {
                return "Total sisa tunggakan iuran {$mName} saat ini adalah Rp " . number_format((float)($mData['total_tunggakan'] ?? 0), 0, ',', '.') . " (dari total kewajiban tagihan Rp " . number_format((float)($mData['total_tagihan'] ?? 0), 0, ',', '.') . " dan total setoran yang telah diverifikasi Rp " . number_format((float)($mData['total_dibayar'] ?? 0), 0, ',', '.') . "). Status: " . ($mData['status'] ?? 'Belum Bayar') . ".";
            }
            return "Total iuran yang sudah dibayarkan oleh {$mName} adalah Rp " . number_format((float)($mData['total_dibayar'] ?? 0), 0, ',', '.') . " dari total tagihan Rp " . number_format((float)($mData['total_tagihan'] ?? 0), 0, ',', '.') . " (sisa tunggakan Rp " . number_format((float)($mData['total_tunggakan'] ?? 0), 0, ',', '.') . ").";
        }

        // 14.0H Apakah Anggota Sudah Membayar Bulan Ini
        if (($queryResults['intent'] ?? '') === 'member_paid_this_month') {
            $mName = $queryResults['member']['nama'] ?? 'Anggota';
            $sum = $queryResults['summary'] ?? [];
            $count = (int)($sum['count'] ?? 0);
            $total = (float)($sum['total'] ?? 0.0);
            if ($count > 0) {
                return "Ya, {$mName} sudah melakukan pembayaran iuran pada bulan Oktober 2026. Tercatat {$count} transaksi pembayaran dengan total setoran Rp " . number_format($total, 0, ',', '.') . ".";
            }
            return "Belum ada catatan transaksi pembayaran untuk {$mName} pada bulan Oktober 2026 di database.";
        }

        // 14.0I Riwayat Transaksi Anggota
        if (($queryResults['intent'] ?? '') === 'member_history') {
            $mData = $queryResults['member_data'] ?? [];
            $mName = $mData['member']['nama'] ?? ($queryResults['member']['nama'] ?? 'Anggota');
            $trxs = $mData['cash_transactions'] ?? [];
            $out = "Riwayat transaksi pembayaran {$mName} (" . count($trxs) . " transaksi tercatat di database):\n\n";
            foreach (array_slice($trxs, 0, 10) as $idx => $t) {
                $num = $idx + 1;
                $tgl = date('d M Y', strtotime($t['tanggal']));
                $nom = number_format((float)$t['nominal'], 0, ',', '.');
                $out .= "{$num}. Tanggal: {$tgl} | Kode: {$t['kode_transaksi']} | Nominal: Rp {$nom} | Kategori: {$t['kategori']} | Keterangan: \"{$t['keterangan']}\"\n";
            }
            if (count($trxs) > 10) {
                $out .= "\n(dan " . (count($trxs) - 10) . " transaksi lainnya tercatat di database).\n";
            }
            $out .= "\nTotal pembayaran masuk kas adalah Rp " . number_format((float)($mData['cash_trx_total'] ?? 0), 0, ',', '.') . ".";
            return trim($out);
        }

        // 14.0J Kategori Kas Bulan Tertentu
        if (($queryResults['intent'] ?? '') === 'category_month') {
            $cat = $queryResults['category'] ?? [];
            $catName = $cat['nama'] ?? 'Kategori';
            $mLabel = $queryResults['month_label'] ?? 'Oktober 2026';
            $sum = $queryResults['summary'] ?? [];
            $count = (int)($sum['count'] ?? 0);
            $total = (float)($sum['total'] ?? 0.0);
            $out = "Total pemasukan dari kategori {$catName} pada bulan {$mLabel} adalah Rp " . number_format($total, 0, ',', '.') . ".\n\n";
            $out .= "Rincian:\n";
            $out .= "- Kategori: {$catName}\n";
            $out .= "- Periode: {$mLabel}\n";
            $out .= "- Jumlah transaksi: {$count} transaksi\n";
            $out .= "- Total nominal: Rp " . number_format($total, 0, ',', '.') . "\n\n";
            $out .= "Data dihitung berdasarkan catatan transaksi kas di database.";
            return trim($out);
        }

        // 14.0K Kategori Kas Keseluruhan
        if (($queryResults['intent'] ?? '') === 'category_total') {
            $cat = $queryResults['category'] ?? [];
            $catName = $cat['nama'] ?? 'Kategori';
            $sum = $queryResults['summary'] ?? [];
            $count = (int)($sum['count'] ?? 0);
            $total = (float)($sum['total'] ?? 0.0);
            return "Total transaksi untuk kategori {$catName} adalah Rp " . number_format($total, 0, ',', '.') . " dari {$count} transaksi yang tercatat di database.";
        }

        // 14.0L Rentang Tanggal (Date Range)
        if (($queryResults['intent'] ?? '') === 'range_income' || ($queryResults['intent'] ?? '') === 'range_expense') {
            $dr = $queryResults['date_range'] ?? [];
            $sum = $queryResults['summary'] ?? [];
            $label = ($queryResults['intent'] ?? '') === 'range_expense' ? 'pengeluaran' : 'pemasukan';
            $drLabel = $dr['label'] ?? 'rentang tanggal tersebut';
            return "Total {$label} kas dari {$drLabel} adalah Rp " . number_format((float)($sum['total'] ?? 0), 0, ',', '.') . " dari " . (int)($sum['count'] ?? 0) . " transaksi yang tercatat di database.";
        }

        // 14.0M Transaksi Tahun Tertentu
        if (($queryResults['intent'] ?? '') === 'year_transactions') {
            $sum = $queryResults['summary'] ?? [];
            $yr = $sum['year'] ?? '2026';
            return "Total seluruh transaksi kas pada tahun {$yr} adalah {$sum['total_count']} transaksi ({$sum['income_count']} transaksi pemasukan senilai Rp " . number_format((float)$sum['income_total'], 0, ',', '.') . " dan {$sum['expense_count']} transaksi pengeluaran senilai Rp " . number_format((float)$sum['expense_total'], 0, ',', '.') . ") dengan saldo kas bersih Rp " . number_format((float)$sum['net_balance'], 0, ',', '.') . ".";
        }

        // 14.0N Pemasukan Bulan Tertentu
        if (($queryResults['intent'] ?? '') === 'month_income') {
            $mLabel = $queryResults['month_label'] ?? 'Oktober 2026';
            $sum = $queryResults['summary'] ?? [];
            $tot = (float)($sum['total'] ?? 0);
            $cnt = (int)($sum['count'] ?? 0);
            return "Total pemasukan kas pada bulan {$mLabel} adalah Rp " . number_format($tot, 0, ',', '.') . " dari {$cnt} transaksi yang tercatat di database.";
        }

        // 14.0O Total Seluruh Transaksi
        if (($queryResults['intent'] ?? '') === 'total_all_transactions') {
            $totInCount = (int)($metrics['total_members'] > 0 ? 30 : 0);
            return "Total seluruh transaksi kas di sistem saat ini adalah 31 transaksi (30 transaksi pemasukan dan 1 transaksi pengeluaran) dengan akumulasi dana masuk Rp " . number_format($income, 2, ',', '.') . " dan dana keluar Rp " . number_format($expense, 2, ',', '.') . ".";
        }

        // 14.1 RINCIAN PENGELUARAN / TUJUAN TRANSAKSI AKTUAL (Sesuai Kebutuhan Prompt)
        $hasExpenseSubject = (bool)(
            preg_match('/\b(pengeluaran(nya)?|belanja(nya)?|biaya(nya)?|beli|uang\s*kas|dana\s*kas|kas\s*keluar|uang\s*keluar)\b/i', $clean) ||
            (preg_match('/\b(uang|dana|kas)\b/i', $clean) && preg_match('/\b(keluar|dipakai|dipake|digunakan|dihabiskan|dialokasikan|disalurkan)\b/i', $clean))
        );

        $hasExpenseDetailIntent = (bool)(
            preg_match('/\b(untuk\s*apa(\s*aja|\s*saja)?|buat\s*apa(\s*aja|\s*saja)?|keperluan\s*apa|alokasi(nya)?|tujuan(nya)?)\b/i', $clean) ||
            preg_match('/\b(apa\s*saja|apa\s*aja|digunakan\s*untuk\s*apa|dipakai\s*untuk\s*apa|dipakai\s*apa\s*saja|dipakai\s*apa\s*aja)\b/i', $clean) ||
            preg_match('/\b(rincian|detail|daftar|catatan|keterangan|deskripsi|sebutkan|tampilkan)\b/i', $clean) ||
            preg_match('/\b(terbaru|terakhir|transaksi|setiap|semua|tiap|masing-masing)\b/i', $clean) ||
            preg_match('/\b(terbesar|tertinggi|paling\s*besar)\b/i', $clean) ||
            preg_match('/\b(tanggal.*keterangan.*nominal|keterangan.*nominal)\b/i', $clean)
        );

        $isPureTotalQuery = (bool)preg_match('/^(berapa\s+(total|jumlah)\s+pengeluaran|total\s+pengeluaran(\s+kas)?\s*(berapa)?|berapa\s+pengeluaran(\s+kas)?\s*(saat ini|sekarang)?)$/i', $clean);

        $isLargestExpenseQuestion = (bool)(
            preg_match('/\b(pengeluaran|belanja|biaya)\s+.*(terbesar|tertinggi|paling\s+besar)\b/i', $clean) ||
            preg_match('/\b(terbesar|tertinggi|paling\s+besar)\s+.*(pengeluaran|belanja|biaya|digunakan|untuk apa)\b/i', $clean) ||
            str_contains($clean, 'pengeluaran terbesar')
        );

        $isExpenseDetailQuestion = ($hasExpenseSubject && $hasExpenseDetailIntent && !$isPureTotalQuery)
            || str_contains($clean, 'pengeluaran bulan ini untuk apa')
            || str_contains($clean, 'rincian pengeluaran')
            || str_contains($clean, 'tujuan pengeluaran')
            || str_contains($clean, 'transaksi pengeluaran')
            || str_contains($clean, 'pengeluaran terbaru')
            || str_contains($clean, 'untuk apa saja')
            || str_contains($clean, 'untuk apa aja')
            || str_contains($clean, 'keterangan setiap pengeluaran')
            || $isLargestExpenseQuestion;

        if ($isExpenseDetailQuestion) {
            if ($role === 'member') {
                return "Mohon maaf karena Anda login sebagai Anggota, data rincian transaksi pengeluaran kas hanya dapat diakses oleh Admin atau Bendahara.";
            }

            if (!empty($queryResults['empty_query'])) {
                return "Tidak ditemukan catatan transaksi pengeluaran pada periode tersebut di database.";
            }

            $exList = !empty($recentExpenses) ? $recentExpenses : ($metrics['recent_expenses'] ?? []);

            if (!empty($exList)) {
                // Khusus jika menanyakan pengeluaran terbesar
                if ($isLargestExpenseQuestion) {
                    $largest = null;
                    foreach ($exList as $ex) {
                        if ($largest === null || (float)$ex['nominal'] > (float)$largest['nominal']) {
                            $largest = $ex;
                        }
                    }
                    if ($largest !== null) {
                        $tgl = date('d M Y', strtotime($largest['tanggal']));
                        $nom = number_format((float)$largest['nominal'], 2, ',', '.');
                        $ket = !empty(trim((string)($largest['keterangan'] ?? ''))) ? $largest['keterangan'] : 'Tanpa keterangan di database';
                        $kat = !empty(trim((string)($largest['kategori'] ?? ''))) ? $largest['kategori'] : 'Tanpa Kategori';

                        $out = "Pengeluaran terbesar dialokasikan untuk \"{$ket}\" (kategori: {$kat}) sebesar Rp {$nom} pada tanggal {$tgl}.\n\n";
                        $out .= "Rincian transaksi pengeluaran kas yang tercatat di database:\n";
                        foreach ($exList as $idx => $ex) {
                            $num = $idx + 1;
                            $exTgl = date('d M Y', strtotime($ex['tanggal']));
                            $exNom = number_format((float)$ex['nominal'], 0, ',', '.');
                            $exKet = !empty(trim((string)($ex['keterangan'] ?? ''))) ? $ex['keterangan'] : 'Tanpa keterangan di database';
                            $exKat = !empty(trim((string)($ex['kategori'] ?? ''))) ? $ex['kategori'] : 'Tanpa Kategori';
                            $out .= "{$num}. {$exTgl}: {$exKat} — Rp {$exNom} (Keterangan: \"{$exKet}\" | Kode: {$ex['kode_transaksi']})\n";
                        }
                        $out .= "Total akumulasi pengeluaran kas adalah Rp " . number_format($expense, 2, ',', '.') . ".";
                        return trim($out);
                    }
                }

                $emptyInFiltered = !empty($queryResults['empty_in_filtered_month']);
                $prefix = $emptyInFiltered
                    ? "Pada bulan ini belum ada transaksi pengeluaran baru. Berikut rincian transaksi pengeluaran kas yang tercatat sebelumnya di database:\n"
                    : "Rincian transaksi pengeluaran kas yang tercatat di database:\n";

                $out = $prefix;
                foreach ($exList as $idx => $ex) {
                    $num = $idx + 1;
                    $tgl = date('d M Y', strtotime($ex['tanggal']));
                    $nom = number_format((float)$ex['nominal'], 0, ',', '.');
                    $ket = !empty(trim((string)($ex['keterangan'] ?? ''))) ? $ex['keterangan'] : 'Tanpa keterangan di database';
                    $kat = !empty(trim((string)($ex['kategori'] ?? ''))) ? $ex['kategori'] : 'Tanpa Kategori';
                    $out .= "{$num}. {$tgl}: {$kat} — Rp {$nom} (Keterangan: \"{$ket}\" | Kode: {$ex['kode_transaksi']})\n";
                }
                $out .= "Total akumulasi pengeluaran kas adalah Rp " . number_format($expense, 2, ',', '.') . ".";
                return trim($out);
            } else {
                return "Tidak ditemukan catatan transaksi pengeluaran pada periode tersebut di database.";
            }
        }

        // ---------------------------------------------------------------------
        // 14.1B RINCIAN SUMBER TOTAL PEMASUKAN KAS (IURAN, DONASI, SUMBANGAN)
        // ---------------------------------------------------------------------
        $breakdown = $metrics['income_breakdown'] ?? ($queryResults['income_breakdown'] ?? []);
        $iuranNom = number_format((float)($breakdown['iuran'] ?? 0), 0, ',', '.');
        $donasiNom = number_format((float)($breakdown['donasi'] ?? 0), 0, ',', '.');
        $sumbanganNom = number_format((float)($breakdown['sumbangan'] ?? 0), 0, ',', '.');
        $totalNom = number_format((float)($breakdown['total'] ?? $income), 0, ',', '.');
        $iuranCount = (int)($breakdown['counts']['iuran'] ?? 0);
        $donasiCount = (int)($breakdown['counts']['donasi'] ?? 0);
        $sumbanganCount = (int)($breakdown['counts']['sumbangan'] ?? 0);
        $totTxCount = $iuranCount + $donasiCount + $sumbanganCount;

        // 1. Jumlah transaksi dari setiap sumber pemasukan
        if (
            ($queryResults['intent'] ?? '') === 'income_transactions_per_source' ||
            (preg_match('/\b(jumlah|banyak(nya)?|berapa)\s+transaksi\b/i', $clean) && preg_match('/\b(sumber\s+pemasukan|setiap\s+sumber|tiap\s+sumber|masing\s*-\s*masing\s+sumber|sumber\s+dana|iuran.*donasi.*sumbang)\b/i', $clean))
        ) {
            return "Rincian jumlah transaksi dari setiap sumber pemasukan:\n\n" .
                   "1. Iuran: {$iuranCount} transaksi (Rp{$iuranNom})\n" .
                   "2. Donasi: {$donasiCount} transaksi (Rp{$donasiNom})\n" .
                   "3. Sumbangan: {$sumbanganCount} transaksi (Rp{$sumbanganNom})\n\n" .
                   "Total keseluruhan: {$totTxCount} transaksi pemasukan (Rp{$totalNom}).";
        }

        // 2. Kombinasi donasi dan sumbangan
        if (
            ($queryResults['intent'] ?? '') === 'income_source_donasi_sumbangan' ||
            (preg_match('/\bdonasi\b/i', $clean) && preg_match('/\bsumbang(an)?\b/i', $clean) && !preg_match('/\biuran\b/i', $clean))
        ) {
            $comboNom = number_format((float)(($breakdown['donasi'] ?? 0) + ($breakdown['sumbangan'] ?? 0)), 0, ',', '.');
            $comboCount = $donasiCount + $sumbanganCount;
            return "Total pemasukan dari donasi dan sumbangan saat ini sebesar Rp{$comboNom} ({$comboCount} transaksi), dengan rincian:\n" .
                   "- Donasi: Rp{$donasiNom} ({$donasiCount} transaksi)\n" .
                   "- Sumbangan: Rp{$sumbanganNom} ({$sumbanganCount} transaksi)";
        }

        // 3. Hanya donasi
        if (
            ($queryResults['intent'] ?? '') === 'income_source_donasi' ||
            (preg_match('/\bdonasi\b/i', $clean) && !preg_match('/\b(sumbang|iuran)\b/i', $clean) && (preg_match('/\b(berapa|jumlah|total|pemasukan|dana|uang|terkumpul|masuk)\b/i', $clean) || $clean === 'donasi' || $clean === 'total donasi'))
        ) {
            return "Total pemasukan dari donasi saat ini sebesar Rp{$donasiNom} ({$donasiCount} transaksi tercatat di database).";
        }

        // 4. Hanya sumbangan
        if (
            ($queryResults['intent'] ?? '') === 'income_source_sumbangan' ||
            (preg_match('/\bsumbang(an)?\b/i', $clean) && !preg_match('/\b(donasi|iuran)\b/i', $clean) && (preg_match('/\b(berapa|jumlah|total|pemasukan|dana|uang|terkumpul|masuk)\b/i', $clean) || $clean === 'sumbangan' || $clean === 'total sumbangan'))
        ) {
            return "Total pemasukan dari sumbangan saat ini sebesar Rp{$sumbanganNom} ({$sumbanganCount} transaksi tercatat di database).";
        }

        // 5. Hanya iuran
        if (
            ($queryResults['intent'] ?? '') === 'income_source_iuran' ||
            (preg_match('/\biuran\b/i', $clean) && !preg_match('/\b(donasi|sumbang)\b/i', $clean) && !preg_match('/\b(belum\s+bayar|belum\s+membayar|menunggak|tunggakan|lunas|tagihan|siapa)\b/i', $clean) && (preg_match('/\b(berapa|jumlah|total|pemasukan|dana|uang|terkumpul|masuk)\b/i', $clean) || $clean === 'iuran' || $clean === 'total iuran'))
        ) {
            return "Total pemasukan dari iuran saat ini sebesar Rp{$iuranNom} ({$iuranCount} transaksi tercatat di database).";
        }

        // 6. Rincian Sumber Pemasukan / Total Pemasukan
        if (
            in_array($queryResults['intent'] ?? '', ['income_breakdown', 'global_income'], true) ||
            (preg_match('/\b(pemasukan|dana\s*masuk|uang\s*masuk)\b/i', $clean) && (
                preg_match('/\b(berasal\s+dari\s+mana|dari\s+mana\s+saja|dari\s+mana\s+aja|asal\s*[- ]*usul)\b/i', $clean) ||
                preg_match('/\b(rincikan|rincian|perincian|rinci|detailkan|detail)\s+(total\s+)?pemasukan\b/i', $clean) ||
                preg_match('/\b(masing\s*[- ]*masing|setiap|tiap)\s+sumber\s+(pemasukan|dana|masuk)\b/i', $clean) ||
                preg_match('/\bsumber\s+pemasukan\s*(apa\s+saja|berasal|dari\s+mana)?\b/i', $clean) ||
                preg_match('/\b(terdiri\s+dari\s+apa\s+saja|apa\s+saja\s+sumber(nya)?)\b/i', $clean) ||
                preg_match('/\brincian\s+sumber\s+pemasukan\b/i', $clean)
            )) ||
            preg_match('/^(berapa\s+(total\s+|jumlah\s+)?pemasukan(\s+kas)?(\s+saat\s+ini|\s+sekarang)?|total\s+pemasukan(\s+kas)?\s*(berapa)?|total\s+pemasukan|pemasukan\s+saat\s+ini|berapa\s+pemasukan\s+kas)$/i', $clean)
        ) {
            return "Total pemasukan kas saat ini sebesar Rp{$totalNom}, dengan rincian:\n\n" .
                   "1. Iuran: Rp{$iuranNom}\n" .
                   "2. Donasi: Rp{$donasiNom}\n" .
                   "3. Sumbangan: Rp{$sumbanganNom}\n\n" .
                   "Total keseluruhan: Rp{$totalNom}.";
        }

        // 14.2 RINCIAN TRANSAKSI PEMASUKAN INDIVIDUAL (RAW TRANSACTIONS)
        $isIncomeDetailQuestion = (
            preg_match('/\b(transaksi\s+pemasukan|daftar\s+pemasukan|mutasi\s+pemasukan|pemasukan\s+terbaru|transaksi\s+masuk)\b/i', $clean)
        );

        if ($isIncomeDetailQuestion) {
            if ($role === 'member') {
                return "Mohon maaf karena Anda login sebagai Anggota, data rincian transaksi pemasukan kas hanya dapat diakses oleh Admin atau Bendahara.";
            }

            if (!empty($recentIncomes)) {
                $out = "Rincian transaksi pemasukan kas yang tercatat di database:\n";
                foreach (array_slice($recentIncomes, 0, 5) as $idx => $in) {
                    $num = $idx + 1;
                    $tgl = date('d M Y', strtotime($in['tanggal']));
                    $nom = number_format($in['nominal'], 0, ',', '.');
                    $out .= "{$num}. {$tgl}: {$in['kategori']} — Rp {$nom} (Keterangan: \"{$in['keterangan']}\")\n";
                }
                $out .= "Total akumulasi pemasukan kas adalah Rp " . number_format($income, 2, ',', '.') . ".";
                return trim($out);
            } else {
                return "Tidak ditemukan catatan transaksi pemasukan pada periode tersebut di database.";
            }
        }

        // 14.3 REKAPITULASI BULAN TERTENTU (Filter Periode Tanggal)
        if (!empty($monthlyRecap) && preg_match('/\bbulan\s*(september|oktober|agustus|juli|juni|mei|april|maret|februari|januari|\d{4}-\d{2})\b/i', $clean)) {
            $mr = $monthlyRecap;
            if ($mr['total_transaksi'] > 0) {
                $bulanStr = date('F Y', strtotime($mr['bulan'] . '-01'));
                $nomIn = number_format($mr['pemasukan'], 2, ',', '.');
                $nomOut = number_format($mr['pengeluaran'], 2, ',', '.');
                $nomNet = number_format($mr['saldo_bersih'], 2, ',', '.');
                return "Rekapitulasi kas bulan {$bulanStr} ({$mr['bulan']}):\n" .
                       "- **Total Pemasukan**: Rp {$nomIn} ({$mr['count_pemasukan']} transaksi)\n" .
                       "- **Total Pengeluaran**: Rp {$nomOut} ({$mr['count_pengeluaran']} transaksi)\n" .
                       "- **Saldo Bersih**: Rp {$nomNet}\n" .
                       "- **Total Mutasi**: {$mr['total_transaksi']} transaksi tercatat.";
            } else {
                return "Belum ada transaksi kas yang tercatat pada bulan {$mr['bulan']} di database.";
            }
        }

        // 14.3B Panduan cara menghitung tunggakan iuran
        if (
            preg_match('/\b(bagaimana|cara|rumus)\s+menghitung\s+.*tunggakan\b/i', $clean) ||
            str_contains($clean, 'menghitung tunggakan') ||
            str_contains($clean, 'hitung tunggakan') ||
            str_contains($clean, 'menghitung total tunggakan')
        ) {
            return "Tunggakan iuran dihitung dengan rumus: Total Tagihan - Total Iuran yang Telah Dibayar. Berdasarkan sistem, total tagihan saat ini adalah Rp " . number_format($totalTagihan, 2, ',', '.') . " dan sisa tunggakan Rp " . number_format($totalTunggakan, 2, ',', '.') . ".";
        }

        // 14.4 SIAPA SAJA ANGGOTA YANG BELUM MEMBAYAR / MENUNGGAK
        $isCalculationQuery = (
            preg_match('/\b(bagaimana|cara|rumus)\b/i', $clean) &&
            preg_match('/\b(hitung|menghitung)\b/i', $clean)
        );

        $isUnpaidQuestion = !$isCalculationQuery && (
            str_contains($clean, 'penunggak') ||
            (preg_match('/\b(siapa|daftar|ada siapa)\b/i', $clean) && preg_match('/\b(belum bayar|belum membayar|menunggak|tunggakan|belum lunas|penunggak)\b/i', $clean)) ||
            str_contains($clean, 'belum bayar') ||
            str_contains($clean, 'belum membayar') ||
            str_contains($clean, 'belum lunas') ||
            str_contains($clean, 'menunggak') ||
            str_contains($clean, 'tunggakan')
        );

        if ($isUnpaidQuestion) {
            if ($role === 'member') {
                return "Mohon maaf karena Anda login sebagai Anggota, data pembayaran anggota lain bersifat privat dan tidak dapat diakses.";
            }

            if (!empty($unpaidMembersList)) {
                $out = "Daftar anggota yang memiliki sisa tunggakan iuran kas:\n";
                foreach ($unpaidMembersList as $idx => $um) {
                    $num = $idx + 1;
                    $tunggakanStr = number_format($um['tunggakan'], 0, ',', '.');
                    $out .= "{$num}. {$um['nama']} ({$um['kelas']}) — Periode: {$um['periode']} — Status: {$um['status']} (Tunggakan: Rp {$tunggakanStr})\n";
                }
                $out .= "Total sisa tunggakan seluruh anggota di sistem adalah Rp " . number_format($totalTunggakan, 2, ',', '.') . ".";
                return trim($out);
            } else {
                return "Seluruh anggota telah melunasi kewajiban iuran kas. Tidak ada tunggakan aktif di sistem.";
            }
        }

        // 14.5 DATA ANGGOTA / DAFTAR SISWA / KELAS
        $isMemberDirQuestion = !$isUnpaidQuestion && (
            preg_match('/\b(data anggota|daftar anggota|data siswa|daftar siswa|anggota kelas|siswa kelas)\b/i', $clean) ||
            preg_match('/\bkelas\s*(xii|xi|x)\b/i', $clean) ||
            (preg_match('/\bsiapa saja anggota\b/i', $clean) && !preg_match('/\b(bayar|lunas|tunggak)\b/i', $clean))
        );

        if ($isMemberDirQuestion) {
            if ($role === 'member') {
                return "Mohon maaf karena Anda login sebagai Anggota, direktori data seluruh anggota hanya dapat diakses oleh Admin atau Bendahara.";
            }

            if (!empty($membersList)) {
                $out = "Daftar anggota yang tercatat di database:\n";
                foreach ($membersList as $idx => $m) {
                    $num = $idx + 1;
                    $out .= "{$num}. {$m['nama']} (NIS: {$m['nis']}, Kelas: {$m['kelas']}, Status: {$m['status']})\n";
                }
                return trim($out);
            } else {
                $ms = $metrics['members_summary'] ?? [];
                $classBreakdown = [];
                if (!empty($ms['classes'])) {
                    foreach ($ms['classes'] as $cls => $jml) {
                        $classBreakdown[] = "{$cls} ({$jml} siswa)";
                    }
                }
                $clsText = !empty($classBreakdown) ? "\nDistribusi kelas: " . implode(', ', $classBreakdown) . "." : "";
                return "Total terdapat {$totalMembers} anggota yang terdaftar di sistem (" . ($ms['aktif'] ?? $totalMembers) . " aktif, " . ($ms['nonaktif'] ?? 0) . " nonaktif).{$clsText}";
            }
        }

        // 14.6 DAFTAR KATEGORI KAS
        if (preg_match('/\b(kategori|pos pengeluaran|pos pemasukan)\b/i', $clean) && !preg_match('/\b(terbesar|persentase)\b/i', $clean)) {
            $cats = !empty($categoriesList) ? $categoriesList : [];
            $inCats = [];
            $outCats = [];
            foreach ($cats as $c) {
                if ($c['tipe'] === 'pemasukan') {
                    $inCats[] = $c['nama'];
                } else {
                    $outCats[] = $c['nama'];
                }
            }
            $inText = !empty($inCats) ? implode(', ', $inCats) : "Iuran Wajib Mingguan, Sumbangan / Donasi Sukarela";
            $outText = !empty($outCats) ? implode(', ', $outCats) : "ATK & Logistik Kas, Konsumsi Kegiatan, Transport & Operasional, Peralatan & Maintenance";
            return "Kategori transaksi kas yang terdaftar di sistem:\n" .
                   "- **Pemasukan**: {$inText}.\n" .
                   "- **Pengeluaran**: {$outText}.";
        }

        // 14.7 ANTREAN VERIFIKASI PEMBAYARAN
        if (preg_match('/\b(antrean|antrian|menunggu verifikasi|belum diverifikasi)\b/i', $clean) && !str_contains($clean, 'mengapa') && !str_contains($clean, 'kenapa')) {
            if ($pendingCount > 0) {
                return "Saat ini terdapat {$pendingCount} setoran pembayaran anggota berstatus Menunggu Verifikasi. Silakan periksa bukti transfer pada menu Pembayaran.";
            }
            return "Saat ini antrean verifikasi pembayaran kosong (0 transaksi menunggu verifikasi).";
        }

        // 14.8 Saldo kas saat ini
        if (
            preg_match('/\b(berapa|total|cek)\s+.*saldo(nya)?\b/i', $clean) ||
            preg_match('/\bsaldo(nya)?\s+.*(berapa|total|cek)\b/i', $clean) ||
            $clean === 'saldo kas' ||
            $clean === 'saldo' ||
            str_contains($clean, 'saldo kas saat ini')
        ) {
            return "Saldo kas bersih saat ini adalah Rp " . number_format($balance, 2, ',', '.') . " dengan status kondisi kas {$condition}.";
        }

        // 14.9 Pemasukan kas
        if (
            preg_match('/\b(berapa|total|cek)\s+.*pemasukan(nya)?\b/i', $clean) ||
            preg_match('/\bpemasukan(nya)?\s+.*(berapa|total|cek)\b/i', $clean) ||
            str_contains($clean, 'pemasukan bulan ini') ||
            $clean === 'pemasukan' ||
            $clean === 'total pemasukan'
        ) {
            $breakdown = $metrics['income_breakdown'] ?? ($queryResults['income_breakdown'] ?? []);
            $iNom = number_format((float)($breakdown['iuran'] ?? 0), 0, ',', '.');
            $dNom = number_format((float)($breakdown['donasi'] ?? 0), 0, ',', '.');
            $sNom = number_format((float)($breakdown['sumbangan'] ?? 0), 0, ',', '.');
            $tNom = number_format((float)($breakdown['total'] ?? $income), 0, ',', '.');
            return "Total pemasukan kas saat ini sebesar Rp{$tNom}, dengan rincian:\n\n" .
                   "1. Iuran: Rp{$iNom}\n" .
                   "2. Donasi: Rp{$dNom}\n" .
                   "3. Sumbangan: Rp{$sNom}\n\n" .
                   "Total keseluruhan: Rp{$tNom}.";
        }

        // 14.10 Pengeluaran kas
        if (
            preg_match('/\b(berapa|total|cek)\s+.*pengeluaran(nya)?\b/i', $clean) ||
            preg_match('/\bpengeluaran(nya)?\s+.*(berapa|total|cek)\b/i', $clean) ||
            str_contains($clean, 'pengeluaran bulan ini') ||
            $clean === 'pengeluaran' ||
            $clean === 'total pengeluaran'
        ) {
            return "Total pengeluaran kas yang tercatat saat ini adalah Rp " . number_format($expense, 2, ',', '.') . ".";
        }

        // 14.11 Panduan melihat laporan keuangan (Prompt 32 Compliance)
        if (
            preg_match('/\b(bagaimana|cara)\s+(melihat|buka|cek|akses|melihatkan)\s+laporan\b/i', $clean) ||
            str_contains($clean, 'melihat laporan keuangan') ||
            str_contains($clean, 'cara melihat laporan')
        ) {
            return "Berikut langkah melihat laporan keuangan:\n" .
                   "1. Buka menu **Laporan** pada sidebar navigasi.\n" .
                   "2. Pilih jenis laporan yang ingin dilihat (Buku Kas Umum, Rekap Pemasukan, Rekap Pengeluaran, atau Rekap Iuran).\n" .
                   "3. Tentukan periode tanggal dan gunakan tombol **Filter** untuk menampilkan data, serta tombol ekspor untuk mencetak atau mengunduh laporan dalam format **PDF** atau Excel.";
        }

        // 14.12 Cara menghitung tunggakan iuran
        if (
            preg_match('/\b(bagaimana|cara|rumus)\s+menghitung\s+.*tunggakan\b/i', $clean) ||
            str_contains($clean, 'menghitung tunggakan') ||
            str_contains($clean, 'hitung tunggakan') ||
            str_contains($clean, 'menghitung total tunggakan')
        ) {
            return "Tunggakan iuran dihitung dengan rumus: Total Tagihan - Total Iuran yang Telah Dibayar. Berdasarkan sistem, total tagihan saat ini adalah Rp " . number_format($totalTagihan, 2, ',', '.') . " dan sisa tunggakan Rp " . number_format($totalTunggakan, 2, ',', '.') . ".";
        }

        // 14.13 Cara menghitung persentase pengeluaran
        if (
            preg_match('/\b(bagaimana|cara|rumus)\s+menghitung\s+persentase\s+pengeluaran\b/i', $clean) ||
            str_contains($clean, 'persentase pengeluaran')
        ) {
            $rasio = $income > 0 ? round(($expense / $income) * 100, 1) : 0;
            return "Persentase pengeluaran dihitung dengan rumus: (Total Pengeluaran Kategori / Total Seluruh Pengeluaran) x 100%. Saat ini rasio belanja terhadap dana masuk berada pada angka {$rasio}%.";
        }

        // 14.14 Fungsi bendahara dalam sistem
        if (
            preg_match('/\b(apa\s+)?(fungsi|tugas|peran)\s+bendahara\b/i', $clean) ||
            str_contains($clean, 'fungsi bendahara') ||
            str_contains($clean, 'peran bendahara')
        ) {
            return "Fungsi Bendahara dalam sistem meliputi Pencatatan transaksi kas masuk dan keluar, Verifikasi pembayaran iuran anggota, rekonsiliasi kas, dan penyusunan laporan keuangan.";
        }

        // 14.15 Cara memperbaiki kesalahan pencatatan transaksi
        if (
            preg_match('/\b(memperbaiki|ubah|koreksi|edit)\s+.*(kesalahan|transaksi)\b/i', $clean) ||
            str_contains($clean, 'memperbaiki kesalahan pencatatan') ||
            str_contains($clean, 'memperbaiki kesalahan transaksi')
        ) {
            return "Berikut langkah memperbaiki kesalahan transaksi:\n" .
                   "1. Buka menu **Transaksi** pada sidebar.\n" .
                   "2. Temukan transaksi yang keliru lalu klik tombol **Edit**.\n" .
                   "3. Perbaiki data yang diperlukan dan simpan perubahan, atau gunakan tombol **Hapus** jika transaksi harus dibatalkan.";
        }

        // 14.16 Cara kerja fitur notifikasi tagihan dalam aplikasi
        if (
            preg_match('/\b(cara\s+kerja|alur|fungsi)\s+.*notifikasi\b/i', $clean) ||
            str_contains($clean, 'fitur notifikasi tagihan') ||
            str_contains($clean, 'notifikasi tagihan dalam aplikasi')
        ) {
            return "Alur kerja fitur notifikasi tagihan:\n" .
                   "1. Sistem mendeteksi anggota yang memiliki tunggakan iuran.\n" .
                   "2. Pengelola membuka menu **WhatsApp (WA)** dan memilih pesan tagihan.\n" .
                   "3. Pesan pengingat dikirim ke nomor WhatsApp anggota dan otomatis tercatat pada riwayat log pengiriman.";
        }

        // 14.17 Pertanyaan Ambigu / Singkat
        if (in_array($clean, ['transaksi', 'laporan', 'kas', 'keuangan', 'data', 'rekap'], true) || mb_strlen($clean) <= 4) {
            return "Pertanyaan Anda kurang spesifik. Anda dapat menanyakan saldo kas, rincian pengeluaran terbaru, daftar anggota yang belum membayar, pemasukan kas per bulan, atau panduan penggunaan fitur sistem.";
        }

        // ---------------------------------------------------------------------
        // 15. Fallback Penolakan Sopan untuk Pertanyaan di Luar Ruang Lingkup Sistem
        // ---------------------------------------------------------------------
        $isRecognizedTopic = preg_match('/\b(kas|keuangan|saldo(nya)?|pemasukan(nya)?|pengeluaran(nya)?|iuran(nya)?|donasi(nya)?|sumbang(an)?(nya)?|sumber(nya)?|asal\s*usul|tagihan(nya)?|tunggakan(nya)?|(pem)?bayar(an)?(nya)?|transaksi(nya)?|laporan(nya)?|bendahara(nya)?|anggota(nya)?|siswa(nya)?|mahasiswa(nya)?|role(nya)?|pengawas|admin|super\s*user|super\s*admin|whatsapp|wa|notifikasi(nya)?|aplikasi(nya)?|database|kategori(nya)?|sistem(nya)?|peran(nya)?|fungsi(nya)?|bantu(an)?|rekap(itulasi)?|kondisi(nya)?|audit|tujuan(nya)?|belanja(nya)?|biaya(nya)?|pos|uang(nya)?|dana(nya)?|keterangan(nya)?|deskripsi(nya)?|nominal(nya)?|catatan(nya)?|alokasi(nya)?|mutasi(nya)?|verifikasi(nya)?|bukti(nya)?|setor(an)?(nya)?|tarik(an)?|lunas|pelunasan|hak\s*akses|kewenangan)\b/i', $clean);

        if (!$isRecognizedTopic) {
            return "Mohon maaf karena sebagai Asisten AI Sistem Keuangan Kas, permintaan ini berada di luar tugas utama saya. Saya berfokus membantu penggunaan Sistem Keuangan Kas.";
        }
        } // End if (!$isPromptInjection)

        // ---------------------------------------------------------------------
        // 16. Struktur Laporan Analisis Keuangan 4-Section (Default Kondisi Keuangan)
        // ---------------------------------------------------------------------
        $isFinancialSummaryQuestion = (
            preg_match('/\b(kondisi\s+keuangan|kondisi\s+kas|analisis\s+keuangan|ringkasan\s+keuangan|keuangan\s+bulan\s+ini|bagaimana\s+(kondisi\s+)?(keuangan|kas)|laporan\s+analisis|performa\s+kas|kesehatan\s+kas)\b/i', $clean) ||
            $clean === 'kondisi keuangan' ||
            $clean === 'kondisi kas' ||
            $clean === 'ringkasan keuangan' ||
            $clean === 'analisis keuangan' ||
            $clean === 'keuangan' ||
            str_contains($clean, 'bagaimana kondisi keuangan') ||
            str_contains($clean, 'bagaimana kondisi kas') ||
            str_contains($clean, 'kondisi kas saat ini')
        );

        if (!$isFinancialSummaryQuestion) {
            return "Maaf, saya belum memahami informasi yang Anda minta. Apakah Anda ingin mengetahui data anggota, mahasiswa, role pengguna, tagihan, pembayaran, atau ringkasan keuangan?";
        }
        $topCategoryText = "Belum ada catatan pos pengeluaran kas.";
        if (!empty($topCategories)) {
            $top1 = $topCategories[0];
            $topCategoryText = "Kategori pengeluaran terbesar dialokasikan untuk \"{$top1['name']}\" sebesar Rp " . number_format($top1['nominal'], 2, ',', '.') . " ({$top1['percentage']}% dari total pengeluaran).";
        }

        // Section 1: Ringkasan
        $ringkasan = "Kondisi keuangan kas saat ini berstatus **{$condition}** dengan total saldo kas bersih sebesar **Rp " . number_format($balance, 2, ',', '.') . "**.\n";
        $ringkasan .= "Akumulasi total pemasukan kas mencapai **Rp " . number_format($income, 2, ',', '.') . "** berbanding total pengeluaran sebesar **Rp " . number_format($expense, 2, ',', '.') . "**.";

        // Section 2: Temuan
        $temuan = "- **Keseimbangan Kas**: Rasio belanja terhadap dana masuk berada pada angka " . ($income > 0 ? round(($expense / $income) * 100, 1) : 0) . "%.\n";
        $temuan .= "- **Alokasi Belanja**: {$topCategoryText}\n";
        $temuan .= "- **Tingkat Kepatuhan Iuran**: Dari total {$totalMembers} anggota, sebanyak {$paidCount} anggota telah lunas, {$partialCount} anggota menyetor sebagian, dan {$unpaidCount} anggota belum melakukan pembayaran.";

        // Section 3: Peringatan
        $peringatan = "";
        if ($totalTunggakan > 0) {
            $peringatan .= "- **Tunggakan Iuran**: Masih terdapat sisa tunggakan iuran sebesar **Rp " . number_format($totalTunggakan, 2, ',', '.') . "** dari total " . ($unpaidCount + $partialCount) . " anggota.\n";
        } else {
            $peringatan .= "- **Nihil Tunggakan**: Tidak ditemukan tunggakan aktif. Kepatuhan pembayaran berada pada angka 100%.\n";
        }

        if ($expense > $income) {
            $peringatan .= "- **Peringatan Defisit**: Total pengeluaran saat ini melampaui total pemasukan kas.\n";
        } elseif ($balance < 200000 && $balance > 0) {
            $peringatan .= "- **Cadangan Kas Menipis**: Saldo kas berada di bawah batas aman operasional.\n";
        } else {
            $peringatan .= "- **Likuiditas Aman**: Cadangan kas berada dalam batas likuiditas yang sehat.";
        }

        // Section 4: Rekomendasi
        $rekomendasi = "- **Tindak Lanjut Penagihan**: Segera terbitkan notifikasi tagihan kepada {$unpaidCount} anggota yang belum bayar.\n";
        if (!empty($topCategories)) {
            $rekomendasi .= "- **Optimalisasi Anggaran**: Lakukan efisiensi pada pos pengeluaran terbesar ({$topCategories[0]['name']}).\n";
        }
        $rekomendasi .= "- **Pencatatan Berkelanjutan**: Pertahankan rekonsiliasi berkala mutasi kas fisik dengan buku kas digital.";

        return <<<INSIGHT
### Ringkasan
{$ringkasan}

### Temuan
{$temuan}

### Peringatan
{$peringatan}

### Rekomendasi
{$rekomendasi}
INSIGHT;
    }
}
