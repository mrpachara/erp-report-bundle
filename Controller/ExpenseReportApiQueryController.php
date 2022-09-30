<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Expense Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/expense")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class ExpenseReportApiQueryController
{
    const docNameEn = 'EXPENSE';
    const docNameTh = 'ใบจ่ายเงิน';
    const docAbbr = 'EP';

    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\ExpenseReportQuery
     */
    private $domainQuery;

    /**
     * @var \Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery
     */
    protected $settingQuery = null;

    /**
     * @var \Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery
     */
    protected $fileQuery = null;

    /**
     * @var \Twig_Environment
     */
    protected $templating;

    /**
     * @var \Erp\Bundle\DocumentBundle\Service\PDFService
     */
    protected $pdfService = null;

    /**
     * ExpenseReportApiQueryController constructor.
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\ExpenseReportQuery $domainQuery,
        \Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery $settingQuery,
        \Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery $fileQuery,
        \Twig_Environment $templating,
        \Erp\Bundle\DocumentBundle\Service\PDFService $pdfService
    ) {
        $this->domainQuery = $domainQuery;
        $this->settingQuery = $settingQuery;
        $this->fileQuery = $fileQuery;
        $this->templating = $templating;
        $this->pdfService = $pdfService;
    }

    /**
     * @Rest\Get("")
     */
    public function expenseSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->expenseSummary($request->getQueryParams()),
        ];
    }

    /**
     * @Rest\Get("/export.{format}")
     */
    public function expenseSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->expenseSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/purchase-finance-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                    'docNameEn' => self::docNameEn,
                    'docNameTh' => self::docNameTh,
                    'docAbbr' => self::docAbbr,
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
                break;
            case 'xlsx':
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
                $sheet->setCellValue('A1', 'รายงานเอกสาร' . self::docNameTh . ' (' . self::docAbbr . '-DC)');
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
                $fileName = 'RP-DC-PU-' . self::docAbbr . '_rev.2.1.0_' . date('Ymd_His', time()) . '.xlsx';
                $temp_file = tempnam(sys_get_temp_dir(), $fileName);
                $writer->save($temp_file);
                $response = new BinaryFileResponse($temp_file);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
                break;
        }
    }

    /**
     * @Rest\Get("/cost/export.{format}")
     */
    public function expenseCostSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->expenseSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/purchase-finance-cost-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                    'docNameEn' => self::docNameEn,
                    'docNameTh' => self::docNameTh,
                    'docAbbr' => self::docAbbr,
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
                break;
            case 'xlsx':
                $costFormat = '#,##0.00_-;[Red]-#,##0.00_-;??"-"??_-;[Green]@_-';

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
                $sheet->setCellValue('A1', 'รายงานการเงิน' . self::docNameTh . ' (' . self::docAbbr . '-FI)');
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
                $sheet->setCellValue('I9', 'มูลค่า (บาท)');
                $sheet->setCellValue('I10', 'VAT');
                $sheet->setCellValue('J10', 'ไม่รวมVAT');
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
                    $sheet->setCellValue('I' . $row, $item['vatCost']);
                    $sheet->setCellValue('J' . $row, $item['excludeVat']);
                    $sheet->setCellValue('K' . $row, $item['taxCost']);
                    $sheet->setCellValue('L' . $row, $item['payTotal']);
                    $sheet->setCellValue('M' . $row, $item['docTotal']);
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
                    ->setFormatCode($costFormat);

                $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:M{$tableEndRow}");
                $itemFooterStyle->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('DCDCDC');
                $itemFooterStyle->getFont()
                    ->setBold(true);

                $sheet->getStyle("I{$tableEndRow}:M{$tableEndRow}")->getFont()
                    ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING);

                // Create your Office 2007 Excel (XLSX Format)
                $writer = new Xlsx($spreadsheet);
                $writer->setPreCalculateFormulas(false);

                // Create a Temporary file in the system
                $fileName = 'RP-FI-PU-' . self::docAbbr . '_rev.2.1.0_' . date('Ymd_His', time()) . '.xlsx';
                $temp_file = tempnam(sys_get_temp_dir(), $fileName);

                // Create the excel file in the tmp directory of the system
                $writer->save($temp_file);

                $response = new BinaryFileResponse($temp_file);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
                break;
        }
    }
}
