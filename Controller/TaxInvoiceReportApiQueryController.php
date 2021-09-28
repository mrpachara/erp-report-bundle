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
 * TaxInvoice Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/tax-invoice")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class TaxInvoiceReportApiQueryController
{
    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\TaxInvoiceReportQuery
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
     * TaxInvoiceReportApiQueryController constructor.
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\TaxInvoiceReportQuery $domainQuery,
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
    public function taxInvoiceSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->taxInvoiceSummary($request->getQueryParams()),
        ];

    }

    /**
     * @Rest\Get("/export.{format}")
     */
    public function taxInvoiceSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->taxInvoiceSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        switch(strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/tax-invoice-report.pdf.twig', [
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
                $sheet->mergeCells("A1:F1");
                $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal('center');
                $sheet->setCellValue('A1', 'รายงานเอกสารใบเสร็จรับเงิน/ใบกำกับภาษี (TI-DC)');
                $sheet->mergeCells("C2:F2");
                $sheet->mergeCells("C3:F3");
                $sheet->mergeCells("C4:F4");
                $sheet->getStyle('B2:B6')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('C2:C6')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('E5:E6')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('F5:F6')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                $sheet->setCellValue('B2', 'โครงการ : ');
                $sheet->setCellValue('B3', 'งบประมาณ : ');
                $sheet->setCellValue('B4', 'ผู้ต้องการ : ');
                $sheet->setCellValue('B5', 'สถานะเอกสาร : ');
                $sheet->setCellValue('E5', 'วันที่เริ่มต้น : ');
                $sheet->setCellValue('E6', 'วันที่สิ้นสุด : ');
                $sheet->setCellValue('C2', (!isset($filterDetail['project']))? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
                $sheet->setCellValue('C3', (!isset($filterDetail['boq']))? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
                $sheet->setCellValue('C4', (!isset($filterDetail['requester']))? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
                $sheet->setCellValue('C5', (!isset($filterDetail['approved']))? 'ทั้งหมด' : ($filterDetail['approved']? 'อนุมัติ' : 'รออนุมัติ'));
                $sheet->setCellValue('F5', (!isset($filterDetail['start']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
                $sheet->setCellValue('F6', (!isset($filterDetail['end']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

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
                foreach($data as $item) {
					$sheet->getStyle('A:D')->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('F')->getAlignment()->setHorizontal('center');
                    $sheet->setCellValue('A'.$row, $count);
                    $sheet->setCellValue('B'.$row, $item['code']);
                    $sheet->setCellValue('C'.$row, $item['approved']? 'อนุมัติ' : 'รออนุมัติ');
                    $sheet->setCellValue('D'.$row, $item['project']);
                    $sheet->setCellValue('E'.$row, $item['boq']);
                    $sheet->setCellValue('F'.$row, $item['requester']);
                    $sheet->getStyle("A{$row}:F{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $row++;
                    $count++;
                }
				
                $writer = new Xlsx($spreadsheet);
                $fileName = 'RP-DC-IN-TI_rev.2.1.0_'.date('Ymd_His', time()).'.xlsx';
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
    public function taxInvoiceCostSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->taxInvoiceSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        switch(strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/tax-invoice-cost-report.pdf.twig', [
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
                $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal('center');
                $sheet->setCellValue('A1', 'รายงานเอกสารใบเสร็จรับเงิน/ใบกำกับภาษี (TI-DC)');
                $sheet->mergeCells("C2:F2");
                $sheet->mergeCells("C3:F3");
                $sheet->mergeCells("C4:F4");
                $sheet->getStyle('B2:B6')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('C2:C6')->getAlignment()->setHorizontal('left');
                $sheet->getStyle('E5:E6')->getAlignment()->setHorizontal('right');
                $sheet->getStyle('F5:F6')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                $sheet->setCellValue('B2', 'โครงการ : ');
                $sheet->setCellValue('B3', 'งบประมาณ : ');
                $sheet->setCellValue('B4', 'ผู้ต้องการ : ');
                $sheet->setCellValue('B5', 'สถานะเอกสาร : ');
                $sheet->setCellValue('E5', 'วันที่เริ่มต้น : ');
                $sheet->setCellValue('E6', 'วันที่สิ้นสุด : ');
                $sheet->setCellValue('C2', (!isset($filterDetail['project']))? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
                $sheet->setCellValue('C3', (!isset($filterDetail['boq']))? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
                $sheet->setCellValue('C4', (!isset($filterDetail['requester']))? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
                $sheet->setCellValue('C5', (!isset($filterDetail['approved']))? 'ทั้งหมด' : ($filterDetail['approved']? 'อนุมัติ' : 'รออนุมัติ'));
                $sheet->setCellValue('F5', (!isset($filterDetail['start']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
                $sheet->setCellValue('F6', (!isset($filterDetail['end']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

                $sheet->mergeCells('A9:A10');
                $sheet->mergeCells('B9:C9');
                $sheet->mergeCells('D9:E9');
                $sheet->mergeCells('F9:F10');
                $sheet->mergeCells('G9:K9');
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
                $sheet->setCellValue('G9', 'มูลค่า (บาท)');
                $sheet->setCellValue('G10', 'VAT');
                $sheet->setCellValue('H10', 'ไม่รวมVAT');
                $sheet->setCellValue('I10', 'TAX');
                $sheet->setCellValue('J10', 'รวมชำระ');
                $sheet->setCellValue('K10', 'รวมสุทธิ');

                $row = 11;
                $count = 1;
                $itemStartRow = $row;
                foreach($data as $item) {
                    $sheet->getStyle('A:D')->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('F')->getAlignment()->setHorizontal('center');
                    $sheet->setCellValue('A'.$row, $count);
                    $sheet->setCellValue('B'.$row, $item['code']);
                    $sheet->setCellValue('C'.$row, $item['approved']? 'อนุมัติ' : 'รออนุมัติ');
                    $sheet->setCellValue('D'.$row, $item['project']);
                    $sheet->setCellValue('E'.$row, $item['boq']);
                    $sheet->setCellValue('F'.$row, $item['requester']);
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
                $fileName = 'RP-FI-IN-TI_rev.2.1.0_'.date('Ymd_His', time()).'.xlsx';
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