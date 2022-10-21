<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Expense Deposit Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/deposit-expense")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class ExpenseDepositReportApiQueryController
{
    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\DepositExpenseReportQuery
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
     * @var PurchaseFinanceExcelReportHelper
     */
    protected $excelReport;

    /**
     * ExpenseDepositReportApiQueryController constructor.
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\DepositExpenseReportQuery $domainQuery,
        \Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery $settingQuery,
        \Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery $fileQuery,
        \Twig_Environment $templating,
        \Erp\Bundle\DocumentBundle\Service\PDFService $pdfService,
        PurchaseFinanceExcelReportHelper $excelReport
    ) {
        $this->domainQuery = $domainQuery;
        $this->settingQuery = $settingQuery;
        $this->fileQuery = $fileQuery;
        $this->templating = $templating;
        $this->pdfService = $pdfService;
        $this->excelReport = $excelReport;
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
    public function depositExpenseSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->depositExpenseSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/purchase-finance-deposit-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                    'docNameEn' => ExpenseReportApiQueryController::docNameEn,
                    'docNameTh' => ExpenseReportApiQueryController::docNameTh,
                    'docAbbr' => ExpenseReportApiQueryController::docAbbr,
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
                break;
            case 'xlsx':
                $tempFile = $this->excelReport->depositReportExcel(
                    $data,
                    $filterDetail,
                    ExpenseReportApiQueryController::docNameEn,
                    ExpenseReportApiQueryController::docNameTh,
                    ExpenseReportApiQueryController::docAbbr,
                    $fileName
                );

                $response = new BinaryFileResponse($tempFile);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
                break;
        }
    }
}
