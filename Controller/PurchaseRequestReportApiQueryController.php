<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

    /** @var \Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseRequestReportQuery */
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
     *
     * @var \Twig_Environment
     */
    protected $templating;
    
    /**
     * @var \Erp\Bundle\DocumentBundle\Service\PDFService
     */
    protected $pdfService = null;
    
    /**
     * PurchaseRequestReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseRequestReportQuery $domainQuery
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
            case 'xlsx':
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                //$sheet->setCellValue('A1', 'Hello World !');
                $row = 1;
                $count = 1;
                foreach($data as $item) {
                    $sheet->setCellValue('A'.$row, $item['code']);
                    $sheet->setCellValue('B'.$row, $item['approved']);
                    $sheet->setCellValue('C'.$row, $item['project']);
                    $sheet->setCellValue('D'.$row, $item['boq']);
                    $sheet->setCellValue('E'.$row, $item['budgetType']);
                    $sheet->setCellValue('F'.$row, $item['requester']);
                    $sheet->setCellValue('G'.$row, $item['vendor']);
                    
                    $row++;
                    $count++;
                }
                
                // Create your Office 2007 Excel (XLSX Format)
                $writer = new Xlsx($spreadsheet);
                
                // Create a Temporary file in the system
                $fileName = 'pr-report-'.date('Ymd_His', time()).'.xlsx';
                $temp_file = tempnam(sys_get_temp_dir(), $fileName);
                
                // Create the excel file in the tmp directory of the system
                $writer->save($temp_file);
                
                $response = new BinaryFileResponse($temp_file);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
        }
    }
}