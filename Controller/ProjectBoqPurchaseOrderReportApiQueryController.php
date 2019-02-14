<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Project Boq Purchase Order Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/project-boq-po")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class ProjectBoqPurchaseOrderReportApiQueryController
{
/*
2_2d6m1egnbuhw0cgk888owwskk0w4c0wg0oksow8ogg4www0co8
26ijx5m68clc4kcs08ckcckwo8k4ow8og4cow4wcwgoowwk40k
 */

/** @var \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqPurchaseOrderReportQuery */
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
     * ProjectBoqPurchaseOrderReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqPurchaseOrderReportQuery $domainQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqPurchaseOrderReportQuery $domainQuery,
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
    public function projectBoqPOSummaryAction(ServerRequestInterface $request, $id)
    {
        return [
            'data' => $this->domainQuery->projectBoqPOSummary($id),
        ];

    }

    /**
     * @Rest\Get("/{id}/{idBoq}")
     */
    public function projectBoqPOSummaryEachAction(ServerRequestInterface $request, $id, $idBoq)
    {
        return [
            'data' => $this->domainQuery->projectBoqPOSummaryEach($id, $idBoq),
        ];
        
    }
    
    /**
     * @Rest\Get("/{id}/{idBoq}/export.{format}")
     */
    public function projectBoqPOSummaryExportAction(ServerRequestInterface $request, $id, $idBoq)
    {
        $data = $this->domainQuery->projectBoqPOSummaryEach($id, $idBoq);
        
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        $view = $this->templating->render('@ErpReport/pdf/project-boq-po-report.pdf.twig', [
            'profile' => $profile,
            'model' => $data,
        ]);
        
        $output = $this->pdfService->generatePdf($view, ['format' => 'A4-L'], function($mpdf) use ($logo) {
            $mpdf->imageVars['logo'] = $logo;
        });
            
            return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
    }
}