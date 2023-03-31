<?php

namespace Erp\Bundle\ReportBundle\Controller;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProjectContractExcelReportHelper
{
    const NUMBER_FORMAT = '#,##0.00_-;[Red]-#,##0.00_-;??"-"??_-;[Green]@_-';

    const TITLE_STYLE = [
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    const DESCRIPTION_HEADER_STYLE = [
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_RIGHT,
        ],
    ];

    const ALL_STYLE = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
    ];

    const HEADER_FOOTER_STYLE = [
        'font' => [
            'bold' => true,
        ],
        'fill' => [
            'fillType' => FILL::FILL_SOLID,
            'startColor' => [
                'rgb' => 'DCDCDC'
            ],
        ],
    ];

    const HEADER_STYLE = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    public function reportExcel(
        array $data,
        string $title,
        string $abbName,
        ?string &$fileName
    ) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $projectCode = 'XXXX';
        $budgetName = 'YYYY';

        $row = 1;

        $sheet->setCellValue("A{$row}", $title);
        $row++;

        $startDescriptionRow = $row;

        $sheet->setCellValue("A{$row}", 'โครงการ : ');
        if (!empty($data[0])) {
            $projectCode = $data[0]['projectCode'];
            $sheet->setCellValue("B{$row}", "[{$data[0]['projectCode']}] {$data[0]['projectName']}");
        }
        $row++;

        $sheet->setCellValue("A{$row}", 'Budget :');
        if (!empty($data[0])) {
            $budgetName = $data[0]['budgetName'];
            $sheet->setCellValue("B{$row}", $data[0]['budgetName']);
        }
        $row++;

        $endDescriptionRow = $row - 1;

        $sheet->getStyleByColumnAndRow(1, $startDescriptionRow, 1, $endDescriptionRow)->applyFromArray(self::DESCRIPTION_HEADER_STYLE);

        $row++;

        $startAllCol = 1;

        $startHeaderRow = $row;

        $col = $startAllCol;

        $sheet->setCellValueByColumnAndRow($col++, $row, 'สำดับ');
        $sheet->setCellValueByColumnAndRow($col++, $row, 'เลขที่เอกสาร');
        $sheet->setCellValueByColumnAndRow($col++, $row, 'สถานะ');
        $paymentDateCol = $col;
        $sheet->setCellValueByColumnAndRow($col++, $row, 'วันที่รับเงิน');
        $contractCol = $col;
        $sheet->setCellValueByColumnAndRow($col++, $row, 'มูลค่าการตั้งเบิก');
        $vatCostCol = $col;
        $sheet->setCellValueByColumnAndRow($col++, $row, 'VAT');
        $excludeVatCol = $col;
        $sheet->setCellValueByColumnAndRow($col++, $row, 'ไม่รวมVAT');
        $taxCostCol = $col;
        $sheet->setCellValueByColumnAndRow($col++, $row, 'TAX');
        $payTotalCol = $col;
        $sheet->setCellValueByColumnAndRow($col++, $row, 'รวมชำระ');
        $sheet->setCellValueByColumnAndRow($col++, $row, '%ประกันผลงาน');
        $retentionCostCol = $col;
        $sheet->setCellValueByColumnAndRow($col++, $row, 'มูลค่าประกันผลงาน');
        $retentionPayTotalCol = $col;
        $sheet->setCellValueByColumnAndRow($col++, $row, 'มูลค่ารวมชำระสุทธิ');

        $endAllCol = $col - 1;
        $endHeaderRow = $row;

        $row++;

        $sheet->mergeCellsByColumnAndRow(1, 1, $endAllCol, 1);
        $sheet->getStyleByColumnAndRow(1, 1, $endAllCol, 1)->applyFromArray(self::TITLE_STYLE);

        $startDataRow = $row;

        foreach ($data as $i => $item) {
            $col = $startAllCol;

            $sheet->setCellValueByColumnAndRow($col++, $row, $i + 1);
            $sheet->setCellValueByColumnAndRow($col++, $row, $item['docCode']);
            $sheet->setCellValueByColumnAndRow($col++, $row, ($item['approved']) ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValueByColumnAndRow($col++, $row, Date::PHPToExcel($item['paymentDate']));
            $sheet->setCellValueByColumnAndRow($col++, $row, $item['contract']);
            $sheet->setCellValueByColumnAndRow($col++, $row, $item['vatCost']);
            $sheet->setCellValueByColumnAndRow($col++, $row, $item['excludeVat']);
            $sheet->setCellValueByColumnAndRow($col++, $row, $item['taxCost']);
            $sheet->setCellValueByColumnAndRow($col++, $row, $item['payTotal']);
            $sheet->setCellValueByColumnAndRow($col++, $row, $item['retention']);
            $sheet->setCellValueByColumnAndRow($col++, $row, $item['retentionCost']);
            $sheet->setCellValueByColumnAndRow($col++, $row, $item['retentionPayTotal']);

            $row++;
        }

        $endDataRow = $row - 1;

        $startFooterRow = $row;

        foreach ([
            $contractCol,
            $vatCostCol,
            $excludeVatCol,
            $taxCostCol,
            $payTotalCol,
            $retentionCostCol,
            $retentionPayTotalCol,
        ] as $col) {
            $range = 0;
            if ($startDataRow <= $endDataRow) {
                $colString = Coordinate::stringFromColumnIndex($col);
                $range = "{$colString}\${$startDataRow}:{$colString}{$endDataRow}";
            }
            $sheet->setCellValueByColumnAndRow($col, $row, "=SUM({$range})");
        }

        $endFooterRow = $row;

        $row++;

        $allStyle = $sheet->getStyleByColumnAndRow($startAllCol, $startHeaderRow, $endAllCol, $endFooterRow);
        $allStyle->applyFromArray(self::ALL_STYLE);

        $headerStyle = $sheet->getStyleByColumnAndRow($startAllCol, $startHeaderRow, $endAllCol, $endHeaderRow);
        $headerStyle->applyFromArray(self::HEADER_FOOTER_STYLE);
        $headerStyle->applyFromArray(self::HEADER_STYLE);

        $footerStyle = $sheet->getStyleByColumnAndRow($startAllCol, $startFooterRow, $endAllCol, $endFooterRow);
        $footerStyle->applyFromArray(self::HEADER_FOOTER_STYLE);

        $numberStyle = $sheet->getStyleByColumnAndRow($contractCol, $startDataRow, $retentionPayTotalCol, $endFooterRow);
        $numberStyle->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);

        if ($startDataRow <= $endDataRow) {
            $paymentDateStyle = $sheet->getStyleByColumnAndRow($paymentDateCol, $startDataRow, $paymentDateCol, $endDataRow);
            $paymentDateStyle->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        }

        // Write to file
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $fileName = "RP-MT-PJ-Contract-{$abbName}_{$projectCode}-{$budgetName}_" . date('Ymd_His', time()) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return $tempFile;
    }
}
