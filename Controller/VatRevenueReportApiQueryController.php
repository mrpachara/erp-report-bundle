<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;


/**
 * vat revenue Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/vat-revenue")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class VatRevenueReportApiQueryController
{

    /** @var \Erp\Bundle\ReportBundle\Domain\CQRS\VatRevenueReportQuery */
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
     * VatRevenueReportQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\VatRevenueReportQuery $domainQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\VatRevenueReportQuery $domainQuery,
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
    public function vatRevenueSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->vatRevenueSummary($request->getQueryParams()),
        ];

    }

    /**
     * @Rest\Get("/export.{format}")
     */
    public function vatRevenueSummaryExportAction(ServerRequestInterface $request)
    {
        $filterDetail = [];
        $data = $this->domainQuery->vatRevenueSummary($request->getQueryParams(), $filterDetail);
        
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        $view = $this->templating->render('@ErpReport/pdf/vat-revenue-report.pdf.twig', [
            'profile' => $profile,
            'model' => $data,
            'filterDetail' => $filterDetail,
        ]);
        
        $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function($mpdf) use ($logo) {
            $mpdf->imageVars['logo'] = $logo;
        });
        
        return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
    }
}
