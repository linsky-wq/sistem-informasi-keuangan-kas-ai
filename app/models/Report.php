<?php
/**
 * Report Model / Helper
 * Provides aggregated financial reporting methods and reconciliation data
 */

namespace App\Models;

use App\Core\Model;

class Report extends Model {
    private Transaction $trxModel;
    private Payment $paymentModel;
    private Member $memberModel;
    private Category $categoryModel;

    public function __construct() {
        parent::__construct();
        $this->trxModel = new Transaction();
        $this->paymentModel = new Payment();
        $this->memberModel = new Member();
        $this->categoryModel = new Category();
    }

    /**
     * Get overall financial summary for report header
     */
    public function getExecutiveSummary(array $filters = []): array {
        $trxReport = $this->trxModel->getReportTransactions($filters);
        $paymentsReport = $this->paymentModel->getPaymentsReport($filters);
        $arrearsReport = $this->paymentModel->getArrearsReport($filters);

        return [
            'total_income'    => $trxReport['total_income'],
            'total_expense'   => $trxReport['total_expense'],
            'net_balance'     => $trxReport['net_balance'],
            'total_tagihan'   => $paymentsReport['total_tagihan'],
            'total_paid'      => $paymentsReport['total_paid'],
            'total_tunggakan' => $arrearsReport['total_tunggakan'],
            'count_arrears'   => $arrearsReport['count'],
        ];
    }

    /**
     * Get distinct filter options
     */
    public function getFilterOptions(): array {
        return [
            'classes'    => $this->memberModel->getDistinctClasses(),
            'categories' => $this->categoryModel->all('tipe ASC, nama ASC'),
            'periods'    => $this->paymentModel->getDistinctPeriods(),
        ];
    }
}
