<?php

namespace Erp\Bundle\ReportBundle\Controller;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PurchaseFinanceExcelReportHelper
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
        $sheet->setCellValue('A1', "รายงานเอกสาร{$docNameTh} ({$docAbbr}-DC)");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        // $sheet->setCellValue('A8', 'XXXXX : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        $sheet->setCellValue('B4', (!isset($filterDetail['budgetType'])) ? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        $sheet->setCellValue('B6', (!isset($filterDetail['vendor'])) ? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        // $sheet->setCellValue('B8', (!isset($filterDetail['xxxXxxxx']))? 'xxxxx' : ($filterDetail['xxxXxxxx']? 'XXX' : 'XXX'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:F9');
        $sheet->mergeCells('G9:G10');
        $sheet->mergeCells('H9:H10');
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
        $sheet->setCellValue('F10', 'ประเภท');
        $sheet->setCellValue('G9', 'ผู้ต้องการ');
        $sheet->setCellValue('H9', 'ผู้จำหน่าย');

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['budgetType']);
            $sheet->setCellValue('G' . $row, $item['requester']);
            $sheet->setCellValue('H' . $row, $item['vendor']);
            $sheet->getStyle("A{$row}:H{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;
        $tableEndRow = $itemEndRow;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:H{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $writer = new Xlsx($spreadsheet);
        $fileName = "RP-DC-PU-{$docAbbr}_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
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
        $sheet->mergeCells("A1:M1");
        $sheet->mergeCells("B2:M2");
        $sheet->mergeCells("B3:M3");
        $sheet->mergeCells("B4:M4");
        $sheet->mergeCells("B5:M5");
        $sheet->mergeCells("B6:M6");
        $sheet->getStyle('A1:M1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:M1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('left');
        $sheet->getStyle('C7:C8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('D7:D8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $sheet->setCellValue('A1', "รายงานการเงิน โดย {$docNameTh} (FI by {$docAbbr})");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        // $sheet->setCellValue('A8', 'XXXXX : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        $sheet->setCellValue('B4', (!isset($filterDetail['budgetType'])) ? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        $sheet->setCellValue('B6', (!isset($filterDetail['vendor'])) ? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        // $sheet->setCellValue('B8', (!isset($filterDetail['xxxXxxxx']))? 'xxxxx' : ($filterDetail['xxxXxxxx']? 'XXX' : 'XXX'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:F9');
        $sheet->mergeCells('G9:G10');
        $sheet->mergeCells('H9:H10');
        $sheet->mergeCells('I9:M9');
        // - > เพิ่มตรงนี้ 06/12/2565(เริ่ม)
        $sheet->mergeCells('N9:O9');
        $sheet->mergeCells('P9:R9');
        $sheet->mergeCells('S9:U9');
        $sheet->mergeCells('V9:W9');
        $sheet->mergeCells('X9:Z9');
        // - - - > เพิ่มตรงนี้ 06/12/2565(สิ้นสุด)

        $sheet->getStyle('A9:M10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A9:M10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A9:M10')->getFill()->getStartColor()->setRGB('DCDCDC');
        $sheet->getStyle('A9:M10')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A9:M10')->getAlignment()->setVertical('center');
        // - > เพิ่มตรงนี้ 06/12/2565(เริ่ม)
        $sheet->getStyle('N9:Z10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('N9:Z10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('N9:Z10')->getFill()->getStartColor()->setRGB('DCDCDC');
        $sheet->getStyle('N9:Z10')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('N9:Z10')->getAlignment()->setVertical('center');
        // - - - > เพิ่มตรงนี้ 06/12/2565(สิ้นสุด)

        $sheet->setCellValue('A9', 'ลำดับ');
        $sheet->setCellValue('B9', 'เอกสาร');
        $sheet->setCellValue('B10', 'เลขที่');
        $sheet->setCellValue('C10', 'สถานะ');
        $sheet->setCellValue('D9', 'โครงการ');
        $sheet->setCellValue('D10', 'รหัส');
        $sheet->setCellValue('E10', 'งบประมาณ');
        $sheet->setCellValue('F10', 'ประเภท');
        $sheet->setCellValue('G9', 'ผู้ต้องการ');
        $sheet->setCellValue('H9', 'ผู้จำหน่าย');
        $sheet->setCellValue('I9', 'มูลค่า (บาท)');
        $sheet->setCellValue('I10', 'VAT');
        $sheet->setCellValue('J10', 'ไม่รวมVAT');
        $sheet->setCellValue('K10', 'TAX');
        $sheet->setCellValue('L10', 'รวมชำระ');
        $sheet->setCellValue('M10', 'รวมสุทธิ');

        // - > เพิ่มตรงนี้ 06/12/2565(เริ่ม)
        $sheet->setCellValue('N9', 'VAT');
        $sheet->setCellValue('P9', 'TAX');
        $sheet->setCellValue('S9', 'การชำระเงิน');
        $sheet->setCellValue('V9', 'ประกันสินค้า');
        $sheet->setCellValue('X9', 'มัดจำสินค้า');
        $sheet->setCellValue('N10', 'สถานะ');
        $sheet->setCellValue('O10', 'มูลค่า (บาท)');
        $sheet->setCellValue('P10', 'สถานะ');
        $sheet->setCellValue('Q10', '%');
        $sheet->setCellValue('R10', 'มูลค่า (บาท)');
        $sheet->setCellValue('S10', 'สถานะ');
        $sheet->setCellValue('T10', 'วันที่');
        $sheet->setCellValue('U10', 'มูลค่า (บาท)');
        $sheet->setCellValue('V10', 'สถานะ');
        $sheet->setCellValue('W10', 'มูลค่า (บาท)');
        $sheet->setCellValue('X10', 'สถานะ');
        $sheet->setCellValue('Y10', 'มูลค่า (บาท)');
        $sheet->setCellValue('Z10', 'คงค้าง (บาท)');
        // - - - > เพิ่มตรงนี้ 06/12/2565(สิ้นสุด)

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['budgetType']);
            $sheet->setCellValue('G' . $row, $item['requester']);
            $sheet->setCellValue('H' . $row, $item['vendor']);
            $sheet->setCellValue('I' . $row, $item['vatCost']);
            $sheet->setCellValue('J' . $row, $item['excludeVat']);
            $sheet->setCellValue('K' . $row, $item['taxCost']);
            $sheet->setCellValue('L' . $row, $item['payTotal']);
            $sheet->setCellValue('M' . $row, $item['docTotal']);

            // - > เพิ่มตรงนี้ 06/12/2565(เริ่ม)
            $sheet->setCellValue('N' . $row, $item['vatFactor'] ? 'มี' : 'ไม่มี');
            $sheet->setCellValue('O' . $row, $item['vatCost']);
            $sheet->setCellValue('P' . $row, $item['taxFactor'] ? 'มี' : 'ไม่มี');
            $sheet->setCellValue('Q' . $row, $item['taxFactor'] * $item['tax']);
            $sheet->setCellValue('R' . $row, $item['taxCost']);
            $sheet->setCellValue('S' . $row, $item['payMethod'] ? 'เครดิต' : 'เงินสด');
            //รอแก้ไขตรงนี้
            $sheet->setCellValue('T' . $row, empty($item['dueDate']) ? '' : Date::PHPToExcel($item['dueDate']));
            //รอแก้ไขตรงนี้
            $sheet->setCellValue('U' . $row, $item['docTotal']);
            $sheet->setCellValue('V' . $row, $item['productWarranty'] ? 'มี' : 'ไม่มี');
            $sheet->setCellValue('W' . $row, $item['productWarranty'] * $item['productWarrantyCost']);
            $sheet->setCellValue('X' . $row, $item['payTerm'] ? 'มี' : 'ไม่มี');
            $sheet->setCellValue('Y' . $row, $item['payDeposit']);
            $sheet->setCellValue('Z' . $row, $item['docTotal'] - $item['payDeposit']);
            // - - - > เพิ่มตรงนี้ 06/12/2565(สิ้นสุด)

            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;

        foreach (range('I', 'M') as $colName) {
            $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
        }
        $row++;

        $tableEndRow = $row - 1;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:H{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $sheet->getStyle("A{$itemStartRow}:M{$tableEndRow}")->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("I{$itemStartRow}:M{$tableEndRow}")->getNumberFormat()
            ->setFormatCode(self::NUMBER_FORMAT);

        $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:M{$tableEndRow}");
        $itemFooterStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('DCDCDC');
        $itemFooterStyle->getFont()
            ->setBold(true);

        $sheet->getStyle("I{$tableEndRow}:M{$tableEndRow}")->getFont()
            ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);
        
        // - > เพิ่มตรงนี้ 06/12/2565(เริ่ม)
        $sheet->getStyle("N{$itemStartRow}:N{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("O{$itemStartRow}:O{$tableEndRow}")->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);
        $sheet->getStyle("P{$itemStartRow}:P{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("Q{$itemStartRow}:Q{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("R{$itemStartRow}:R{$tableEndRow}")->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);
        $sheet->getStyle("S{$itemStartRow}:T{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("T{$itemStartRow}:T{$tableEndRow}")->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $sheet->getStyle("U{$itemStartRow}:U{$tableEndRow}")->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);
        $sheet->getStyle("V{$itemStartRow}:V{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("W{$itemStartRow}:W{$tableEndRow}")->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);
        $sheet->getStyle("X{$itemStartRow}:X{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("Y{$itemStartRow}:Z{$tableEndRow}")->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);

        $sheet->getStyle("N{$itemStartRow}:Z{$tableEndRow}")->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $itemFooterStyle = $sheet->getStyle("N{$tableEndRow}:Z{$tableEndRow}");
        $itemFooterStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('DCDCDC');
        $itemFooterStyle->getFont()
            ->setBold(true);

        $sheet->getStyle("N{$tableEndRow}:Z{$tableEndRow}")->getFont()
            ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);
        // - - - > เพิ่มตรงนี้ 06/12/2565(สิ้นสุด)

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        // Create a Temporary file in the system
        $fileName = "RP-FI-PU-{$docAbbr}_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
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
        $sheet->mergeCells("A1:L1");
        $sheet->mergeCells("B2:L2");
        $sheet->mergeCells("B3:L3");
        $sheet->mergeCells("B4:L4");
        $sheet->mergeCells("B5:L5");
        $sheet->mergeCells("B6:L6");
        $sheet->getStyle('A1:L1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:L1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('left');
        $sheet->getStyle('C7:C8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('D7:D8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $sheet->setCellValue('A1', "รายงานภาษีมูลค่าเพิ่ม โดย {$docNameTh} (Vat by {$docAbbr})");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        $sheet->setCellValue('A8', 'สถานะVAT : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        $sheet->setCellValue('B4', (!isset($filterDetail['budgetType'])) ? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        $sheet->setCellValue('B6', (!isset($filterDetail['vendor'])) ? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        $sheet->setCellValue('B8', (!isset($filterDetail['vatFactor'])) ? 'ทั้งหมด' : ($filterDetail['vatFactor'] ? 'มีVAT' : 'ไม่มีVAT'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:F9');
        $sheet->mergeCells('G9:G10');
        $sheet->mergeCells('H9:H10');
        $sheet->mergeCells('I9:I10');
        $sheet->mergeCells('J9:L9');
        $sheet->getStyle('A9:L10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A9:L10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A9:L10')->getFill()->getStartColor()->setRGB('DCDCDC');
        $sheet->getStyle('A9:L10')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A9:L10')->getAlignment()->setVertical('center');
        $sheet->setCellValue('A9', 'ลำดับ');
        $sheet->setCellValue('B9', 'เอกสาร');
        $sheet->setCellValue('B10', 'เลขที่');
        $sheet->setCellValue('C10', 'สถานะ');
        $sheet->setCellValue('D9', 'โครงการ');
        $sheet->setCellValue('D10', 'รหัส');
        $sheet->setCellValue('E10', 'งบประมาณ');
        $sheet->setCellValue('F10', 'ประเภท');
        $sheet->setCellValue('G9', 'ผู้ต้องการ');
        $sheet->setCellValue('H9', 'ผู้จำหน่าย');
        $sheet->setCellValue('I9', 'สถานะVAT');
        $sheet->setCellValue('J9', 'มูลค่า (บาท)');
        $sheet->setCellValue('J10', 'VAT');
        $sheet->setCellValue('K10', 'ไม่รวมVAT');
        $sheet->setCellValue('L10', 'รวมสุทธิ');

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['budgetType']);
            $sheet->setCellValue('G' . $row, $item['requester']);
            $sheet->setCellValue('H' . $row, $item['vendor']);
            $sheet->setCellValue('I' . $row, $item['vatFactor'] ? 'มี' : 'ไม่มี');
            $sheet->setCellValue('J' . $row, $item['vatCost']);
            $sheet->setCellValue('K' . $row, $item['excludeVat']);
            $sheet->setCellValue('L' . $row, $item['docTotal']);
            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;

        foreach (range('J', 'L') as $colName) {
            $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
        }
        $row++;

        $tableEndRow = $row - 1;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:I{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $sheet->getStyle("A{$itemStartRow}:L{$tableEndRow}")->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("J{$itemStartRow}:L{$tableEndRow}")->getNumberFormat()
            ->setFormatCode(self::NUMBER_FORMAT);

        $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:L{$tableEndRow}");
        $itemFooterStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('DCDCDC');
        $itemFooterStyle->getFont()
            ->setBold(true);

        $sheet->getStyle("J{$tableEndRow}:L{$tableEndRow}")->getFont()
            ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        // Create a Temporary file in the system
        $fileName = "RP-FI-PU-{$docAbbr}-Vat_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
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
        $sheet->mergeCells("A1:M1");
        $sheet->mergeCells("B2:M2");
        $sheet->mergeCells("B3:M3");
        $sheet->mergeCells("B4:M4");
        $sheet->mergeCells("B5:M5");
        $sheet->mergeCells("B6:M6");
        $sheet->getStyle('A1:M1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:M1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('left');
        $sheet->getStyle('C7:C8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('D7:D8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $sheet->setCellValue('A1', "รายงานภาษีหัก ณ ที่จ่าย โดย {$docNameTh} (Tax by {$docAbbr})");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        $sheet->setCellValue('A8', 'สถานะTAX : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        $sheet->setCellValue('B4', (!isset($filterDetail['budgetType'])) ? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        $sheet->setCellValue('B6', (!isset($filterDetail['vendor'])) ? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        $sheet->setCellValue('B8', (!isset($filterDetail['taxFactor'])) ? 'ทั้งหมด' : ($filterDetail['taxFactor'] ? 'มีTAX' : 'ไม่มีTAX'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:F9');
        $sheet->mergeCells('G9:G10');
        $sheet->mergeCells('H9:H10');
        $sheet->mergeCells('I9:J9');
        $sheet->mergeCells('K9:M9');
        $sheet->getStyle('A9:M10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A9:M10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A9:M10')->getFill()->getStartColor()->setRGB('DCDCDC');
        $sheet->getStyle('A9:M10')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A9:M10')->getAlignment()->setVertical('center');
        $sheet->setCellValue('A9', 'ลำดับ');
        $sheet->setCellValue('B9', 'เอกสาร');
        $sheet->setCellValue('B10', 'เลขที่');
        $sheet->setCellValue('C10', 'สถานะ');
        $sheet->setCellValue('D9', 'โครงการ');
        $sheet->setCellValue('D10', 'รหัส');
        $sheet->setCellValue('E10', 'งบประมาณ');
        $sheet->setCellValue('F10', 'ประเภท');
        $sheet->setCellValue('G9', 'ผู้ต้องการ');
        $sheet->setCellValue('H9', 'ผู้จำหน่าย');
        $sheet->setCellValue('I9', 'TAX');
        $sheet->setCellValue('I10', 'สถานะ');
        $sheet->setCellValue('J10', '%');
        $sheet->setCellValue('K9', 'มูลค่า (บาท)');
        $sheet->setCellValue('K10', 'TAX');
        $sheet->setCellValue('L10', 'รวมชำระ');
        $sheet->setCellValue('M10', 'รวมสุทธิ');

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['budgetType']);
            $sheet->setCellValue('G' . $row, $item['requester']);
            $sheet->setCellValue('H' . $row, $item['vendor']);
            $sheet->setCellValue('I' . $row, $item['taxFactor'] ? 'มี' : 'ไม่มี');
            $sheet->setCellValue('J' . $row, $item['taxFactor'] * $item['tax']);
            $sheet->setCellValue('K' . $row, $item['taxCost']);
            $sheet->setCellValue('L' . $row, $item['payTotal']);
            $sheet->setCellValue('M' . $row, $item['docTotal']);
            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;

        foreach (range('J', 'M') as $colName) {
            $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
        }
        $row++;

        $tableEndRow = $row - 1;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:I{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $sheet->getStyle("A{$itemStartRow}:M{$tableEndRow}")->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("J{$itemStartRow}:M{$tableEndRow}")->getNumberFormat()
            ->setFormatCode(self::NUMBER_FORMAT);

        $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:M{$tableEndRow}");
        $itemFooterStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('DCDCDC');
        $itemFooterStyle->getFont()
            ->setBold(true);

        $sheet->getStyle("J{$tableEndRow}:M{$tableEndRow}")->getFont()
            ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        // Create a Temporary file in the system
        $fileName = "RP-FI-PU-{$docAbbr}-Tax_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
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
        $sheet->setCellValue('A1', "รายงานการชำระเงิน โดย {$docNameTh} (Payment by {$docAbbr})");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        $sheet->setCellValue('A8', 'สถานะการชำระเงิน : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        $sheet->setCellValue('B4', (!isset($filterDetail['budgetType'])) ? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        $sheet->setCellValue('B6', (!isset($filterDetail['vendor'])) ? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        $sheet->setCellValue('B8', (!isset($filterDetail['payMethod'])) ? 'ทั้งหมด' : ($filterDetail['payMethod'] ? 'เครดิต' : 'เงินสด'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:F9');
        $sheet->mergeCells('G9:G10');
        $sheet->mergeCells('H9:H10');
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
        $sheet->setCellValue('F10', 'ประเภท');
        $sheet->setCellValue('G9', 'ผู้ต้องการ');
        $sheet->setCellValue('H9', 'ผู้จำหน่าย');
        $sheet->setCellValue('I9', 'การชำระเงิน');
        $sheet->setCellValue('I10', 'สถานะ');
        $sheet->setCellValue('J10', 'วันที่');
        $sheet->setCellValue('K10', 'มูลค่า (บาท)');

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['budgetType']);
            $sheet->setCellValue('G' . $row, $item['requester']);
            $sheet->setCellValue('H' . $row, $item['vendor']);
            $sheet->setCellValue('I' . $row, $item['payMethod'] ? 'เครดิต' : 'เงินสด');
            //รอแก้ไขตรงนี้
            $sheet->setCellValue('J' . $row, empty($item['dueDate']) ? '' : Date::PHPToExcel($item['dueDate']));
            //รอแก้ไขตรงนี้
            $sheet->setCellValue('K' . $row, $item['docTotal']);
            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;

        foreach (range('K', 'K') as $colName) {
            $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
        }
        $row++;

        $tableEndRow = $row - 1;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:J{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $sheet->getStyle("A{$itemStartRow}:K{$tableEndRow}")->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("K{$itemStartRow}:K{$tableEndRow}")->getNumberFormat()
            ->setFormatCode(self::NUMBER_FORMAT);

        $sheet->getStyle("J{$itemStartRow}:J{$tableEndRow}")->getNumberFormat()
            ->setFormatCode('DD/MM/YYYY');

        $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:K{$tableEndRow}");
        $itemFooterStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('DCDCDC');
        $itemFooterStyle->getFont()
            ->setBold(true);

        $sheet->getStyle("K{$tableEndRow}:K{$tableEndRow}")->getFont()
            ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        // Create a Temporary file in the system
        $fileName = "RP-FI-PU-{$docAbbr}-Payment_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($tempFile);

        return $tempFile;
    }

    function warrantyReportExcel(
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
        $sheet->setCellValue('A1', "รายงานค่าประกันสินค้า โดย {$docNameTh} (Warranty by {$docAbbr})");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        $sheet->setCellValue('A8', 'สถานะประกันสินค้า : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        $sheet->setCellValue('B4', (!isset($filterDetail['budgetType'])) ? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        $sheet->setCellValue('B6', (!isset($filterDetail['vendor'])) ? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        $sheet->setCellValue('B8', (!isset($filterDetail['productWarranty'])) ? 'ทั้งหมด' : ($filterDetail['productWarranty'] ? 'มี' : 'ไม่มี'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:F9');
        $sheet->mergeCells('G9:G10');
        $sheet->mergeCells('H9:H10');
        $sheet->mergeCells('I9:J9');
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
        $sheet->setCellValue('F10', 'ประเภท');
        $sheet->setCellValue('G9', 'ผู้ต้องการ');
        $sheet->setCellValue('H9', 'ผู้จำหน่าย');
        $sheet->setCellValue('I9', 'ประกันสินค้า');
        $sheet->setCellValue('I10', 'สถานะ');
        $sheet->setCellValue('J10', 'มูลค่า (บาท)');

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['budgetType']);
            $sheet->setCellValue('G' . $row, $item['requester']);
            $sheet->setCellValue('H' . $row, $item['vendor']);
            $sheet->setCellValue('I' . $row, $item['productWarranty'] ? 'มี' : 'ไม่มี');
            $sheet->setCellValue('J' . $row, $item['productWarranty'] * $item['productWarrantyCost']);
            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;

        foreach (range('J', 'J') as $colName) {
            $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
        }
        $row++;

        $tableEndRow = $row - 1;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:I{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $sheet->getStyle("A{$itemStartRow}:J{$tableEndRow}")->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("J{$itemStartRow}:J{$tableEndRow}")->getNumberFormat()
            ->setFormatCode(self::NUMBER_FORMAT);

        $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:J{$tableEndRow}");
        $itemFooterStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('DCDCDC');
        $itemFooterStyle->getFont()
            ->setBold(true);

        $sheet->getStyle("J{$tableEndRow}:J{$tableEndRow}")->getFont()
            ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        // Create a Temporary file in the system
        $fileName = "RP-FI-PU-{$docAbbr}-Warranty_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($tempFile);

        return $tempFile;
    }

    function depositReportExcel(
        array $data,
        array $filterDetail,
        string $docNameEn,
        string $docNameTh,
        string $docAbbr,
        ?string &$fileName
    ): string {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->mergeCells("A1:L1");
        $sheet->mergeCells("B2:L2");
        $sheet->mergeCells("B3:L3");
        $sheet->mergeCells("B4:L4");
        $sheet->mergeCells("B5:L5");
        $sheet->mergeCells("B6:L6");
        $sheet->getStyle('A1:L1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:L1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('left');
        $sheet->getStyle('C7:C8')->getAlignment()->setHorizontal('right');
        $sheet->getStyle('D7:D8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
        $sheet->setCellValue('A1', "รายงานค่ามัดจำสินค้า โดย {$docNameTh} (Deposit by {$docAbbr})");
        $sheet->setCellValue('A2', 'โครงการ : ');
        $sheet->setCellValue('A3', 'งบประมาณ : ');
        $sheet->setCellValue('A4', 'ประเภท : ');
        $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
        $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
        $sheet->setCellValue('A7', 'สถานะเอกสาร : ');
        $sheet->setCellValue('A8', 'สถานะมัดจำสินค้า : ');
        $sheet->setCellValue('C7', 'วันที่เริ่มต้น : ');
        $sheet->setCellValue('C8', 'วันที่สิ้นสุด : ');
        $sheet->setCellValue('B2', (!isset($filterDetail['project'])) ? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
        $sheet->setCellValue('B3', (!isset($filterDetail['boq'])) ? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
        $sheet->setCellValue('B4', (!isset($filterDetail['budgetType'])) ? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
        $sheet->setCellValue('B5', (!isset($filterDetail['requester'])) ? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
        $sheet->setCellValue('B6', (!isset($filterDetail['vendor'])) ? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
        $sheet->setCellValue('B7', (!isset($filterDetail['approved'])) ? 'ทั้งหมด' : ($filterDetail['approved'] ? 'อนุมัติ' : 'รออนุมัติ'));
        $sheet->setCellValue('B8', (!isset($filterDetail['payTerm'])) ? 'ทั้งหมด' : ($filterDetail['payTerm'] ? 'มี' : 'ไม่มี'));
        $sheet->setCellValue('D7', (!isset($filterDetail['start'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
        $sheet->setCellValue('D8', (!isset($filterDetail['end'])) ? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

        $sheet->mergeCells('A9:A10');
        $sheet->mergeCells('B9:C9');
        $sheet->mergeCells('D9:F9');
        $sheet->mergeCells('G9:G10');
        $sheet->mergeCells('H9:H10');
        $sheet->mergeCells('I9:I10');
        $sheet->mergeCells('J9:L9');
        $sheet->getStyle('A9:L10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A9:L10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A9:L10')->getFill()->getStartColor()->setRGB('DCDCDC');
        $sheet->getStyle('A9:L10')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A9:L10')->getAlignment()->setVertical('center');
        $sheet->setCellValue('A9', 'ลำดับ');
        $sheet->setCellValue('B9', 'เอกสาร');
        $sheet->setCellValue('B10', 'เลขที่');
        $sheet->setCellValue('C10', 'สถานะ');
        $sheet->setCellValue('D9', 'โครงการ');
        $sheet->setCellValue('D10', 'รหัส');
        $sheet->setCellValue('E10', 'งบประมาณ');
        $sheet->setCellValue('F10', 'ประเภท');
        $sheet->setCellValue('G9', 'ผู้ต้องการ');
        $sheet->setCellValue('H9', 'ผู้จำหน่าย');
        $sheet->setCellValue('I9', 'สถานะมัดจำ');
        $sheet->setCellValue('J9', 'มูลค่า (บาท)');
        $sheet->setCellValue('J10', 'มัดจำ');
        $sheet->setCellValue('K10', 'ค้างชำระ');
        $sheet->setCellValue('L10', 'รวมสุทธิ');

        $row = 11;
        $count = 1;
        $itemStartRow = $row;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $count);
            $sheet->setCellValue('B' . $row, $item['code']);
            $sheet->setCellValue('C' . $row, $item['approved'] ? 'อนุมัติ' : 'รออนุมัติ');
            $sheet->setCellValue('D' . $row, $item['project']);
            $sheet->setCellValue('E' . $row, $item['boq']);
            $sheet->setCellValue('F' . $row, $item['budgetType']);
            $sheet->setCellValue('G' . $row, $item['requester']);
            $sheet->setCellValue('H' . $row, $item['vendor']);
            $sheet->setCellValue('I' . $row, $item['payTerm'] ? 'มี' : 'ไม่มี');
            $sheet->setCellValue('J' . $row, $item['payDeposit']);
            $sheet->setCellValue('K' . $row, $item['docTotal'] - $item['payDeposit']);
            $sheet->setCellValue('L' . $row, $item['docTotal']);
            $row++;
            $count++;
        }
        $itemEndRow = $row - 1;

        foreach (range('J', 'L') as $colName) {
            $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
        }
        $row++;

        $tableEndRow = $row - 1;

        $sheet->getStyle("A{$itemStartRow}:D{$tableEndRow}")->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F{$itemStartRow}:I{$tableEndRow}")->getAlignment()->setHorizontal('center');

        $sheet->getStyle("A{$itemStartRow}:L{$tableEndRow}")->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("J{$itemStartRow}:L{$tableEndRow}")->getNumberFormat()
            ->setFormatCode(self::NUMBER_FORMAT);

        $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:L{$tableEndRow}");
        $itemFooterStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('DCDCDC');
        $itemFooterStyle->getFont()
            ->setBold(true);

        $sheet->getStyle("J{$tableEndRow}:L{$tableEndRow}")->getFont()
            ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

        // Create your Office 2007 Excel (XLSX Format)
        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

        // Create a Temporary file in the system
        $fileName = "RP-FI-PU-{$docAbbr}-Deposit_rev.2.1.0_" . date('Ymd_His', time()) . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        // Create the excel file in the tmp directory of the system
        $writer->save($tempFile);

        return $tempFile;
    }
}
