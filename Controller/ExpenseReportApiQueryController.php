<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
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
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        switch(strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/expense-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
            case 'xlsx':
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
				$sheet->mergeCells("A1:H1");
                $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal('center');
                $sheet->setCellValue('A1', 'รายงานเอกสารใบจ่ายเงิน EP');
                $sheet->getStyle('A2:A8')->getAlignment()->setHorizontal('right');
                $sheet->setCellValue('A2', 'โครงการ : ');
                $sheet->mergeCells("B2:H2");
                $sheet->setCellValue('B2', (!isset($filterDetail['project']))? 'ทั้งหมด' : "[{$filterDetail['project']->getCode()}] {$filterDetail['project']->getName()}");
                $sheet->setCellValue('A3', 'งบประมาณ : ');
                $sheet->mergeCells("B3:H3");
                $sheet->setCellValue('B3', (!isset($filterDetail['boq']))? 'ทั้งหมด' : "{$filterDetail['boq']->getName()}");
                $sheet->setCellValue('A4', 'ประเภท : ');
                $sheet->mergeCells("B4:H4");
                $sheet->setCellValue('B4', (!isset($filterDetail['budgetType']))? 'ทั้งหมด' : "{$filterDetail['budgetType']->getName()}");
                $sheet->setCellValue('A5', 'ผู้ต้องการ : ');
                $sheet->mergeCells("B5:H5");
                $sheet->setCellValue('B5', (!isset($filterDetail['requester']))? 'ทั้งหมด' : "[{$filterDetail['requester']->getCode()}] {$filterDetail['requester']->getName()}");
                $sheet->setCellValue('A6', 'ผู้จำหน่าย : ');
                $sheet->mergeCells("B6:H6");
                $sheet->setCellValue('B6', (!isset($filterDetail['vendor']))? 'ทั้งหมด' : "[{$filterDetail['vendor']->getCode()}] {$filterDetail['vendor']->getName()}");
                $sheet->setCellValue('A7', 'สถานะ : ');
                $sheet->setCellValue('B7', (!isset($filterDetail['approved']))? 'ทั้งหมด' : ($filterDetail['approved']? 'อนุมัติ' : 'รออนุมัติ'));
                $sheet->getStyle('G7:G8')->getAlignment()->setHorizontal('right');
                $sheet->setCellValue('G7', 'วันที่เริ่มต้น : ');
                $sheet->getStyle('H7')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                $sheet->setCellValue('H7', (!isset($filterDetail['start']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['start']));
                $sheet->setCellValue('G8', 'วันที่สิ้นสุด : ');
                $sheet->getStyle('H8')->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                $sheet->setCellValue('H8', (!isset($filterDetail['end']))? 'ทั้งหมด' : Date::PHPToExcel($filterDetail['end']));
				$sheet->getStyle('A9:H10')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheet->getStyle('A9:H10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A9:H10')->getFill()->getStartColor()->setRGB('DCDCDC');
				$sheet->getStyle('A9:H10')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A9:H10')->getAlignment()->setVertical('center');
                $sheet->mergeCells('A9:A10');
                $sheet->setCellValue('A9', 'ลำดับ');
                $sheet->mergeCells('B9:C9');
                $sheet->setCellValue('B9', 'เอกสาร');
                $sheet->setCellValue('B10', 'เลขที่');
                $sheet->setCellValue('C10', 'สถานะ');
                $sheet->mergeCells('D9:F9');
                $sheet->setCellValue('D9', 'โครงการ');
                $sheet->setCellValue('D10', 'รหัส');
                $sheet->setCellValue('E10', 'งบประมาณ');
                $sheet->setCellValue('F10', 'ประเภท');
                $sheet->mergeCells('G9:G10');
                $sheet->setCellValue('G9', 'ผู้ต้องการ');
                $sheet->mergeCells('H9:H10');
                $sheet->setCellValue('H9', 'ผู้จำหน่าย');
				
                $row = 11;
                $count = 1;
                foreach($data as $item) {
                    $sheet->getStyle('A')->getAlignment()->setHorizontal('center');
					$sheet->getStyle('C:D')->getAlignment()->setHorizontal('center');
                    $sheet->getStyle('G:H')->getAlignment()->setHorizontal('center');
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
                $fileName = 'ep-report-'.date('Ymd_His', time()).'.xlsx';
                $temp_file = tempnam(sys_get_temp_dir(), $fileName);
                $writer->save($temp_file);
                $response = new BinaryFileResponse($temp_file);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
        }
    }
}