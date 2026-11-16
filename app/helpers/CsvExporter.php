<?php
/**
 * CSV Exporter Helper
 * Generates RFC-4180 compliant CSV files with UTF-8 BOM for Microsoft Excel compatibility.
 */

namespace App\Helpers;

class CsvExporter {
    protected string $title;
    protected string $systemName;
    protected string $period;
    protected string $generatedAt;

    protected array $headers = [];
    protected array $rows = [];
    protected ?array $totalRow = null;

    public function __construct(
        string $title,
        string $period = 'Semua Periode',
        ?string $systemName = null
    ) {
        $this->title = $title;
        $this->period = $period;
        $this->systemName = $systemName ?? (defined('APP_NAME') ? APP_NAME : 'Sistem Keuangan Kas');
        $this->generatedAt = date('d F Y, H:i') . ' WIB';
    }

    public function setHeaders(array $headers): self {
        $this->headers = $headers;
        return $this;
    }

    public function addRow(array $row): self {
        $this->rows[] = $row;
        return $this;
    }

    public function addRows(array $rows): self {
        foreach ($rows as $r) {
            $this->addRow($r);
        }
        return $this;
    }

    public function setTotalRow(array $totalRow): self {
        $this->totalRow = $totalRow;
        return $this;
    }

    /**
     * Generate CSV string with UTF-8 BOM
     */
    public function output(): string {
        $fp = fopen('php://memory', 'r+');

        // Write UTF-8 BOM for Excel
        fwrite($fp, "\xEF\xBB\xBF");

        // 1. Metadata Headers
        fputcsv($fp, ['NAMA SISTEM', $this->systemName]);
        fputcsv($fp, ['NAMA LAPORAN', $this->title]);
        fputcsv($fp, ['PERIODE', $this->period]);
        fputcsv($fp, ['TANGGAL GENERATE', $this->generatedAt]);
        fputcsv($fp, []); // blank spacer row

        // 2. Table Column Headers
        if (!empty($this->headers)) {
            fputcsv($fp, $this->headers);
        }

        // 3. Data Rows
        if (!empty($this->rows)) {
            foreach ($this->rows as $row) {
                fputcsv($fp, $row);
            }
        } else {
            fputcsv($fp, ['(Tidak ada data laporan yang cocok dengan kriteria filter)']);
        }

        // 4. Total Row (if relevant)
        if (!empty($this->totalRow)) {
            fputcsv($fp, $this->totalRow);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv ?: '';
    }

    /**
     * Send HTTP download response
     */
    public function download(string $filename): void {
        $content = $this->output();
        if (headers_sent()) {
            echo $content;
            exit;
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $content;
        exit;
    }
}
