<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;


/**
 * vat PurchaseOrer Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/vat-purchase-order")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class VatPurchaseOrderReportApiQueryController
{

    /** @var \Erp\Bundle\ReportBundle\Domain\CQRS\VatPurchaseOrderReportQuery */
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
     * VatPurchaseOrderReportQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\VatPurchaseOrderReportQuery $domainQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\VatPurchaseOrderReportQuery $domainQuery,
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
    public function vatPurchaseOrderSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->vatPurchaseOrderSummary($request->getQueryParams()),
        ];

    }

    /**
     * @Rest\Get("/export.{format}")
     */
    public function vatPurchaseOrderSummaryExportAction(ServerRequestInterface $request)
    {
        $filterDetail = [];
        $data = $this->domainQuery->vatPurchaseOrderSummary($request->getQueryParams(), $filterDetail);
        
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        $view = $this->templating->render('@ErpReport/pdf/vat-purchase-order-report.pdf.twig', [
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
