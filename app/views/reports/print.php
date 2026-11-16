<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Laporan Keuangan Kas') ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #000; background: #fff; }
        .kop-surat { border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        table th, table td { border: 1px solid #000 !important; padding: 6px 8px !important; }
        table thead th { background-color: #f2f2f2 !important; text-align: center; }
        .no-border, .no-border td { border: none !important; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 10mm; }
        }
    </style>
</head>
<body>
    <!-- Print Action Bar -->
    <div class="no-print bg-light p-3 border-bottom mb-4 text-center">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-bold">
            Cetak Dokumen Sekarang (Print / PDF)
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3 ms-2">
            Tutup Jendela
        </button>
    </div>

    <!-- Kop Surat Resmi -->
    <div class="kop-surat text-center">
        <h4 class="fw-bold text-uppercase mb-0"><?= e(APP_NAME) ?></h4>
        <h5 class="fw-bold mb-1">PENGELOLA KAS PESERTA DIDIK & ORGANISASI</h5>
        <p class="small text-muted mb-0">Alamat Lingkungan Sekolah &bull; Tahun Ajaran 2026/2027 &bull; Email: kas@sekolah.sch.id</p>
    </div>

    <!-- Judul Laporan -->
    <div class="text-center mb-4">
        <h5 class="fw-bold text-uppercase mb-1">
            <?php
                if ($activeTab === 'pemasukan') echo "LAPORAN PENERIMAAN / PEMASUKAN KAS";
                elseif ($activeTab === 'pengeluaran') echo "LAPORAN PENGELUARAN KAS";
                elseif ($activeTab === 'saldo') echo "LAPORAN BUKU KAS UMUM & SALDO";
                elseif ($activeTab === 'pembayaran') echo "LAPORAN IURAN & PEMBAYARAN KAS ANGGOTA";
                elseif ($activeTab === 'tunggakan') echo "LAPORAN TUNGGAKAN IURAN KAS PESERTA DIDIK";
                else echo "LAPORAN KEUANGAN KAS";
            ?>
        </h5>
        <p class="small mb-0">
            Dicetak pada: <?= date('d F Y, H:i') ?> WIB &bull; Petugas: <?= e($user['username'] ?? 'Super User') ?>
            <?php if (!empty($filters['start_date']) || !empty($filters['end_date'])): ?>
                <br>Periode: <?= e($filters['start_date'] ?: 'Awal') ?> s/d <?= e($filters['end_date'] ?: 'Sekarang') ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Data Tables -->
    <?php if ($activeTab === 'pemasukan'): ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Tanggal</th>
                    <th>Kode Transaksi</th>
                    <th>Kategori</th>
                    <th>Nominal (Rp)</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($incomeReport['data'])): ?>
                    <?php foreach ($incomeReport['data'] as $idx => $r): ?>
                        <tr>
                            <td class="text-center"><?= $idx + 1 ?></td>
                            <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                            <td><?= e($r['kode_transaksi']) ?></td>
                            <td><?= e($r['category_name']) ?></td>
                            <td class="text-end">Rp <?= number_format((float)$r['nominal'], 2, ',', '.') ?></td>
                            <td><?= e($r['keterangan']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-3">Tidak ada data pemasukan.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">TOTAL PEMASUKAN:</td>
                    <td class="text-end">Rp <?= number_format($incomeReport['total_income'], 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

    <?php elseif ($activeTab === 'pengeluaran'): ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Tanggal</th>
                    <th>Kode Transaksi</th>
                    <th>Kategori</th>
                    <th>Nominal (Rp)</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($expenseReport['data'])): ?>
                    <?php foreach ($expenseReport['data'] as $idx => $r): ?>
                        <tr>
                            <td class="text-center"><?= $idx + 1 ?></td>
                            <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                            <td><?= e($r['kode_transaksi']) ?></td>
                            <td><?= e($r['category_name']) ?></td>
                            <td class="text-end">Rp <?= number_format((float)$r['nominal'], 2, ',', '.') ?></td>
                            <td><?= e($r['keterangan']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-3">Tidak ada data pengeluaran.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">TOTAL PENGELUARAN:</td>
                    <td class="text-end">Rp <?= number_format($expenseReport['total_expense'], 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

    <?php elseif ($activeTab === 'saldo'): ?>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kode Transaksi</th>
                    <th>Kategori</th>
                    <th>Pemasukan (Rp)</th>
                    <th>Pengeluaran (Rp)</th>
                    <th>Saldo (Rp)</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($saldoReport['data'])): ?>
                    <?php $running = 0.0; ?>
                    <?php foreach ($saldoReport['data'] as $r): ?>
                        <?php
                            $nom = (float)$r['nominal'];
                            if ($r['tipe'] === 'pemasukan') $running += $nom;
                            else $running -= $nom;
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                            <td><?= e($r['kode_transaksi']) ?></td>
                            <td><?= e($r['category_name']) ?></td>
                            <td class="text-end"><?= $r['tipe'] === 'pemasukan' ? number_format($nom, 2, ',', '.') : '-' ?></td>
                            <td class="text-end"><?= $r['tipe'] === 'pengeluaran' ? number_format($nom, 2, ',', '.') : '-' ?></td>
                            <td class="text-end fw-bold"><?= number_format($running, 2, ',', '.') ?></td>
                            <td><?= e($r['keterangan']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-3">Tidak ada data transaksi.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">TOTAL:</td>
                    <td class="text-end">Rp <?= number_format($saldoReport['total_income'], 2, ',', '.') ?></td>
                    <td class="text-end">Rp <?= number_format($saldoReport['total_expense'], 2, ',', '.') ?></td>
                    <td class="text-end">Rp <?= number_format($saldoReport['net_balance'], 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

    <?php elseif ($activeTab === 'pembayaran'): ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Anggota</th>
                    <th>Kelas</th>
                    <th>Periode</th>
                    <th>Tagihan</th>
                    <th>Pembayaran</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($paymentsReport['data'])): ?>
                    <?php foreach ($paymentsReport['data'] as $idx => $p): ?>
                        <tr>
                            <td class="text-center"><?= $idx + 1 ?></td>
                            <td><?= e($p['member_name']) ?> (<?= e($p['nomor_anggota']) ?>)</td>
                            <td><?= e($p['kelas']) ?></td>
                            <td><?= e($p['periode']) ?></td>
                            <td class="text-end">Rp <?= number_format((float)$p['tagihan'], 2, ',', '.') ?></td>
                            <td class="text-end">Rp <?= number_format((float)$p['nominal'], 2, ',', '.') ?></td>
                            <td class="text-center"><?= strtoupper(e($p['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-3">Tidak ada data pembayaran.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">TOTAL:</td>
                    <td class="text-end">Rp <?= number_format($paymentsReport['total_tagihan'], 2, ',', '.') ?></td>
                    <td class="text-end">Rp <?= number_format($paymentsReport['total_paid'], 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

    <?php elseif ($activeTab === 'tunggakan'): ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Anggota</th>
                    <th>Kelas</th>
                    <th>Periode Tagihan</th>
                    <th>Nilai Tagihan</th>
                    <th>Sudah Dibayar</th>
                    <th>Sisa Tunggakan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($arrearsReport['data'])): ?>
                    <?php foreach ($arrearsReport['data'] as $idx => $arr): ?>
                        <tr>
                            <td class="text-center"><?= $idx + 1 ?></td>
                            <td><?= e($arr['member_name']) ?> (<?= e($arr['nomor_anggota']) ?>)</td>
                            <td><?= e($arr['kelas']) ?></td>
                            <td><?= e($arr['periode']) ?></td>
                            <td class="text-end">Rp <?= number_format((float)$arr['tagihan'], 2, ',', '.') ?></td>
                            <td class="text-end">Rp <?= number_format((float)$arr['nominal'], 2, ',', '.') ?></td>
                            <td class="text-end fw-bold">Rp <?= number_format((float)$arr['sisa'], 2, ',', '.') ?></td>
                            <td class="text-center"><?= strtoupper(e($arr['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center py-3">Tidak ada tunggakan kas. Seluruh peserta didik lunas.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="6" class="text-end">TOTAL SISA TUNGGAKAN:</td>
                    <td class="text-end">Rp <?= number_format($arrearsReport['total_tunggakan'], 2, ',', '.') ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <!-- Signature Section -->
    <div class="row mt-5 pt-3 no-border">
        <div class="col-6 text-center">
            <p class="mb-5">Mengetahui,<br><strong>Ketua Kas / Pengurus</strong></p>
            <br>
            <p class="fw-bold text-decoration-underline mb-0">( .................................................. )</p>
            <small class="text-muted">NIP / Identitas</small>
        </div>
        <div class="col-6 text-center">
            <p class="mb-5">Tanggal: <?= date('d F Y') ?><br><strong>Bendahara Kas</strong></p>
            <br>
            <p class="fw-bold text-decoration-underline mb-0"><?= e($user['username'] ?? 'Bendahara') ?></p>
            <small class="text-muted">Petugas Super User</small>
        </div>
    </div>
</body>
</html>
