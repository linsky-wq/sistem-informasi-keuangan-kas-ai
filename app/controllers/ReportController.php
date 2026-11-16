<?php
/**
 * Report Controller (Laporan Keuangan & Rekonsiliasi Kas)
 * Super User Only — Handles Financial Statements, Ledger, Payments & Arrears Reports
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Member;
use App\Models\Category;
use App\Models\Report;

class ReportController extends Controller {
    protected Transaction $trxModel;
    protected Payment $paymentModel;
    protected Member $memberModel;
    protected Category $categoryModel;
    protected Report $reportModel;

    public function __construct() {
        // Enforce RBAC: Super User, Bendahara, and Pengawas have access
        Auth::requireRole(Auth::ROLE_SUPER_USER, Auth::ROLE_BENDAHARA, Auth::ROLE_PENGAWAS);

        $this->trxModel = new Transaction();
        $this->paymentModel = new Payment();
        $this->memberModel = new Member();
        $this->categoryModel = new Category();
        $this->reportModel = new Report();
    }

    /**
     * Financial Reports Master Dashboard & Tables
     */
    public function index(): void {
        $tab = trim((string)$this->get('tab', 'saldo'));
        if (!in_array($tab, ['pemasukan', 'pengeluaran', 'saldo', 'pembayaran', 'tunggakan'], true)) {
            $tab = 'saldo';
        }

        $startDate = trim((string)$this->get('start_date', ''));
        $endDate = trim((string)$this->get('end_date', ''));
        $kelas = trim((string)$this->get('kelas', ''));
        $categoryId = (int)$this->get('category_id', 0);
        $tipe = trim((string)$this->get('tipe', ''));
        $periode = trim((string)$this->get('periode', ''));
        $search = trim((string)$this->get('search', ''));

        $filters = [
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'kelas'       => $kelas,
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'tipe'        => $tipe,
            'periode'     => $periode,
            'search'      => $search,
        ];

        // 1. Laporan Pemasukan
        $incomeReport = $this->trxModel->getReportTransactions(array_merge($filters, ['tipe' => Transaction::TYPE_PEMASUKAN]));

        // 2. Laporan Pengeluaran
        $expenseReport = $this->trxModel->getReportTransactions(array_merge($filters, ['tipe' => Transaction::TYPE_PENGELUARAN]));

        // 3. Laporan Saldo (General Ledger)
        $saldoReport = $this->trxModel->getReportTransactions($filters);

        // 4. Laporan Pembayaran
        $paymentsReport = $this->paymentModel->getPaymentsReport($filters);

        // 5. Laporan Tunggakan (Arrears)
        $arrearsReport = $this->paymentModel->getArrearsReport($filters);

        // Executive Summary
        $executiveSummary = $this->reportModel->getExecutiveSummary($filters);

        // Filter Options
        $filterOptions = $this->reportModel->getFilterOptions();

        $this->view('reports.index', [
            'title'            => 'Laporan Keuangan Kas — ' . APP_NAME,
            'activeTab'        => $tab,
            'filters'          => $filters,
            'incomeReport'     => $incomeReport,
            'expenseReport'    => $expenseReport,
            'saldoReport'      => $saldoReport,
            'paymentsReport'   => $paymentsReport,
            'arrearsReport'    => $arrearsReport,
            'executiveSummary' => $executiveSummary,
            'classes'          => $filterOptions['classes'],
            'categories'       => $filterOptions['categories'],
            'periods'          => $filterOptions['periods'],
            'todayDate'        => date('d F Y'),
        ]);
    }

    /**
     * Print View for Financial Reports
     */
    public function print(): void {
        $tab = trim((string)$this->get('tab', 'saldo'));
        $startDate = trim((string)$this->get('start_date', ''));
        $endDate = trim((string)$this->get('end_date', ''));
        $kelas = trim((string)$this->get('kelas', ''));
        $categoryId = (int)$this->get('category_id', 0);
        $tipe = trim((string)$this->get('tipe', ''));

        $filters = [
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'kelas'       => $kelas,
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'tipe'        => $tipe,
        ];

        $incomeReport = $this->trxModel->getReportTransactions(array_merge($filters, ['tipe' => Transaction::TYPE_PEMASUKAN]));
        $expenseReport = $this->trxModel->getReportTransactions(array_merge($filters, ['tipe' => Transaction::TYPE_PENGELUARAN]));
        $saldoReport = $this->trxModel->getReportTransactions($filters);
        $paymentsReport = $this->paymentModel->getPaymentsReport($filters);
        $arrearsReport = $this->paymentModel->getArrearsReport($filters);
        $executiveSummary = $this->reportModel->getExecutiveSummary($filters);

        $this->view('reports.print', [
            'title'            => 'Cetak Laporan Keuangan — ' . APP_NAME,
            'activeTab'        => $tab,
            'filters'          => $filters,
            'incomeReport'     => $incomeReport,
            'expenseReport'    => $expenseReport,
            'saldoReport'      => $saldoReport,
            'paymentsReport'   => $paymentsReport,
            'arrearsReport'    => $arrearsReport,
            'executiveSummary' => $executiveSummary,
            'todayDate'        => date('d F Y'),
            'user'             => Auth::user(),
        ]);
    }

    /**
     * Unified Export Handler for PDF, CSV, and Excel
     */
    public function export(): void {
        $format = strtolower(trim((string)$this->get('format', 'csv')));
        $tab = trim((string)$this->get('tab', 'saldo'));
        if (!in_array($tab, ['pemasukan', 'pengeluaran', 'saldo', 'pembayaran', 'tunggakan'], true)) {
            $tab = 'saldo';
        }

        $startDate = trim((string)$this->get('start_date', ''));
        $endDate = trim((string)$this->get('end_date', ''));
        $kelas = trim((string)$this->get('kelas', ''));
        $categoryId = (int)$this->get('category_id', 0);
        $tipe = trim((string)$this->get('tipe', ''));
        $periode = trim((string)$this->get('periode', ''));
        $search = trim((string)$this->get('search', ''));

        $filters = [
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'kelas'       => $kelas,
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'tipe'        => $tipe,
            'periode'     => $periode,
            'search'      => $search,
        ];

        // Format period label
        if (!empty($startDate) && !empty($endDate)) {
            $periodLabel = date('d/m/Y', strtotime($startDate)) . ' s/d ' . date('d/m/Y', strtotime($endDate));
        } elseif (!empty($startDate)) {
            $periodLabel = date('d/m/Y', strtotime($startDate)) . ' s/d Sekarang';
        } elseif (!empty($endDate)) {
            $periodLabel = 'Sampai ' . date('d/m/Y', strtotime($endDate));
        } elseif (!empty($periode)) {
            $periodLabel = $periode;
        } else {
            $periodLabel = 'Semua Periode';
        }

        $reportTitle = '';
        $headers = [];
        $colWidths = [];
        $colAligns = [];
        $rows = [];
        $totalRow = null;

        // 1. Prepare data based on report type
        if ($tab === 'pemasukan') {
            $reportTitle = 'Laporan Pemasukan Kas';
            $data = $this->trxModel->getReportTransactions(array_merge($filters, ['tipe' => Transaction::TYPE_PEMASUKAN]));

            $headers = ['No', 'Tanggal', 'Kode Transaksi', 'Kategori', 'Nominal (Rp)', 'Keterangan'];
            $colWidths = [1, 2, 3, 3, 3, 4];
            $colAligns = ['C', 'C', 'L', 'L', 'R', 'L'];

            foreach ($data['data'] as $idx => $r) {
                $rows[] = [
                    $idx + 1,
                    date('d/m/Y', strtotime($r['tanggal'])),
                    $r['kode_transaksi'],
                    $r['category_name'],
                    'Rp ' . number_format((float)$r['nominal'], 2, ',', '.'),
                    $r['keterangan']
                ];
            }
            $totalRow = ['TOTAL PEMASUKAN', '', '', '', 'Rp ' . number_format($data['total_income'], 2, ',', '.'), ''];

        } elseif ($tab === 'pengeluaran') {
            $reportTitle = 'Laporan Pengeluaran Kas';
            $data = $this->trxModel->getReportTransactions(array_merge($filters, ['tipe' => Transaction::TYPE_PENGELUARAN]));

            $headers = ['No', 'Tanggal', 'Kode Transaksi', 'Kategori', 'Nominal (Rp)', 'Keterangan'];
            $colWidths = [1, 2, 3, 3, 3, 4];
            $colAligns = ['C', 'C', 'L', 'L', 'R', 'L'];

            foreach ($data['data'] as $idx => $r) {
                $rows[] = [
                    $idx + 1,
                    date('d/m/Y', strtotime($r['tanggal'])),
                    $r['kode_transaksi'],
                    $r['category_name'],
                    'Rp ' . number_format((float)$r['nominal'], 2, ',', '.'),
                    $r['keterangan']
                ];
            }
            $totalRow = ['TOTAL PENGELUARAN', '', '', '', 'Rp ' . number_format($data['total_expense'], 2, ',', '.'), ''];

        } elseif ($tab === 'saldo') {
            $reportTitle = 'Laporan Saldo & Rekonsiliasi Kas';
            $data = $this->trxModel->getReportTransactions($filters);

            $headers = ['Tanggal', 'Kode Transaksi', 'Kategori', 'Pemasukan (Rp)', 'Pengeluaran (Rp)', 'Saldo (Rp)', 'Keterangan'];
            $colWidths = [2, 2.8, 2.8, 2.8, 2.8, 3, 3.5];
            $colAligns = ['C', 'L', 'L', 'R', 'R', 'R', 'L'];

            $running = 0.0;
            foreach ($data['data'] as $r) {
                $nom = (float)$r['nominal'];
                if ($r['tipe'] === 'pemasukan') {
                    $running += $nom;
                } else {
                    $running -= $nom;
                }
                $rows[] = [
                    date('d/m/Y', strtotime($r['tanggal'])),
                    $r['kode_transaksi'],
                    $r['category_name'],
                    $r['tipe'] === 'pemasukan' ? 'Rp ' . number_format($nom, 2, ',', '.') : '-',
                    $r['tipe'] === 'pengeluaran' ? 'Rp ' . number_format($nom, 2, ',', '.') : '-',
                    'Rp ' . number_format($running, 2, ',', '.'),
                    $r['keterangan']
                ];
            }
            $totalRow = [
                'TOTAL',
                '',
                '',
                'Rp ' . number_format($data['total_income'], 2, ',', '.'),
                'Rp ' . number_format($data['total_expense'], 2, ',', '.'),
                'Rp ' . number_format($data['net_balance'], 2, ',', '.'),
                ''
            ];

        } elseif ($tab === 'pembayaran') {
            $reportTitle = 'Laporan Pembayaran Kas Anggota';
            $data = $this->paymentModel->getPaymentsReport($filters);

            $headers = ['No', 'Anggota', 'Kelas', 'Periode', 'Tagihan (Rp)', 'Pembayaran (Rp)', 'Status'];
            $colWidths = [1, 3.5, 2, 2.5, 3, 3, 2];
            $colAligns = ['C', 'L', 'C', 'L', 'R', 'R', 'C'];

            foreach ($data['data'] as $idx => $p) {
                $rows[] = [
                    $idx + 1,
                    $p['member_name'] . ' (' . $p['nomor_anggota'] . ')',
                    $p['kelas'],
                    $p['periode'],
                    'Rp ' . number_format((float)$p['tagihan'], 2, ',', '.'),
                    'Rp ' . number_format((float)$p['nominal'], 2, ',', '.'),
                    strtoupper($p['status'])
                ];
            }
            $totalRow = [
                'TOTAL',
                '',
                '',
                '',
                'Rp ' . number_format($data['total_tagihan'], 2, ',', '.'),
                'Rp ' . number_format($data['total_paid'], 2, ',', '.'),
                ''
            ];

        } elseif ($tab === 'tunggakan') {
            $reportTitle = 'Laporan Tunggakan Kas Peserta Didik';
            $data = $this->paymentModel->getArrearsReport($filters);

            $headers = ['No', 'Anggota', 'Kelas', 'Periode Tagihan', 'Nilai Tagihan (Rp)', 'Sudah Dibayar (Rp)', 'Sisa Tunggakan (Rp)', 'Status'];
            $colWidths = [1, 3.5, 1.8, 2.5, 2.5, 2.5, 3, 2];
            $colAligns = ['C', 'L', 'C', 'L', 'R', 'R', 'R', 'C'];

            foreach ($data['data'] as $idx => $arr) {
                $rows[] = [
                    $idx + 1,
                    $arr['member_name'] . ' (' . $arr['nomor_anggota'] . ')',
                    $arr['kelas'],
                    $arr['periode'],
                    'Rp ' . number_format((float)$arr['tagihan'], 2, ',', '.'),
                    'Rp ' . number_format((float)$arr['nominal'], 2, ',', '.'),
                    'Rp ' . number_format((float)$arr['sisa'], 2, ',', '.'),
                    strtoupper($arr['status'])
                ];
            }
            $totalRow = [
                'TOTAL SISA TUNGGAKAN',
                '',
                '',
                '',
                '',
                '',
                'Rp ' . number_format($data['total_tunggakan'], 2, ',', '.'),
                ''
            ];
        }

        $dateSlug = date('Ymd_His');
        $fileBaseName = "laporan_{$tab}_{$dateSlug}";

        // 2. Export based on format
        if ($format === 'pdf') {
            $orientation = ($tab === 'saldo' || $tab === 'tunggakan') ? 'L' : 'P';
            $pdf = new \App\Helpers\PdfExporter($reportTitle, $periodLabel, $orientation);
            $pdf->setTableHeaders($headers, $colWidths, $colAligns);
            $pdf->addRows($rows);
            if ($totalRow) {
                $pdf->setTotalRow($totalRow);
            }
            $pdf->download("{$fileBaseName}.pdf");
            return;

        } elseif ($format === 'excel' || $format === 'xls') {
            $excel = new \App\Helpers\ExcelExporter($reportTitle, $periodLabel);
            $excel->setHeaders($headers);
            $excel->addRows($rows);
            if ($totalRow) {
                $excel->setTotalRow($totalRow);
            }
            $excel->download("{$fileBaseName}.xls");
            return;

        } else {
            // Default CSV
            $csv = new \App\Helpers\CsvExporter($reportTitle, $periodLabel);
            $csv->setHeaders($headers);
            $csv->addRows($rows);
            if ($totalRow) {
                $csv->setTotalRow($totalRow);
            }
            $csv->download("{$fileBaseName}.csv");
            return;
        }
    }

    /**
     * Shortcut for PDF Export
     */
    public function pdf(): void {
        $_GET['format'] = 'pdf';
        $this->export();
    }

    /**
     * Shortcut for CSV Export
     */
    public function csv(): void {
        $_GET['format'] = 'csv';
        $this->export();
    }

    /**
     * Shortcut for Excel Export
     */
    public function excel(): void {
        $_GET['format'] = 'excel';
        $this->export();
    }
}
