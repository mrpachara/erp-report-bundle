<?php

namespace Erp\Bundle\ReportBundle\Controller;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class IncomeFinanceExcelReportHelper
{
    const NUMBER_FORMAT = '#,##0.00_-;[Red]-#,##0.00_-;??"-"??_-;[Green]@_-';

    function docReportExcel(
        array $data,
        array $filterDetail,
        string $docNameEn,
        string $docNameTh,
        string $docAbbr,
        ?string &$fileName
    ): string {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->mergeCells("A1:F1");
        $sheet->mergeCells("B2:F2");
        $sheet->mergeCells("B3:F3");
        $sheet->mergeCells("B4:F4");
        $sheet->mergeCells("B5:F5");
        $sheet->mergeCells("B6:F6");
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:F1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('left');
        $sheet->getStyle('C7:C8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('D7:D8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $sheet->setCellValue('A1', "รายงานเอกสาร{$docNameTh} ({$docAbbr}-DC)");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        // $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        // $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        // $sheet->setCellValue('A8', 'XXXXX : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        // $sheet->setCellValue('B4', (!isset($filterDetail['budgetType']))? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        // $sheet->setCellValue('B6', (!isset($filterDetail['vendor']))? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        // $sheet->setCellValue('B8', (!isset($filterDetail['xxxXxxxx']))? 'xxxxx' : ($filterDetail['xxxXxxxx']? 'XXX' : 'XXX'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:E9');
        $sheet->mergeCells('F9:F10');
        $sheet->getStyle('A9:F10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A9:F10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A9:F10')->getFill()->getStartColor()->setRGB('DCDCDC');
        $sheet->getStyle('A9:F10')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A9:F10')->getAlignment()->setVertical('center');
        $sheet->setCellValue('A9', 'ลำดับ');
        $sheet->setCellValue('B9', 'เอกสาร');
        $sheet->setCellValue('B10', 'เลขที่');
        $sheet->setCellValue('C10', 'สถานะ');
        $sheet->setCellValue('D9', 'โครงการ');
        $sheet->setCellValue('D10', 'รหัส');
        $sheet->setCellValue('E10', 'งบประมาณ');
        $sheet->setCellValue('F9', 'ผู้ต้องการ');

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['requester']);
            $sheet->getStyle("A{$row}:F{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;
        $tableEndRow = $itemEndRow;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:F{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $writer = new Xlsx($spreadsheet);
        $fileName = "RP-DC-IN-{$docAbbr}_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return $tempFile;
    }

    function costReportExcel(
        array $data,
        array $filterDetail,
        string $docNameEn,
        string $docNameTh,
        string $docAbbr,
        ?string &$fileName
    ): string {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->mergeCells("A1:K1");
        $sheet->mergeCells("B2:K2");
        $sheet->mergeCells("B3:K3");
        $sheet->mergeCells("B4:K4");
        $sheet->mergeCells("B5:K5");
        $sheet->mergeCells("B6:K6");
        $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:K1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('left');
        $sheet->getStyle('C7:C8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('D7:D8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $sheet->setCellValue('A1', "รายงานการเงิน{$docNameTh} ({$docAbbr}-FI)");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        // $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        // $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        // $sheet->setCellValue('A8', 'XXXXX : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        // $sheet->setCellValue('B4', (!isset($filterDetail['budgetType']))? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        // $sheet->setCellValue('B6', (!isset($filterDetail['vendor']))? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        // $sheet->setCellValue('B8', (!isset($filterDetail['xxxXxxxx']))? 'xxxxx' : ($filterDetail['xxxXxxxx']? 'XXX' : 'XXX'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:E9');
        $sheet->mergeCells('F9:F10');
        $sheet->mergeCells('G9:K9');
        $sheet->getStyle('A9:K10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A9:K10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A9:K10')->getFill()->getStartColor()->setRGB('DCDCDC');
        $sheet->getStyle('A9:K10')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A9:K10')->getAlignment()->setVertical('center');
        $sheet->setCellValue('A9', 'ลำดับ');
        $sheet->setCellValue('B9', 'เอกสาร');
        $sheet->setCellValue('B10', 'เลขที่');
        $sheet->setCellValue('C10', 'สถานะ');
        $sheet->setCellValue('D9', 'โครงการ');
        $sheet->setCellValue('D10', 'รหัส');
        $sheet->setCellValue('E10', 'งบประมาณ');
        $sheet->setCellValue('F9', 'ผู้ต้องการ');
        $sheet->setCellValue('G9', 'มูลค่า (บาท)');
        $sheet->setCellValue('G10', 'VAT');
        $sheet->setCellValue('H10', 'ไม่รวมVAT');
        $sheet->setCellValue('I10', 'TAX');
        $sheet->setCellValue('J10', 'รวมชำระ');
        $sheet->setCellValue('K10', 'รวมสุทธิ');

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['requester']);
            $sheet->setCellValue('G' . $row, $item['vatCost']);
            $sheet->setCellValue('H' . $row, $item['excludeVat']);
            $sheet->setCellValue('I' . $row, $item['taxCost']);
            $sheet->setCellValue('J' . $row, $item['payTotal']);
            $sheet->setCellValue('K' . $row, $item['docTotal']);
            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;

        foreach (range('G', 'K') as $colName) {
            $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
        }
        $row++;

        $tableEndRow = $row - 1;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:F{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $sheet->getStyle("A{$itemStartRow}:K{$tableEndRow}")->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("G{$itemStartRow}:K{$tableEndRow}")->getNumberFormat()
            ->setFormatCode(self::NUMBER_FORMAT);

        $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:K{$tableEndRow}");
        $itemFooterStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('DCDCDC');
        $itemFooterStyle->getFont()
            ->setBold(true);

        $sheet->getStyle("G{$tableEndRow}:K{$tableEndRow}")->getFont()
            ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        // Create a Temporary file in the system
        $fileName = "RP-FI-IN-{$docAbbr}_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($tempFile);

        return $tempFile;
    }

    function vatReportExcel(
        array $data,
        array $filterDetail,
        string $docNameEn,
        string $docNameTh,
        string $docAbbr,
        ?string &$fileName
    ): string {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->mergeCells("A1:J1");
        $sheet->mergeCells("B2:J2");
        $sheet->mergeCells("B3:J3");
        $sheet->mergeCells("B4:J4");
        $sheet->mergeCells("B5:J5");
        $sheet->mergeCells("B6:J6");
        $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:J1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('left');
        $sheet->getStyle('C7:C8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('D7:D8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $sheet->setCellValue('A1', "รายงานภาษีมูลค่าเพิ่ม โดย {$docNameTh} (Vat by {$docAbbr}-FI)");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        // $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        // $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        $sheet->setCellValue('A8', 'สถานะVAT : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        // $sheet->setCellValue('B4', (!isset($filterDetail['budgetType']))? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        // $sheet->setCellValue('B6', (!isset($filterDetail['vendor']))? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        $sheet->setCellValue('B8', (!isset($filterDetail['vatFactor'])) ? 'ทั้งหมด' : ($filterDetail['vatFactor'] ? 'มีVAT' : 'ไม่มีVAT'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:E9');
        $sheet->mergeCells('F9:F10');
        $sheet->mergeCells('G9:G10');
        $sheet->mergeCells('H9:J9');
        $sheet->getStyle('A9:J10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A9:J10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A9:J10')->getFill()->getStartColor()->setRGB('DCDCDC');
        $sheet->getStyle('A9:J10')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A9:J10')->getAlignment()->setVertical('center');
        $sheet->setCellValue('A9', 'ลำดับ');
        $sheet->setCellValue('B9', 'เอกสาร');
        $sheet->setCellValue('B10', 'เลขที่');
        $sheet->setCellValue('C10', 'สถานะ');
        $sheet->setCellValue('D9', 'โครงการ');
        $sheet->setCellValue('D10', 'รหัส');
        $sheet->setCellValue('E10', 'งบประมาณ');
        $sheet->setCellValue('F9', 'ผู้ต้องการ');
        $sheet->setCellValue('G9', 'สถานะVAT');
        $sheet->setCellValue('H9', 'มูลค่า (บาท)');
        $sheet->setCellValue('H10', 'VAT');
        $sheet->setCellValue('I10', 'ไม่รวมVAT');
        $sheet->setCellValue('J10', 'รวมสุทธิ');

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['requester']);
            $sheet->setCellValue('G' . $row, $item['vatFactor'] ? 'มี' : 'ไม่มี');
            $sheet->setCellValue('H' . $row, $item['vatCost']);
            $sheet->setCellValue('I' . $row, $item['excludeVat']);
            $sheet->setCellValue('J' . $row, $item['docTotal']);
            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;

        foreach (range('H', 'J') as $colName) {
            $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
        }
        $row++;

        $tableEndRow = $row - 1;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:G{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $sheet->getStyle("A{$itemStartRow}:J{$tableEndRow}")->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("H{$itemStartRow}:J{$tableEndRow}")->getNumberFormat()
            ->setFormatCode(self::NUMBER_FORMAT);

        $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:J{$tableEndRow}");
        $itemFooterStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('DCDCDC');
        $itemFooterStyle->getFont()
            ->setBold(true);

        $sheet->getStyle("H{$tableEndRow}:J{$tableEndRow}")->getFont()
            ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        // Create a Temporary file in the system
        $fileName = "RP-FI-IN-{$docAbbr}-Vat_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($tempFile);

        return $tempFile;
    }

    function taxReportExcel(
        array $data,
        array $filterDetail,
        string $docNameEn,
        string $docNameTh,
        string $docAbbr,
        ?string &$fileName
    ): string {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->mergeCells("A1:K1");
        $sheet->mergeCells("B2:K2");
        $sheet->mergeCells("B3:K3");
        $sheet->mergeCells("B4:K4");
        $sheet->mergeCells("B5:K5");
        $sheet->mergeCells("B6:K6");
        $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:K1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('left');
        $sheet->getStyle('C7:C8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('D7:D8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $sheet->setCellValue('A1', "รายงานภาษีหัก ณ ที่จ่าย โดย {$docNameTh} (Tax by {$docAbbr}-FI)");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        // $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        // $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        $sheet->setCellValue('A8', 'สถานะTAX : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        // $sheet->setCellValue('B4', (!isset($filterDetail['budgetType']))? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}"); //
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        // $sheet->setCellValue('B6', (!isset($filterDetail['vendor']))? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}"); //
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        $sheet->setCellValue('B8', (!isset($filterDetail['taxFactor'])) ? 'ทั้งหมด' : ($filterDetail['taxFactor'] ? 'มีTAX' : 'ไม่มีTAX'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:E9');
        $sheet->mergeCells('F9:F10');
        $sheet->mergeCells('G9:H9');
        $sheet->mergeCells('I9:K9');
        $sheet->getStyle('A9:K10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A9:K10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A9:K10')->getFill()->getStartColor()->setRGB('DCDCDC');
        $sheet->getStyle('A9:K10')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A9:K10')->getAlignment()->setVertical('center');
        $sheet->setCellValue('A9', 'ลำดับ');
        $sheet->setCellValue('B9', 'เอกสาร');
        $sheet->setCellValue('B10', 'เลขที่');
        $sheet->setCellValue('C10', 'สถานะ');
        $sheet->setCellValue('D9', 'โครงการ');
        $sheet->setCellValue('D10', 'รหัส');
        $sheet->setCellValue('E10', 'งบประมาณ');
        $sheet->setCellValue('F9', 'ผู้ต้องการ');
        $sheet->setCellValue('G9', 'TAX');
        $sheet->setCellValue('G10', 'สถานะ');
        $sheet->setCellValue('H10', '%');
        $sheet->setCellValue('I9', 'มูลค่า (บาท)');
        $sheet->setCellValue('I10', 'TAX');
        $sheet->setCellValue('J10', 'รวมชำระ');
        $sheet->setCellValue('K10', 'รวมสุทธิ');

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['requester']);
            $sheet->setCellValue('G' . $row, $item['taxFactor'] ? 'มี' : 'ไม่มี');
            $sheet->setCellValue('H' . $row, $item['taxFactor'] * $item['tax']);
            $sheet->setCellValue('I' . $row, $item['taxCost']);
            $sheet->setCellValue('J' . $row, $item['payTotal']);
            $sheet->setCellValue('K' . $row, $item['docTotal']);
            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;

        foreach (range('I', 'K') as $colName) {
            $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
        }
        $row++;

        $tableEndRow = $row - 1;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:H{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $sheet->getStyle("A{$itemStartRow}:K{$tableEndRow}")->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("I{$itemStartRow}:K{$tableEndRow}")->getNumberFormat()
            ->setFormatCode(self::NUMBER_FORMAT);

        $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:K{$tableEndRow}");
        $itemFooterStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('DCDCDC');
        $itemFooterStyle->getFont()
            ->setBold(true);

        $sheet->getStyle("I{$tableEndRow}:K{$tableEndRow}")->getFont()
            ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        // Create a Temporary file in the system
        $fileName = "RP-FI-IN-{$docAbbr}-Tax_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($tempFile);

        return $tempFile;
    }

    function retentionReportExcel(
        array $data,
        array $filterDetail,
        string $docNameEn,
        string $docNameTh,
        string $docAbbr,
        ?string &$fileName
    ): string {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->mergeCells("A1:K1");
        $sheet->mergeCells("B2:K2");
        $sheet->mergeCells("B3:K3");
        $sheet->mergeCells("B4:K4");
        $sheet->mergeCells("B5:K5");
        $sheet->mergeCells("B6:K6");
        $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:K1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('left');
        $sheet->getStyle('C7:C8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('D7:D8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $sheet->setCellValue('A1', "รายงานค่าประกันผลงาน โดย {$docNameTh} (Retention by {$docAbbr}-FI)");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        // $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        // $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        $sheet->setCellValue('A8', 'สถานะประกันผลงาน : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        // $sheet->setCellValue('B4', (!isset($filterDetail['budgetType']))? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}"); //
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        // $sheet->setCellValue('B6', (!isset($filterDetail['vendor']))? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}"); //
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        $sheet->setCellValue('B8', (!isset($filterDetail['retentionFactor'])) ? 'ทั้งหมด' : ($filterDetail['retentionFactor'] ? 'มีประกันผลงาน' : 'ไม่มีประกันผลงาน'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:E9');
        $sheet->mergeCells('F9:F10');
        $sheet->mergeCells('G9:H9');
        $sheet->mergeCells('I9:K9');
        $sheet->getStyle('A9:K10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A9:K10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A9:K10')->getFill()->getStartColor()->setRGB('DCDCDC');
        $sheet->getStyle('A9:K10')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A9:K10')->getAlignment()->setVertical('center');
        $sheet->setCellValue('A9', 'ลำดับ');
        $sheet->setCellValue('B9', 'เอกสาร');
        $sheet->setCellValue('B10', 'เลขที่');
        $sheet->setCellValue('C10', 'สถานะ');
        $sheet->setCellValue('D9', 'โครงการ');
        $sheet->setCellValue('D10', 'รหัส');
        $sheet->setCellValue('E10', 'งบประมาณ');
        $sheet->setCellValue('F9', 'ผู้ต้องการ');
        $sheet->setCellValue('G9', 'ประกันผลงาน');
        $sheet->setCellValue('G10', 'สถานะ');
        $sheet->setCellValue('H10', '%');
        $sheet->setCellValue('I9', 'มูลค่า (บาท)');
        $sheet->setCellValue('I10', 'ประกันผลงาน');
        $sheet->setCellValue('J10', 'รวมชำระ');
        $sheet->setCellValue('K10', 'รวมสุทธิ');

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['requester']);
            $sheet->setCellValue('G' . $row, $item['retentionFactor'] ? 'มี' : 'ไม่มี');
            $sheet->setCellValue('H' . $row, $item['retentionFactor'] * $item['retention']);
            $sheet->setCellValue('I' . $row, $item['retentionCost']);
            $sheet->setCellValue('J' . $row, $item['retentionPayTotal']);
            $sheet->setCellValue('K' . $row, $item['docTotal']);
            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;

        foreach (range('I', 'K') as $colName) {
            $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
        }
        $row++;

        $tableEndRow = $row - 1;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:H{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $sheet->getStyle("A{$itemStartRow}:K{$tableEndRow}")->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("I{$itemStartRow}:K{$tableEndRow}")->getNumberFormat()
            ->setFormatCode(self::NUMBER_FORMAT);

        $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:K{$tableEndRow}");
        $itemFooterStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('DCDCDC');
        $itemFooterStyle->getFont()
            ->setBold(true);

        $sheet->getStyle("I{$tableEndRow}:K{$tableEndRow}")->getFont()
            ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        // Create a Temporary file in the system
        $fileName = "RP-FI-IN-{$docAbbr}-Retention_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($tempFile);

        return $tempFile;
    }

    function payMethodReportExcel(
        array $data,
        array $filterDetail,
        string $docNameEn,
        string $docNameTh,
        string $docAbbr,
        ?string &$fileName
    ): string {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->mergeCells("A1:H1");
        $sheet->mergeCells("B2:H2");
        $sheet->mergeCells("B3:H3");
        $sheet->mergeCells("B4:H4");
        $sheet->mergeCells("B5:H5");
        $sheet->mergeCells("B6:H6");
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:H1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('left');
        $sheet->getStyle('C7:C8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('D7:D8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $sheet->setCellValue('A1', "รายงานกำหนดการรับเงิน โดย {$docNameTh} (Accept by {$docAbbr}-FI)");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        // $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        // $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        // $sheet->setCellValue('A8', 'xxxxx : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        // $sheet->setCellValue('B4', (!isset($filterDetail['budgetType']))? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        // $sheet->setCellValue('B6', (!isset($filterDetail['vendor']))? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        // $sheet->setCellValue('B8', (!isset($filterDetail['xxxXxxx']))? 'ทั้งหมด' : ($filterDetail['xxxXxxx']? 'xxx' : 'xxx'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:E9');
        $sheet->mergeCells('F9:F10');
        $sheet->mergeCells('G9:H9');
        $sheet->getStyle('A9:H10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A9:H10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A9:H10')->getFill()->getStartColor()->setRGB('DCDCDC');
        $sheet->getStyle('A9:H10')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A9:H10')->getAlignment()->setVertical('center');
        $sheet->setCellValue('A9', 'ลำดับ');
        $sheet->setCellValue('B9', 'เอกสาร');
        $sheet->setCellValue('B10', 'เลขที่');
        $sheet->setCellValue('C10', 'สถานะ');
        $sheet->setCellValue('D9', 'โครงการ');
        $sheet->setCellValue('D10', 'รหัส');
        $sheet->setCellValue('E10', 'งบประมาณ');
        $sheet->setCellValue('F9', 'ผู้ต้องการ');
        $sheet->setCellValue('G9', 'กำหนดการรับเงิน');
        $sheet->setCellValue('H10', 'วันที่');
        $sheet->setCellValue('G10', 'มูลค่า (บาท)');

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['requester']);
            $sheet->setCellValue('G' . $row, empty($item['paymentDate']) ? '' : Date::PHPToExcel($item['paymentDate']));
            $sheet->setCellValue('H' . $row, $item['netTotal']);
            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;

        foreach (range('H', 'H') as $colName) {
            $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
        }
        $row++;

        $tableEndRow = $row - 1;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:J{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $sheet->getStyle("A{$itemStartRow}:H{$tableEndRow}")->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("H{$itemStartRow}:H{$tableEndRow}")->getNumberFormat()
            ->setFormatCode(self::NUMBER_FORMAT);

        $sheet->getStyle("G{$itemStartRow}:G{$tableEndRow}")->getNumberFormat()
            ->setFormatCode('DD/MM/YYYY');

        $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:H{$tableEndRow}");
        $itemFooterStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('DCDCDC');
        $itemFooterStyle->getFont()
            ->setBold(true);

        $sheet->getStyle("H{$tableEndRow}:H{$tableEndRow}")->getFont()
            ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        // Create a Temporary file in the system
        $fileName = "RP-FI-IN-{$docAbbr}-Accept_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($tempFile);

        return $tempFile;
    }
}
