<?php

class SimpleXLSX {
    private $sheets;

    public function __construct($filename) {
        if (!is_readable($filename)) {
            throw new Exception("Fichier illisible: $filename");
        }

        $zip = new ZipArchive();
        if (!$zip->open($filename)) {
            throw new Exception("Impossible d'ouvrir le fichier ZIP: $filename");
        }

        $xml = $zip->getFromName('xl/sharedStrings.xml');
        $sharedStrings = [];
        if ($xml) {
            $sxe = simplexml_load_string($xml);
            foreach ($sxe->si as $val) {
                if (isset($val->t)) {
                    $sharedStrings[] = (string) $val->t;
                } elseif (isset($val->r)) {
                    $text = '';
                    foreach ($val->r as $run) {
                        $text .= (string) $run->t;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $this->sheets = [];
        $i = 1;
        while ($xml = $zip->getFromName("xl/worksheets/sheet$i.xml")) {
            $rows = [];
            $sxe = simplexml_load_string($xml);
            foreach ($sxe->sheetData->row as $row) {
                $rowData = [];
                foreach ($row->c as $c) {
                    $v = (string)$c->v;
                    if ($c['t'] == 's') {
                        $rowData[] = $sharedStrings[(int)$v];
                    } else {
                        $rowData[] = $v;
                    }
                }
                $rows[] = $rowData;
            }
            $this->sheets[] = $rows;
            $i++;
        }
    }

    public function sheets() {
        return $this->sheets;
    }

    public function rows($sheetIndex = 0) {
        return isset($this->sheets[$sheetIndex]) ? $this->sheets[$sheetIndex] : [];
    }
}
