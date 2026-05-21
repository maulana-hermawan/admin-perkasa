<?php
/**
 * SimpleXLSX - Parser Excel (.xlsx) untuk Perkasa Mulia CAT
 * Mendukung: sharedStrings, cell references (A1, B2, dst.), kolom tidak berurutan
 */

if (!class_exists('SimpleXLSX')) {

class SimpleXLSX {

    public $rows = [];

    /**
     * Parse file .xlsx dan kembalikan instance SimpleXLSX
     * @param string $filename Path ke file .xlsx
     * @return SimpleXLSX|false
     */
    public static function parse($filename) {
        $xlsx = new self();

        if (!file_exists($filename)) {
            error_log("SimpleXLSX: File tidak ditemukan: " . $filename);
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($filename) !== TRUE) {
            error_log("SimpleXLSX: Gagal membuka ZIP: " . $filename);
            return false;
        }

        // 1. Baca Shared Strings (teks yang di-share antar sel)
        $sharedStrings = [];
        $ssIndex = $zip->locateName('xl/sharedStrings.xml');
        if ($ssIndex !== false) {
            $ssData = $zip->getFromIndex($ssIndex);
            if ($ssData) {
                $ssXml = @simplexml_load_string($ssData, 'SimpleXMLElement', LIBXML_NOCDATA);
                if ($ssXml) {
                    foreach ($ssXml->si as $si) {
                        // Ambil teks — bisa berupa <t> langsung atau <r><t>
                        $text = '';
                        if (isset($si->t)) {
                            $text = (string)$si->t;
                        } elseif (isset($si->r)) {
                            foreach ($si->r as $r) {
                                if (isset($r->t)) {
                                    $text .= (string)$r->t;
                                }
                            }
                        }
                        $sharedStrings[] = $text;
                    }
                }
            }
        }

        // 2. Baca Sheet pertama
        $sheetIndex = $zip->locateName('xl/worksheets/sheet1.xml');
        if ($sheetIndex === false) {
            // Coba nama alternatif
            $sheetIndex = $zip->locateName('xl/worksheets/Sheet1.xml');
        }

        if ($sheetIndex !== false) {
            $sheetData = $zip->getFromIndex($sheetIndex);
            if ($sheetData) {
                $sheetXml = @simplexml_load_string($sheetData, 'SimpleXMLElement', LIBXML_NOCDATA);
                if ($sheetXml && isset($sheetXml->sheetData)) {
                    foreach ($sheetXml->sheetData->row as $row) {
                        $rowData     = [];
                        $maxColIndex = -1;
                        $cellMap     = []; // kolom_index => nilai

                        foreach ($row->c as $cell) {
                            // Dapatkan koordinat sel (misal: "A1", "B2", "AA3")
                            $cellRef = (string)$cell['r'];
                            $colLetter = preg_replace('/[0-9]/', '', $cellRef);
                            $colIndex  = self::colLetterToIndex($colLetter);

                            // Dapatkan nilai sel
                            $cellType  = isset($cell['t']) ? (string)$cell['t'] : '';
                            $cellValue = isset($cell->v) ? (string)$cell->v : '';

                            if ($cellType === 's') {
                                // Shared string
                                $strIdx = (int)$cellValue;
                                $cellValue = isset($sharedStrings[$strIdx]) ? $sharedStrings[$strIdx] : '';
                            } elseif ($cellType === 'b') {
                                // Boolean
                                $cellValue = $cellValue ? 'TRUE' : 'FALSE';
                            }
                            // Tipe 'n' (number), '' (default), dll — ambil nilai apa adanya

                            $cellMap[$colIndex] = $cellValue;
                            if ($colIndex > $maxColIndex) {
                                $maxColIndex = $colIndex;
                            }
                        }

                        // Bangun array baris yang berurutan (0,1,2,3,...)
                        // Sel yang kosong diisi string kosong
                        if ($maxColIndex >= 0) {
                            for ($i = 0; $i <= $maxColIndex; $i++) {
                                $rowData[] = isset($cellMap[$i]) ? $cellMap[$i] : '';
                            }
                            $xlsx->rows[] = $rowData;
                        }
                    }
                }
            }
        }

        $zip->close();
        return $xlsx;
    }

    /**
     * Konversi huruf kolom Excel ke index (0-based)
     * A=0, B=1, ..., Z=25, AA=26, AB=27, dst.
     */
    private static function colLetterToIndex($colLetter) {
        $colLetter = strtoupper(trim($colLetter));
        $index     = 0;
        $len       = strlen($colLetter);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($colLetter[$i]) - ord('A') + 1);
        }
        return $index - 1; // 0-based
    }

    /**
     * Kembalikan semua baris sebagai array 2D
     */
    public function rows() {
        return $this->rows;
    }
}

} // end if (!class_exists)
