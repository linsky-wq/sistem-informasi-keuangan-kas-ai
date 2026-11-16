<?php
/**
 * PDF Exporter Helper
 * Pure PHP Standards-Compliant PDF 1.4 Document Generator
 * Zero external dependencies required.
 */

namespace App\Helpers;

class PdfExporter {
    protected string $title;
    protected string $systemName;
    protected string $period;
    protected string $generatedAt;
    protected string $orientation; // 'P' (Portrait) or 'L' (Landscape)

    protected float $pageWidth;
    protected float $pageHeight;
    protected float $marginLeft = 36.0;   // 0.5 inch
    protected float $marginRight = 36.0;
    protected float $marginTop = 36.0;
    protected float $marginBottom = 36.0;

    protected array $headers = [];
    protected array $colWidths = [];
    protected array $colAligns = [];
    protected array $rows = [];
    protected ?array $totalRow = null;

    /**
     * Constructor
     */
    public function __construct(
        string $title,
        string $period = 'Semua Periode',
        string $orientation = 'P',
        ?string $systemName = null
    ) {
        $this->title = $title;
        $this->period = $period;
        $this->orientation = strtoupper($orientation) === 'L' ? 'L' : 'P';
        $this->systemName = $systemName ?? (defined('APP_NAME') ? APP_NAME : 'Sistem Keuangan Kas');
        $this->generatedAt = date('d F Y, H:i') . ' WIB';

        if ($this->orientation === 'L') {
            $this->pageWidth = 841.89;
            $this->pageHeight = 595.28;
        } else {
            $this->pageWidth = 595.28;
            $this->pageHeight = 841.89;
        }
    }

    /**
     * Set table headers and relative widths
     */
    public function setTableHeaders(array $headers, array $relativeWidths = [], array $alignments = []): self {
        $this->headers = $headers;
        $numCols = count($headers);

        $usableWidth = $this->pageWidth - $this->marginLeft - $this->marginRight;

        if (!empty($relativeWidths) && count($relativeWidths) === $numCols) {
            $totalRel = array_sum($relativeWidths);
            $this->colWidths = array_map(fn($w) => ($w / $totalRel) * $usableWidth, $relativeWidths);
        } else {
            $defaultWidth = $usableWidth / max(1, $numCols);
            $this->colWidths = array_fill(0, $numCols, $defaultWidth);
        }

        if (!empty($alignments) && count($alignments) === $numCols) {
            $this->colAligns = $alignments;
        } else {
            $this->colAligns = array_fill(0, $numCols, 'L');
        }

        return $this;
    }

    /**
     * Add data rows
     */
    public function addRow(array $row): self {
        $this->rows[] = $row;
        return $this;
    }

    /**
     * Add multiple rows
     */
    public function addRows(array $rows): self {
        foreach ($rows as $r) {
            $this->addRow($r);
        }
        return $this;
    }

    /**
     * Set total / summary row
     */
    public function setTotalRow(array $totalRow): self {
        $this->totalRow = $totalRow;
        return $this;
    }

    /**
     * Escape strings for PDF literal syntax
     */
    protected function escapePdf(string $str): string {
        // Convert UTF-8 to ASCII/WinAnsi friendly characters
        $str = str_replace(['•', '–', '—', '“', '”', '’', '‘'], ['-', '-', '-', '"', '"', "'", "'"], $str);
        $str = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $str) ?: $str;
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace('(', '\\(', $str);
        $str = str_replace(')', '\\)', $str);
        return $str;
    }

    /**
     * Generate the complete PDF binary string
     */
    public function output(): string {
        $usableWidth = $this->pageWidth - $this->marginLeft - $this->marginRight;
        $rowHeight = 18.0;
        $headerHeight = 20.0;
        $docTopY = $this->pageHeight - $this->marginTop;

        $pagesStream = [];
        $currentPageStream = '';
        $currentY = $docTopY;

        // Helper to draw document title and header on a page
        $drawPageDocHeader = function(int $pageNum) use (&$currentPageStream, &$currentY, $docTopY, $usableWidth): void {
            $currentY = $docTopY;

            // System Name
            $sysEsc = $this->escapePdf($this->systemName);
            $currentPageStream .= "BT /F2 14 Tf 0 0 0 rg {$this->marginLeft} {$currentY} Td ({$sysEsc}) Tj ET\n";
            $currentY -= 16;

            // Report Title
            $titleEsc = $this->escapePdf(strtoupper($this->title));
            $currentPageStream .= "BT /F2 11 Tf 0.1 0.3 0.7 rg {$this->marginLeft} {$currentY} Td ({$titleEsc}) Tj ET\n";
            $currentY -= 14;

            // Metadata: Periode & Tanggal Generate
            $metaEsc = $this->escapePdf("Periode: {$this->period}  |  Tanggal Generate: {$this->generatedAt}");
            $currentPageStream .= "BT /F1 8 Tf 0.4 0.4 0.4 rg {$this->marginLeft} {$currentY} Td ({$metaEsc}) Tj ET\n";
            $currentY -= 8;

            // Horizontal Rule
            $lineY = $currentY;
            $currentPageStream .= "0.8 0.8 0.8 RG 1 w {$this->marginLeft} {$lineY} m " . ($this->marginLeft + $usableWidth) . " {$lineY} l S\n";
            $currentY -= 14;
        };

        // Helper to draw table column headers
        $drawTableColHeaders = function() use (&$currentPageStream, &$currentY, $usableWidth, $headerHeight): void {
            $tableTop = $currentY;
            $tableBottom = $currentY - $headerHeight;

            // Header Background
            $currentPageStream .= "0.92 0.94 0.97 rg {$this->marginLeft} {$tableBottom} {$usableWidth} {$headerHeight} re f\n";
            // Header Border
            $currentPageStream .= "0.75 0.78 0.82 RG 0.75 w {$this->marginLeft} {$tableBottom} {$usableWidth} {$headerHeight} re S\n";

            // Header Labels
            $curX = $this->marginLeft;
            foreach ($this->headers as $idx => $hdr) {
                $w = $this->colWidths[$idx];
                $align = $this->colAligns[$idx] ?? 'L';
                $textEsc = $this->escapePdf((string)$hdr);

                $textX = $curX + 5;
                if ($align === 'R') {
                    $textX = $curX + $w - 6 - (strlen((string)$hdr) * 5.0);
                    $textX = max($curX + 2, $textX);
                } elseif ($align === 'C') {
                    $textX = $curX + ($w / 2) - (strlen((string)$hdr) * 2.5);
                    $textX = max($curX + 2, $textX);
                }

                $textY = $tableBottom + 5.5;
                $currentPageStream .= "BT /F2 8.5 Tf 0.15 0.2 0.3 rg {$textX} {$textY} Td ({$textEsc}) Tj ET\n";

                // Vertical column separator
                if ($idx > 0) {
                    $currentPageStream .= "0.8 0.82 0.85 RG 0.5 w {$curX} {$tableBottom} m {$curX} {$tableTop} l S\n";
                }
                $curX += $w;
            }

            $currentY -= $headerHeight;
        };

        // Initialize First Page
        $drawPageDocHeader(1);
        $drawTableColHeaders();

        // If no rows, print empty state
        if (empty($this->rows)) {
            $emptyY = $currentY - 20;
            $emptyText = $this->escapePdf("Tidak ada data laporan yang tercatat pada kriteria filter ini.");
            $currentPageStream .= "BT /F1 9 Tf 0.5 0.5 0.5 rg " . ($this->marginLeft + 15) . " {$emptyY} Td ({$emptyText}) Tj ET\n";
            $currentY -= 35;
        } else {
            // Render Data Rows
            foreach ($this->rows as $rIdx => $row) {
                // Page overflow check
                if ($currentY - $rowHeight < ($this->marginBottom + 30)) {
                    // Push current page
                    $pagesStream[] = $currentPageStream;
                    $currentPageStream = '';
                    $pageNum = count($pagesStream) + 1;

                    // New Page
                    $drawPageDocHeader($pageNum);
                    $drawTableColHeaders();
                }

                $rowBottom = $currentY - $rowHeight;

                // Alternating zebra row background
                if ($rIdx % 2 === 1) {
                    $currentPageStream .= "0.98 0.98 0.99 rg {$this->marginLeft} {$rowBottom} {$usableWidth} {$rowHeight} re f\n";
                }

                // Row border bottom
                $currentPageStream .= "0.88 0.90 0.92 RG 0.5 w {$this->marginLeft} {$rowBottom} m " . ($this->marginLeft + $usableWidth) . " {$rowBottom} l S\n";

                // Cell values
                $curX = $this->marginLeft;
                foreach ($row as $colIdx => $val) {
                    if ($colIdx >= count($this->colWidths)) break;
                    $w = $this->colWidths[$colIdx];
                    $align = $this->colAligns[$colIdx] ?? 'L';
                    
                    $valStr = (string)$val;
                    // Truncate if excessively long
                    if (strlen($valStr) > 60) {
                        $valStr = substr($valStr, 0, 57) . '...';
                    }
                    $textEsc = $this->escapePdf($valStr);

                    $textX = $curX + 5;
                    if ($align === 'R') {
                        $textX = $curX + $w - 6 - (strlen($valStr) * 4.6);
                        $textX = max($curX + 2, $textX);
                    } elseif ($align === 'C') {
                        $textX = $curX + ($w / 2) - (strlen($valStr) * 2.3);
                        $textX = max($curX + 2, $textX);
                    }

                    $textY = $rowBottom + 5;
                    $currentPageStream .= "BT /F1 8 Tf 0.1 0.1 0.1 rg {$textX} {$textY} Td ({$textEsc}) Tj ET\n";

                    $curX += $w;
                }

                $currentY -= $rowHeight;
            }
        }

        // Total Row (if defined)
        if (!empty($this->totalRow)) {
            if ($currentY - 22 < ($this->marginBottom + 30)) {
                $pagesStream[] = $currentPageStream;
                $currentPageStream = '';
                $pageNum = count($pagesStream) + 1;
                $drawPageDocHeader($pageNum);
                $drawTableColHeaders();
            }

            $totalH = 20.0;
            $totalBottom = $currentY - $totalH;

            // Total background
            $currentPageStream .= "0.93 0.95 0.98 rg {$this->marginLeft} {$totalBottom} {$usableWidth} {$totalH} re f\n";
            // Double border effect
            $currentPageStream .= "0.6 0.65 0.72 RG 1 w {$this->marginLeft} {$currentY} m " . ($this->marginLeft + $usableWidth) . " {$currentY} l S\n";
            $currentPageStream .= "0.6 0.65 0.72 RG 1.2 w {$this->marginLeft} {$totalBottom} m " . ($this->marginLeft + $usableWidth) . " {$totalBottom} l S\n";

            $curX = $this->marginLeft;
            foreach ($this->totalRow as $colIdx => $val) {
                if ($colIdx >= count($this->colWidths)) break;
                $w = $this->colWidths[$colIdx];
                $align = $this->colAligns[$colIdx] ?? 'L';
                $valStr = (string)$val;
                $textEsc = $this->escapePdf($valStr);

                $textX = $curX + 5;
                if ($align === 'R') {
                    $textX = $curX + $w - 6 - (strlen($valStr) * 5.0);
                    $textX = max($curX + 2, $textX);
                } elseif ($align === 'C') {
                    $textX = $curX + ($w / 2) - (strlen($valStr) * 2.5);
                    $textX = max($curX + 2, $textX);
                }

                $textY = $totalBottom + 6;
                $currentPageStream .= "BT /F2 8.5 Tf 0.05 0.1 0.2 rg {$textX} {$textY} Td ({$textEsc}) Tj ET\n";
                $curX += $w;
            }

            $currentY -= $totalH;
        }

        // Push last page stream
        $pagesStream[] = $currentPageStream;
        $totalPages = count($pagesStream);

        // Add Footer to each page (Page X of Y)
        foreach ($pagesStream as $pIdx => &$stream) {
            $pNum = $pIdx + 1;
            $footerText = $this->escapePdf("Halaman {$pNum} dari {$totalPages}  |  Dokumen Resmi " . $this->systemName);
            $footerY = $this->marginBottom - 10;
            $stream .= "BT /F1 7.5 Tf 0.5 0.5 0.5 rg {$this->marginLeft} {$footerY} Td ({$footerText}) Tj ET\n";
        }
        unset($stream);

        // Assemble Final PDF Objects
        return $this->compilePdf($pagesStream);
    }

    /**
     * Compile PDF objects, xref table, and trailer
     */
    protected function compilePdf(array $pagesStream): string {
        $numPages = count($pagesStream);
        $objects = [];

        // 1. Catalog
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";

        // 2. Pages
        $kids = [];
        for ($i = 0; $i < $numPages; $i++) {
            $pageObjId = 3 + ($i * 2);
            $kids[] = "$pageObjId 0 R";
        }
        $kidsStr = implode(' ', $kids);
        $objects[2] = "<< /Type /Pages /Kids [{$kidsStr}] /Count {$numPages} >>";

        // Font Objects
        $fontF1Id = 3 + ($numPages * 2);
        $fontF2Id = $fontF1Id + 1;

        $objects[$fontF1Id] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[$fontF2Id] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        // Generate Page and Content Objects
        for ($i = 0; $i < $numPages; $i++) {
            $pageObjId = 3 + ($i * 2);
            $contentObjId = $pageObjId + 1;
            $streamContent = $pagesStream[$i];
            $streamLen = strlen($streamContent);

            $objects[$pageObjId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->pageWidth} {$this->pageHeight}] /Contents {$contentObjId} 0 R /Resources << /Font << /F1 {$fontF1Id} 0 R /F2 {$fontF2Id} 0 R >> >> >>";
            $objects[$contentObjId] = "<< /Length {$streamLen} >>\nstream\n{$streamContent}\nendstream";
        }

        // Build File Content with precise byte offsets
        $out = "%PDF-1.4\n";
        $offsets = [0 => 0];

        ksort($objects);
        foreach ($objects as $id => $objData) {
            $offsets[$id] = strlen($out);
            $out .= "{$id} 0 obj\n{$objData}\nendobj\n";
        }

        $xrefOffset = strlen($out);
        $totalObjs = count($objects) + 1;

        $out .= "xref\n0 {$totalObjs}\n";
        $out .= "0000000000 65535 f \n";

        for ($i = 1; $i < $totalObjs; $i++) {
            $offset = $offsets[$i] ?? 0;
            $out .= sprintf("%010d 00000 n \n", $offset);
        }

        $out .= "trailer\n<< /Size {$totalObjs} /Root 1 0 R >>\n";
        $out .= "startxref\n{$xrefOffset}\n%%EOF";

        return $out;
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

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $content;
        exit;
    }

    /**
     * Send inline view response
     */
    public function stream(string $filename): void {
        $content = $this->output();
        if (headers_sent()) {
            echo $content;
            exit;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $content;
        exit;
    }
}
