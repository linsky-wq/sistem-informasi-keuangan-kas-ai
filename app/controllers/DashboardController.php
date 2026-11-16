<?php
/**
 * Main Dashboard Controller (Route Dispatcher for Logged-in Users)
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Transaction;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Category;
use App\Models\WhatsAppLog;

class DashboardController extends Controller {
    public function index(): void {
        Auth::requireLogin();

        // If member, route to Member Dashboard
        if (Auth::isMember()) {
            $this->redirect('member/dashboard');
            return;
        }

        // Operational Dashboard (Super User, Bendahara, Pengawas)
        $trxModel = new Transaction();
        $memberModel = new Member();
        $paymentModel = new Payment();
        $categoryModel = new Category();
        $waLogModel = new WhatsAppLog();

        // 1. Summary Cards Data
        $totalIncome = $trxModel->getTotalIncome();
        $incomeBreakdown = $trxModel->getIncomeBreakdown();
        $totalExpense = $trxModel->getTotalExpense();
        $currentBalance = $trxModel->getCurrentBalance();
        $totalMembers = $memberModel->count();
        $totalCategories = $categoryModel->count();

        // Payment status & metrics
        $paymentStats = $paymentModel->getSummaryStats();
        $statusDistribution = $paymentModel->getStatusDistribution();
        $unpaidMembersCount = $paymentModel->getUnpaidMembersCount();

        $countPaid = $statusDistribution['lunas'];
        $countUnpaid = $statusDistribution['belumBayar'] + $statusDistribution['sebagian'];

        // 2. Charts Data
        $monthlyComparison = $trxModel->getMonthlyComparison(6);
        $balanceTrend = $trxModel->getBalanceTrend(15);

        // 3. Recent 5 Transactions (tanggal, kode, tipe, nominal, keterangan)
        $recentTransactions = $trxModel->getAllWithRelations(['limit' => 5]);

        // 4. Recent 5 Payments (Status Iuran & Verifikasi)
        $recentPayments = $paymentModel->getPaginated([], 1, 5)['data'] ?? [];

        // 5. Unpaid Members (Daftar Tunggakan Ringkas)
        $unpaidList = $paymentModel->getPaginated(['status' => 'Belum Bayar'], 1, 5)['data'] ?? [];

        // 6. Recent WhatsApp Logs & Stats
        $recentWaLogs = $waLogModel->getRecent(5);
        $waStats = $waLogModel->getStats();

        // Flags for Empty State
        $hasTransactions = !empty($recentTransactions);
        $hasPayments = $statusDistribution['hasData'];

        $roleName = match(Auth::role()) {
            Auth::ROLE_SUPER_USER, 'admin' => 'Super User',
            Auth::ROLE_BENDAHARA => 'Bendahara',
            Auth::ROLE_PENGAWAS => 'Pengawas',
            default => 'Keuangan'
        };

        $this->view('dashboard.index', [
            'title'               => 'Dashboard ' . $roleName . ' — ' . APP_NAME,
            'user'                => Auth::user(),
            'roleName'            => $roleName,
            'totalIncome'         => $totalIncome,
            'incomeBreakdown'     => $incomeBreakdown,
            'totalExpense'        => $totalExpense,
            'currentBalance'      => $currentBalance,
            'totalMembers'        => $totalMembers,
            'totalCategories'     => $totalCategories,
            'countPaid'           => $countPaid,
            'countUnpaid'         => $countUnpaid,
            'unpaidMembersCount'  => $unpaidMembersCount,
            'totalTunggakan'      => $paymentStats['total_tunggakan'],
            'monthlyComparison'   => $monthlyComparison,
            'balanceTrend'        => $balanceTrend,
            'statusDistribution'  => $statusDistribution,
            'recentTransactions'  => $recentTransactions,
            'recentPayments'      => $recentPayments,
            'unpaidList'          => $unpaidList,
            'recentWaLogs'        => $recentWaLogs,
            'waStats'             => $waStats,
            'hasTransactions'     => $hasTransactions,
            'hasPayments'         => $hasPayments,
        ]);
    }
}
