<?php

namespace Erp\Bundle\ReportBundle\Controller;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProjectBoqExcelReportHelper
{
    const NUMBER_FORMAT = '#,##0.00_-;[Red]-#,##0.00_-;??"-"??_-;[Green]@_-';

    public function reportExcel(
        array $data,
        bool $withFormular,
        ?string &$fileName,
        bool $withoutValue = false
    ) {
        $extendHeader = null;
        $extendName = null;
        if (!$withoutValue) {
            $extendHeader = ' โดย ใบขอซื้อ (PJ-Budget by PU)';
            $extendName = 'PU';
        } else {
            $extendHeader = '';
            $extendName = 'WOV';
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // START: write data
        $fieldMap = [
            'budget' => 'ที่มี',
            'cost' => 'ใช้ไป',
            'remain' => 'คงเหลือ',
        ];
        $costStartColumn = 3;
        $row = 1;
        foreach ($data as $item) {
            $itemStartRow = $row;
            $sheet->setCellValue("A{$row}", "รายงานงบประมาณโครงการ{$extendHeader}");
            $row++;
            $labelRow = $row;
            $sheet->setCellValue("A{$row}", 'โครงการ : ');
            $sheet->setCellValue("B{$row}", "[{$item['projectCode']}] {$item['projectName']}");
            $row++;
            $sheet->setCellValue("A{$row}", 'งบประมาณ : ');
            $sheet->setCellValue("B{$row}", $item['name']);
            $row++;

            $labelStyle = $sheet->getStyleByColumnAndRow(1, $labelRow, 1, $row - 1);
            $labelStyle->getFont()
                ->setBold(true);
            $labelStyle->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $row += 1;

            $sheet->mergeCells("A{$row}:A" . ($row + 1));
            $sheet->setCellValue("A{$row}", 'สำดับ');
            $sheet->mergeCells("B{$row}:B" . ($row + 1));
            $sheet->setCellValue("B{$row}", 'รายการ');
            $column = $costStartColumn;
            $itemEndColumn = $column - 1;
            $totalFields = [];
            foreach (array_keys($fieldMap) as $field) {
                $totalFields[$field] = [];
            }

            foreach ($item['cost']['columns'] as $costColumn) {
                $sheet->mergeCellsByColumnAndRow($column, $row, $column + count($fieldMap) - 1, $row);
                $sheet->setCellValueByColumnAndRow($column, $row, ($costColumn['name'] === 'total') ? 'รวม' : $costColumn['name']);
                foreach (array_keys($fieldMap) as $field) {
                    $sheet->setCellValueByColumnAndRow($column, $row + 1, $fieldMap[$field]);
                    if ($costColumn['name'] !== 'total') {
                        $columnName = Coordinate::stringFromColumnIndex($column);
                        $totalFields[$field][] = "{$columnName}:{$columnName}";
                    }
                    $column++;
                }
                $itemEndColumn = $column - 1;
            }

            $sheet->mergeCellsByColumnAndRow(1, $itemStartRow, $itemEndColumn, $itemStartRow);
            $titleStyle = $sheet->getStyleByColumnAndRow(1, $itemStartRow, $itemEndColumn, $itemStartRow);
            $titleStyle->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $titleStyle->getFont()
                ->setSize(16)
                ->setBold(true);

            $headerStyle = $sheet->getStyleByColumnAndRow(1, $row, $itemEndColumn, $row + 1);
            $headerStyle->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $headerStyle->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $headerStyle->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('DCDCDC');
            $row += 2;

            $itemBoqStartRow = $row;
            $totalBoq = null;
            foreach ($item['cost']['data'] as $boq) {
                if ($boq['number'] === '') {
                    $totalBoq = $boq;
                    continue;
                }

                $indent = substr_count($boq['number'], '.');
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B{$row}")->getAlignment()->setIndent($indent);
                $sheet->setCellValueExplicit("A{$row}", $boq['number'], DataType::TYPE_STRING);
                $sheet->setCellValue("B{$row}", $boq['name']);
                $column = $costStartColumn;
                foreach ($item['cost']['columns'] as $i => $costColumn) {
                    if (!$boq['isTotal']) {
                        foreach (array_keys($fieldMap) as $field) {
                            if ($withFormular && $costColumn['name'] === 'total') {
                                $sheet->setCellValueByColumnAndRow(
                                    $column,
                                    $row,
                                    "=SUM((" . implode(',', $totalFields[$field]) . ") {$row}:{$row})"
                                );
                            } else {
                                $sheet->setCellValueByColumnAndRow($column, $row, $boq['costs'][$i][$field]);
                            }
                            $column++;
                        }
                    }
                }
                $row++;
            }

            if (!$withoutValue) {
                $sheet->mergeCells("A{$row}:B{$row}");
                $sheet->setCellValue("A{$row}", 'รวม');
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $column = $costStartColumn;
                foreach ($item['cost']['columns'] as $i => $costColumn) {
                    foreach (array_keys($fieldMap) as $field) {
                        if ($withFormular) {
                            if ($costColumn['name'] === 'total') {
                                $sheet->setCellValueByColumnAndRow(
                                    $column,
                                    $row,
                                    "=SUM((" . implode(',', $totalFields[$field]) . ") {$row}:{$row})"
                                );
                            } else {
                                $itemBoqEndRow = $row - 1;
                                $columnName = Coordinate::stringFromColumnIndex($column);
                                $sheet->setCellValueByColumnAndRow(
                                    $column,
                                    $row,
                                    "=SUM({$itemBoqStartRow}:{$itemBoqEndRow} {$columnName}:{$columnName})"
                                );
                            }
                        } else {
                            $sheet->setCellValueByColumnAndRow($column, $row, $totalBoq['costs'][$i][$field]);
                        }
                        $column++;
                    }
                }
                $row++;
            }

            $itemDataStyle = $sheet->getStyleByColumnAndRow(1, $itemBoqStartRow, $itemEndColumn, $row - 1);
            $itemDataStyle->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            if (!$withoutValue) {
                $itemBoqStyle = $sheet->getStyleByColumnAndRow($costStartColumn, $itemBoqStartRow, $itemEndColumn, $row - 1);
                $itemBoqStyle->getNumberFormat()
                    ->setFormatCode(self::NUMBER_FORMAT);

                $itemFooterStyle = $sheet->getStyleByColumnAndRow(1, $row - 1, $itemEndColumn, $row - 1);
                $itemFooterStyle->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('DCDCDC');
                $itemFooterStyle->getFont()
                    ->setBold(true);
            }
        }

        // END: write data

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $fileName = "RP-MT-PJ-Budget-{$extendName}_{$item['projectCode']}-{$item['name']}_" . date('Ymd_His', time()) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return $tempFile;
    }
}
