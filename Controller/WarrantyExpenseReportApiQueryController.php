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
 * warranty expense Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/warranty-expense")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class WarrantyExpenseReportApiQueryController
{
    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\WarrantyExpenseReportQuery
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
     * WarrantyExpenseReportQueryController constructor.
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\WarrantyExpenseReportQuery $domainQuery,
        \Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery $settingQuery,
        \Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery $fileQuery,
        \Twig_Environment $templating,
        \Erp\Bundle\DocumentBundle\Service\PDFService $pdfService
        )
    {
        $this->domainQuery = $domainQuery;
        $this->settingQuery = $settingQuery;
        $this->fileQuery = $fileQuery;
        $this->templating = $templating;
        $this->pdfService = $pdfService;
    }

    /**
     * @Rest\Get("")
     */
    public function warrantyExpenseSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->warrantyExpenseSummary($request->getQueryParams()),
        ];
    }

    /**
     * @Rest\Get("/export.{format}")
     */
    public function warrantyExpenseSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->warrantyExpenseSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        switch(strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/warranty-expense-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
            break;
            case 'xlsx':
                $costFormat = '#,##0.00_-;[Red]-#,##0.00_-;??"-"??_-;[Green]@_-';

                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->mergeCells("A1:J1");
                $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:J1')->getFont()->setSize(16)->setBold(true);
                $sheet->setCellValue('A1', 'รายงานค่าประกันสินค้า โดย ใบจ่ายเงิน (Warranty by EP-FI)');
                $sheet->mergeCells("C2:F2");
                $sheet->mergeCells("C3:F3");
                $sheet->mergeCells("C4:F4");
                $sheet->mergeCells("C5:F5");
                $sheet->mergeCells("C6:F6");
                $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('C2:C8')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('E7:E8')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('F7:F8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                $sheet->setCellValue('B2', 'โครงการ : ');
                $sheet->setCellValue('B3', 'งบประมาณ : ');
                $sheet->setCellValue('B4', 'ประเภท : ');
                $sheet->setCellValue('B5', 'ผู้ต้องการ : ');
                $sheet->setCellValue('B6', 'ผู้จำหน่าย : ');
                $sheet->setCellValue('B7', 'สถานะเอกสาร : ');
                $sheet->setCellValue('B8', 'สถานะประกันสินค้า : ');
                $sheet->setCellValue('E7', 'วันที่เริ่มต้น : ');
                $sheet->setCellValue('E8', 'วันที่สิ้นสุด : ');
                $sheet->setCellValue('C2', (!isset($filterDetail['project']))? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
                $sheet->setCellValue('C3', (!isset($filterDetail['boq']))? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
                $sheet->setCellValue('C4', (!isset($filterDetail['budgetType']))? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
                $sheet->setCellValue('C5', (!isset($filterDetail['requester']))? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
                $sheet->setCellValue('C6', (!isset($filterDetail['vendor']))? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
                $sheet->setCellValue('C7', (!isset($filterDetail['approved']))? 'ทั้งหมด' : ($filterDetail['approved']? 'อนุมัติ' : 'รออนุมัติ'));
                $sheet->setCellValue('C8', (!isset($filterDetail['productWarranty']))? 'ทั้งหมด' : ($filterDetail['productWarranty']? 'มี' : 'ไม่มี'));
                $sheet->setCellValue('F7', (!isset($filterDetail['start']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
                $sheet->setCellValue('F8', (!isset($filterDetail['end']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

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
                foreach($data as $item) {
                    $sheet->getStyle('A:D')->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('F:I')->getAlignment()->setHorizontal('center');
                    $sheet->setCellValue('A'.$row, $count);
                    $sheet->setCellValue('B'.$row, $item['code']);
                    $sheet->setCellValue('C'.$row, $item['approved']? 'อนุมัติ' : 'รออนุมัติ');
                    $sheet->setCellValue('D'.$row, $item['project']);
                    $sheet->setCellValue('E'.$row, $item['boq']);
                    $sheet->setCellValue('F'.$row, $item['budgetType']);
                    $sheet->setCellValue('G'.$row, $item['requester']);
                    $sheet->setCellValue('H'.$row, $item['vendor']);
                    $sheet->setCellValue('I'.$row, $item['productWarranty']? 'มี' : 'ไม่มี');
                    $sheet->setCellValue('J'.$row, $item['productWarranty'] * $item['productWarrantyCost']);
                    $row++;
                    $count++;
                }
                $itemEndRow = $row - 1;

                foreach(range('J', 'J') as $colName) {
                    $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
                }
                $row++;

                $tableEndRow = $row - 1;

                $sheet->getStyle("A{$itemStartRow}:J{$tableEndRow}")->getBorders()
                    ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN)
                ;

                $sheet->getStyle("J{$itemStartRow}:J{$tableEndRow}")->getNumberFormat()
                    ->setFormatCode($costFormat)
                ;

                $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:J{$tableEndRow}");
                $itemFooterStyle->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                        ->setRGB('DCDCDC')
                ;
                $itemFooterStyle->getFont()
                    ->setBold(true)
                ;

                $sheet->getStyle("J{$tableEndRow}:J{$tableEndRow}")->getFont()
                    ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING)
                ;

                // Create your Office 2007 Excel (XLSX Format)
                $writer = new Xlsx($spreadsheet);
                $writer->setPreCalculateFormulas(false);

                // Create a Temporary file in the system
                $fileName = 'RP-FI-PU-EP-Warranty_rev.2.1.0_'.date('Ymd_His', time()).'.xlsx';
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