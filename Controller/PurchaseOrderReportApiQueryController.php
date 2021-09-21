<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * PurchaseOrder Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/purchase-order")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class PurchaseOrderReportApiQueryController
{
    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseOrderReportQuery
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
     * PurchaseOrderReportApiQueryController constructor.
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseOrderReportQuery $domainQuery,
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
    public function purchaseOrderSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->purchaseOrderSummary($request->getQueryParams()),
        ];
    }

    /**
     * @Rest\Get("/export.{format}")
     */
    public function purchaseOrderSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->purchaseOrderSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        switch(strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/purchase-order-report.pdf.twig', [
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
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
				$sheet->mergeCells("A1:H1");
                $sheet->mergeCells("C2:H2");
                $sheet->mergeCells("C3:H3");
                $sheet->mergeCells("C4:H4");
                $sheet->mergeCells("C5:H5");
                $sheet->mergeCells("C6:H6");
                $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('G7:G8')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('H7:H8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                $sheet->setCellValue('A1', 'รายงานเอกสารใบสั่งซื้อ (PO-DC)');
                $sheet->setCellValue('B2', 'โครงการ : ');
                $sheet->setCellValue('B3', 'งบประมาณ : ');
                $sheet->setCellValue('B4', 'ประเภท : ');
                $sheet->setCellValue('B5', 'ผู้ต้องการ : ');
                $sheet->setCellValue('B6', 'ผู้จำหน่าย : ');
                $sheet->setCellValue('B7', 'สถานะเอกสาร : ');
                // $sheet->setCellValue('B8', 'สถานะVAT : ');
                $sheet->setCellValue('G7', 'วันที่เริ่มต้น : ');
                $sheet->setCellValue('G8', 'วันที่สิ้นสุด : ');
                $sheet->setCellValue('C2', (!isset($filterDetail['project']))? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
                $sheet->setCellValue('C3', (!isset($filterDetail['boq']))? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
                $sheet->setCellValue('C4', (!isset($filterDetail['budgetType']))? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
                $sheet->setCellValue('C5', (!isset($filterDetail['requester']))? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
                $sheet->setCellValue('C6', (!isset($filterDetail['vendor']))? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
                $sheet->setCellValue('C7', (!isset($filterDetail['approved']))? 'ทั้งหมด' : ($filterDetail['approved']? 'อนุมัติ' : 'รออนุมัติ'));
                // $sheet->setCellValue('C8', (!isset($filterDetail['vatFactor']))? 'ทั้งหมด' : ($filterDetail['vatFactor']? 'มีVAT' : 'ไม่มีVAT'));
                $sheet->setCellValue('H7', (!isset($filterDetail['start']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
                $sheet->setCellValue('H8', (!isset($filterDetail['end']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));
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
                foreach($data as $item) {
					$sheet->getStyle('A:D')->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('F:H')->getAlignment()->setHorizontal('center');
                    $sheet->setCellValue('A'.$row, $count);
                    $sheet->setCellValue('B'.$row, $item['code']);
                    $sheet->setCellValue('C'.$row, $item['approved']? 'อนุมัติ' : 'รออนุมัติ');
                    $sheet->setCellValue('D'.$row, $item['project']);
                    $sheet->setCellValue('E'.$row, $item['boq']);
                    $sheet->setCellValue('F'.$row, $item['budgetType']);
                    $sheet->setCellValue('G'.$row, $item['requester']);
                    $sheet->setCellValue('H'.$row, $item['vendor']);
                    $sheet->getStyle("A{$row}:H{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $row++;
                    $count++;
                }
				
                $writer = new Xlsx($spreadsheet);
                $fileName = 'RP-DC-PU-PO_rev2.1.0_'.date('Ymd_His', time()).'.xlsx';
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
    public function purchaseOrderCostSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->purchaseOrderSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        switch(strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/purchase-order-cost-report.pdf.twig', [
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
                $sheet->mergeCells("A1:K1");
                $sheet->mergeCells("C2:K2");
                $sheet->mergeCells("C3:K3");
                $sheet->mergeCells("C4:K4");
                $sheet->mergeCells("C5:K5");
                $sheet->mergeCells("C6:K6");
                $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('B2:B8')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('J7:J8')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('K7:K8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                $sheet->setCellValue('A1', 'รายงานมูลค่าใบสั่งซื้อ (PO-FI)');
                $sheet->setCellValue('B2', 'โครงการ : ');
                $sheet->setCellValue('B3', 'งบประมาณ : ');
                $sheet->setCellValue('B4', 'ประเภท : ');
                $sheet->setCellValue('B5', 'ผู้ต้องการ : ');
                $sheet->setCellValue('B6', 'ผู้จำหน่าย : ');
                $sheet->setCellValue('B7', 'สถานะเอกสาร : ');
                // $sheet->setCellValue('B8', 'สถานะVAT : ');
                $sheet->setCellValue('J7', 'วันที่เริ่มต้น : ');
                $sheet->setCellValue('J8', 'วันที่สิ้นสุด : ');
                $sheet->setCellValue('C2', (!isset($filterDetail['project']))? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
                $sheet->setCellValue('C3', (!isset($filterDetail['boq']))? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
                $sheet->setCellValue('C4', (!isset($filterDetail['budgetType']))? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
                $sheet->setCellValue('C5', (!isset($filterDetail['requester']))? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
                $sheet->setCellValue('C6', (!isset($filterDetail['vendor']))? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
                $sheet->setCellValue('C7', (!isset($filterDetail['approved']))? 'ทั้งหมด' : ($filterDetail['approved']? 'อนุมัติ' : 'รออนุมัติ'));
                // $sheet->setCellValue('C8', (!isset($filterDetail['vatFactor']))? 'ทั้งหมด' : ($filterDetail['vatFactor']? 'มีVAT' : 'ไม่มีVAT'));
                $sheet->setCellValue('K7', (!isset($filterDetail['start']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
                $sheet->setCellValue('K8', (!isset($filterDetail['end']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));
                $sheet->getStyle('A9:K10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle('A9:K10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A9:K10')->getFill()->getStartColor()->setRGB('DCDCDC');
				$sheet->getStyle('A9:K10')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A9:K10')->getAlignment()->setVertical('center');
                $sheet->mergeCells('A9:A10');
                $sheet->mergeCells('B9:C9');
                $sheet->mergeCells('D9:F9');
                $sheet->mergeCells('G9:K9');
                $sheet->setCellValue('A9', 'ลำดับ');
                $sheet->setCellValue('B9', 'เอกสาร');
                $sheet->setCellValue('B10', 'เลขที่');
                $sheet->setCellValue('C10', 'สถานะ');
                $sheet->setCellValue('D9', 'โครงการ');
                $sheet->setCellValue('D10', 'รหัส');
                $sheet->setCellValue('E10', 'งบประมาณ');
                $sheet->setCellValue('F10', 'ประเภท');
                $sheet->setCellValue('G9', 'มูลค่าการสั่งซื้อ');
                $sheet->setCellValue('G10', 'VAT');
                $sheet->setCellValue('H10', 'ไม่รวมVAT');
                $sheet->setCellValue('I10', 'TAX');
                $sheet->setCellValue('J10', 'รวมชำระ');
                $sheet->setCellValue('K10', 'รวมสุทธิ');

                $row = 11;
                $count = 1;
                $itemStartRow = $row;
                foreach($data as $item) {
                    $sheet->getStyle('A')->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('C:D')->getAlignment()->setHorizontal('center');
                    $sheet->setCellValue('A'.$row, $count);
                    $sheet->setCellValue('B'.$row, $item['code']);
                    $sheet->setCellValue('C'.$row, $item['approved']? 'อนุมัติ' : 'รออนุมัติ');
                    $sheet->setCellValue('D'.$row, $item['project']);
                    $sheet->setCellValue('E'.$row, $item['boq']);
                    $sheet->setCellValue('F'.$row, $item['budgetType']);
                    $sheet->setCellValue('G'.$row, $item['vatCost']);
                    $sheet->setCellValue('H'.$row, $item['excludeVat']);
                    $sheet->setCellValue('I'.$row, $item['taxCost']);
                    $sheet->setCellValue('J'.$row, $item['payTotal']);
                    $sheet->setCellValue('K'.$row, $item['total']);
                    $row++;
                    $count++;
                }
                $itemEndRow = $row - 1;

                foreach(range('G', 'K') as $colName) {
                    $sheet->setCellValue("{$colName}{$row}", "=SUM({$colName}:{$colName} {$itemStartRow}:{$itemEndRow})");
                }
                $row++;

                $tableEndRow = $row - 1;

                $sheet->getStyle("A{$itemStartRow}:K{$tableEndRow}")->getBorders()
                    ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN)
                ;

                $sheet->getStyle("G{$itemStartRow}:K{$tableEndRow}")->getNumberFormat()
                    ->setFormatCode($costFormat)
                ;

                $itemFooterStyle = $sheet->getStyle("A{$tableEndRow}:K{$tableEndRow}");
                $itemFooterStyle->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                        ->setRGB('DCDCDC')
                ;
                $itemFooterStyle->getFont()
                    ->setBold(true)
                ;

                $sheet->getStyle("G{$tableEndRow}:K{$tableEndRow}")->getFont()
                    ->setUnderline(Font::UNDERLINE_DOUBLEACCOUNTING)
                ;

                // Create your Office 2007 Excel (XLSX Format)
                $writer = new Xlsx($spreadsheet);
                $writer->setPreCalculateFormulas(false);

                // Create a Temporary file in the system
                $fileName = 'RP-FI-PU-PO_rev2.1.0_'.date('Ymd_His', time()).'.xlsx';
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