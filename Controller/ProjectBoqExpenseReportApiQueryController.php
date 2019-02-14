<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Project Boq Expense Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/project-boq-ep")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class ProjectBoqExpenseReportApiQueryController
{
/*
2_2d6m1egnbuhw0cgk888owwskk0w4c0wg0oksow8ogg4www0co8
26ijx5m68clc4kcs08ckcckwo8k4ow8og4cow4wcwgoowwk40k
 */

/** @var \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqExpenseReportQuery */
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
     * ProjectBoqExpenseReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqExpenseReportQuery $domainQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqExpenseReportQuery $domainQuery,
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
    public function projectBoqEPSummaryAction(ServerRequestInterface $request, $id)
    {
        return [
            'data' => $this->domainQuery->projectBoqEPSummary($id),
        ];

    }

    /**
     * @Rest\Get("/{id}/{idBoq}")
     */
    public function projectBoqEPSummaryEachAction(ServerRequestInterface $request, $id, $idBoq)
    {
        return [
            'data' => $this->domainQuery->projectBoqEPSummaryEach($id, $idBoq),
        ];
        
    }
    
    /**
     * @Rest\Get("/{id}/{idBoq}/export.{format}")
     */
    public function projectBoqEPSummaryExportAction(ServerRequestInterface $request, $id, $idBoq)
    {
        $data = $this->domainQuery->projectBoqEPSummaryEach($id, $idBoq);
        
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        $view = $this->templating->render('@ErpReport/pdf/project-boq-ep-report.pdf.twig', [
            'profile' => $profile,
            'model' => $data,
        ]);
        
        $output = $this->pdfService->generatePdf($view, ['format' => 'A4-L'], function($mpdf) use ($logo) {
            $mpdf->imageVars['logo'] = $logo;
        });
            
            return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
    }
}