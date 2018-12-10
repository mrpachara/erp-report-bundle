<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;


/**
 * deposit expense Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/deposit-expense")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class DepositExpenseReportApiQueryController
{

/** @var \Erp\Bundle\ReportBundle\Domain\CQRS\DepositExpenseReportQuery */
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
     * DepositExpenseReportQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\DepositExpenseReportQuery $domainQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\DepositExpenseReportQuery $domainQuery,
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
    public function depositExpenseSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->depositExpenseSummary($request->getQueryParams()),
        ];

    }

    /**
     * @Rest\Get("/export.{format}")
     */
    public function depositExpenseSummaryExportAction(ServerRequestInterface $request)
    {
        $filterDetail = [];
        $data = $this->domainQuery->depositExpenseSummary($request->getQueryParams(), $filterDetail);
        
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        
        $logo = null;
        if(!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }
        
        $view = $this->templating->render('@ErpReport/pdf/deposit-expense-report.pdf.twig', [
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
