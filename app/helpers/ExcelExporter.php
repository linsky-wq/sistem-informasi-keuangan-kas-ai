<?php
/**
 * Excel Exporter Helper
 * Generates styled Spreadsheet documents (.xls) compatible with Microsoft Excel and Google Sheets.
 */

namespace App\Helpers;

class ExcelExporter {
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
     * Generate HTML-based Excel spreadsheet content
     */
    public function output(): string {
        $colspan = max(1, count($this->headers));

        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '<meta charset="UTF-8">' . "\n";
        $html .= '<style>' . "\n";
        $html .= 'body { font-family: Calibri, Arial, sans-serif; }' . "\n";
        $html .= 'table { border-collapse: collapse; width: 100%; }' . "\n";
        $html .= 'th, td { border: 0.5pt solid #000000; padding: 6px 10px; font-size: 11pt; }' . "\n";
        $html .= 'th { background-color: #D9E1F2; color: #000000; font-weight: bold; text-align: center; }' . "\n";
        $html .= '.title { font-size: 16pt; font-weight: bold; color: #1F4E79; text-align: left; }' . "\n";
        $html .= '.subtitle { font-size: 12pt; font-weight: bold; color: #2F5597; }' . "\n";
        $html .= '.meta { font-size: 10pt; color: #595959; }' . "\n";
        $html .= '.total { background-color: #EDEDED; font-weight: bold; }' . "\n";
        $html .= '.zebra { background-color: #F9F9F9; }' . "\n";
        $html .= '.text-center { text-align: center; }' . "\n";
        $html .= '.text-right { text-align: right; }' . "\n";
        $html .= '</style>' . "\n";
        $html .= '</head>' . "\n";
        $html .= '<body>' . "\n";

        $html .= '<table>' . "\n";
        // Metadata Title Block
        $html .= '<tr><td colspan="' . $colspan . '" class="title" style="border:none;">' . htmlspecialchars($this->systemName) . '</td></tr>' . "\n";
        $html .= '<tr><td colspan="' . $colspan . '" class="subtitle" style="border:none;">' . htmlspecialchars($this->title) . '</td></tr>' . "\n";
        $html .= '<tr><td colspan="' . $colspan . '" class="meta" style="border:none;">Periode: ' . htmlspecialchars($this->period) . ' &bull; Tanggal Generate: ' . htmlspecialchars($this->generatedAt) . '</td></tr>' . "\n";
        $html .= '<tr><td colspan="' . $colspan . '" style="border:none;"></td></tr>' . "\n";

        // Column Headers
        if (!empty($this->headers)) {
            $html .= '<tr>' . "\n";
            foreach ($this->headers as $h) {
                $html .= '<th>' . htmlspecialchars((string)$h) . '</th>' . "\n";
            }
            $html .= '</tr>' . "\n";
        }

        // Data Rows
        if (!empty($this->rows)) {
            foreach ($this->rows as $rIdx => $row) {
                $class = ($rIdx % 2 === 1) ? ' class="zebra"' : '';
                $html .= '<tr' . $class . '>' . "\n";
                foreach ($row as $cell) {
                    $cellStr = (string)$cell;
                    $isNum = is_numeric(str_replace(['.', ',', 'Rp', ' ', '%', '+', '-'], '', $cellStr));
                    $align = $isNum ? ' style="text-align: right;"' : '';
                    $html .= '<td' . $align . '>' . htmlspecialchars($cellStr) . '</td>' . "\n";
                }
                $html .= '</tr>' . "\n";
            }
        } else {
            $html .= '<tr><td colspan="' . $colspan . '" class="text-center" style="color: #888;">(Tidak ada data laporan)</td></tr>' . "\n";
        }

        // Total Row
        if (!empty($this->totalRow)) {
            $html .= '<tr class="total">' . "\n";
            foreach ($this->totalRow as $cell) {
                $cellStr = (string)$cell;
                $isNum = is_numeric(str_replace(['.', ',', 'Rp', ' ', '%', '+', '-'], '', $cellStr));
                $align = $isNum ? ' style="text-align: right;"' : '';
                $html .= '<td' . $align . '><strong>' . htmlspecialchars($cellStr) . '</strong></td>' . "\n";
            }
            $html .= '</tr>' . "\n";
        }

        $html .= '</table>' . "\n";
        $html .= '</body>' . "\n";
        $html .= '</html>' . "\n";

        return $html;
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

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $content;
        exit;
    }
}
