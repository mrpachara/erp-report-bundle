<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
//
//
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * PurchaseRequest Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/purchase-request")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class PurchaseRequestReportApiQueryController
{
    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseRequestReportQuery
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
     * PurchaseRequestReportApiQueryController constructor.
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseRequestReportQuery $domainQuery,
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
    public function purchaseRequestSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->purchaseRequestSummary($request->getQueryParams()),
        ];
    }
    
    /** 
     * @Rest\Get("/export.{format}")
     */
    public function purchaseRequestSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->purchaseRequestSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        switch(strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/purchase-request-report.pdf.twig', [
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
                $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal('center');
                $sheet->setCellValue('A1', 'รายงานเอกสารใบขอซื้อ (PR-DC)');
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
                //
                $sheet->setCellValue('E7', 'วันที่เริ่มต้น : ');
                $sheet->setCellValue('E8', 'วันที่สิ้นสุด : ');
                $sheet->setCellValue('C2', (!isset($filterDetail['project']))? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
                $sheet->setCellValue('C3', (!isset($filterDetail['boq']))? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
                $sheet->setCellValue('C4', (!isset($filterDetail['budgetType']))? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
                $sheet->setCellValue('C5', (!isset($filterDetail['requester']))? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
                $sheet->setCellValue('C6', (!isset($filterDetail['vendor']))? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
                $sheet->setCellValue('C7', (!isset($filterDetail['approved']))? 'ทั้งหมด' : ($filterDetail['approved']? 'อนุมัติ' : 'รออนุมัติ'));
                //
                $sheet->setCellValue('F7', (!isset($filterDetail['start']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
                $sheet->setCellValue('F8', (!isset($filterDetail['end']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));

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
                $fileName = 'RP-DC-PU-PR_rev.2.1.0_'.date('Ymd_His', time()).'.xlsx';
                $temp_file = tempnam(sys_get_temp_dir(), $fileName);
                $writer->save($temp_file);
                $response = new BinaryFileResponse($temp_file);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
            break;
        }
    }
}