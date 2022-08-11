<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Project Contract Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/project-contract")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class ProjectContractReportApiQueryController
{
/*
2_2d6m1egnbuhw0cgk888owwskk0w4c0wg0oksow8ogg4www0co8
26ijx5m68clc4kcs08ckcckwo8k4ow8og4cow4wcwgoowwk40k
 */

/** @var \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectContractReportQuery */
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
     * ProjectContractReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectContractReportQuery $domainQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectContractReportQuery $domainQuery,
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
     * @Rest\Get("/{id}")
     */
    public function projectContractSummaryAction(ServerRequestInterface $request, $id)
    {
        $query = $request->getQueryParams();
        $excepts = (empty($query['excepts']))? null : $query['excepts'];
        return [
            'data' => $this->domainQuery->projectContractSummary($id , $excepts),
        ];

    }

    /**
     * @Rest\Get("/{id}/{idBoq}")
     */
    public function projectContractSummaryEachAction(ServerRequestInterface $request, $id, $idBoq)
    {
        return [
            'data' => $this->domainQuery->projectContractSummaryEach($id, $idBoq),
        ];
        
    }
    
    /**
     * @Rest\Get("/{id}/{idBoq}/delivery-note/export.{format}")
     */
    public function projectContractDeliverySummaryExportAction(ServerRequestInterface $request, $id, $idBoq)
    {
        $data = $this->domainQuery->projectContractSummaryEach($id, $idBoq);
        
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        $view = $this->templating->render('@ErpReport/pdf/project-contract-delivery-note-report.pdf.twig', [
            'profile' => $profile,
            'model' => $data,
        ]);
        
        $output = $this->pdfService->generatePdf($view, ['format' => 'A4-L'], function($mpdf) use ($logo) {
            $mpdf->imageVars['logo'] = $logo;
        });
            
            return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
    }
    
    /**
     * @Rest\Get("/{id}/{idBoq}/billing-note/export.{format}")
     */
    public function projectContractBillingSummaryExportAction(ServerRequestInterface $request, $id, $idBoq, $format)
    {
        $data = array_values(array_filter($this->domainQuery->projectContractSummaryEach($id, $idBoq), function($item) {
            return 'billingnote' === $item['dtype'] || 'taxinvoice' === $item['dtype'] || 'revenue' === $item['dtype'];
        }));
        
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        switch(strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/project-contract-billing-note-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                ]);
                
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4-L'], function($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
            break;
            case 'xlsx':
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();








                // Create your Office 2007 Excel (XLSX Format)
                $writer = new Xlsx($spreadsheet);
                $writer->setPreCalculateFormulas(false);

                // Create a Temporary file in the system
                $fileName = 'RP-MT-CI-Quantity-PR_rev.2.1.0_'.date('Ymd_His', time()).'.xlsx';
                $temp_file = tempnam(sys_get_temp_dir(), $fileName);
                
                // Create the excel file in the tmp directory of the system
                $writer->save($temp_file);
                
                $response = new BinaryFileResponse($temp_file);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
            break;
        }
    }
    
    /**
     * @Rest\Get("/{id}/{idBoq}/tax-invoice/export.{format}")
     */
    public function projectContractTaxInvoiceSummaryExportAction(ServerRequestInterface $request, $id, $idBoq, $format)
    {
        $data = array_values(array_filter($this->domainQuery->projectContractSummaryEach($id, $idBoq), function($item) {
            return 'taxinvoice' === $item['dtype'] || 'revenue' === $item['dtype'];
        }));
        
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        $view = $this->templating->render('@ErpReport/pdf/project-contract-tax-invoice-report.pdf.twig', [
            'profile' => $profile,
            'model' => $data,
        ]);
        
        $output = $this->pdfService->generatePdf($view, ['format' => 'A4-L'], function($mpdf) use ($logo) {
            $mpdf->imageVars['logo'] = $logo;
        });
            
            return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
    }
    
    /**
     * @Rest\Get("/{id}/{idBoq}/revenue/export.{format}")
     */
    public function projectContractRevenueSummaryExportAction(ServerRequestInterface $request, $id, $idBoq, $format)
    {
        $data = array_values(array_filter($this->domainQuery->projectContractSummaryEach($id, $idBoq), function($item) {
            return 'revenue' === $item['dtype'];
        }));
        
//         $filtereds = [];
//         foreach($data as $item) {
//             if('revenue' === $item['dtype']) $filtereds[] = item;
//         }
        
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        $view = $this->templating->render('@ErpReport/pdf/project-contract-revenue-report.pdf.twig', [
            'profile' => $profile,
            'model' => $data,
        ]);
        
        $output = $this->pdfService->generatePdf($view, ['format' => 'A4-L'], function($mpdf) use ($logo) {
            $mpdf->imageVars['logo'] = $logo;
        });
            
            return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
    }
    
    /**
     * @Rest\Get("/{id}/{idBoq}/contract/export.{format}")
     */
    public function projectContractProjectSummaryExportAction(ServerRequestInterface $request, $id, $idBoq, $format)
    {
        $data = array_values(array_filter($this->domainQuery->projectContractSummaryEach($id, $idBoq), function($item) {
            return 'revenue' === $item['dtype'] && $item['approved'] == 1;
        }));
            
            //         $filtereds = [];
            //         foreach($data as $item) {
            //             if('revenue' === $item['dtype']) $filtereds[] = item;
            //         }
            
            $profile = $this->settingQuery->findOneByCode('profile')->getValue();
            
            $logo = null;
            if(!empty($profile['logo'])) {
                $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
            }
            
            $view = $this->templating->render('@ErpReport/pdf/project-contract-report.pdf.twig', [
                'profile' => $profile,
                'model' => $data,
            ]);
            
            $output = $this->pdfService->generatePdf($view, ['format' => 'A4-L'], function($mpdf) use ($logo) {
                $mpdf->imageVars['logo'] = $logo;
            });
                
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
    }
}