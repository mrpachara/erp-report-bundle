<?php

namespace Erp\Bundle\ReportBundle\Controller;

use Erp\Bundle\ReportBundle\Authorization\RevenueReportAuthorization;
use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Revenue PayMethod Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/pay-method-revenue")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class RevenuePayMethodReportApiQueryController
{
    use ReportGranterTrait;

    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\PayMethodRevenueReportQuery
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
     * @var IncomeFinanceExcelReportHelper
     */
    protected $excelReport;

    /**
     * @var RevenueReportAuthorization
     */
    protected $authorization;

    /**
     * RevenuePayMethodReportApiQueryController constructor.
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\PayMethodRevenueReportQuery $domainQuery,
        \Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery $settingQuery,
        \Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery $fileQuery,
        \Twig_Environment $templating,
        \Erp\Bundle\DocumentBundle\Service\PDFService $pdfService,
        IncomeFinanceExcelReportHelper $excelReport,
        RevenueReportAuthorization $authorization
    ) {
        $this->domainQuery = $domainQuery;
        $this->settingQuery = $settingQuery;
        $this->fileQuery = $fileQuery;
        $this->templating = $templating;
        $this->pdfService = $pdfService;
        $this->excelReport = $excelReport;
        $this->authorization = $authorization;

        $this->grant($this->authorization->access());
    }

    /**
     * @Rest\Get("")
     */
    public function payMethodRevenueSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->payMethodRevenueSummary($request->getQueryParams()),
        ];
    }

    /**
     * @Rest\Get("/export.{format}")
     */
    public function payMethodRevenueSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->payMethodRevenueSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $this->grant($this->authorization->pdf());

                $view = $this->templating->render('@ErpReport/pdf/income-finance-pay-method-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                    'docNameEn' => RevenueReportApiQueryController::docNameEn,
                    'docNameTh' => RevenueReportApiQueryController::docNameTh,
                    'docAbbr' => RevenueReportApiQueryController::docAbbr,
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
                break;
            case 'xlsx':
                $this->grant($this->authorization->excel());

                $tempFile = $this->excelReport->payMethodReportExcel(
                    $data,
                    $filterDetail,
                    RevenueReportApiQueryController::docNameEn,
                    RevenueReportApiQueryController::docNameTh,
                    RevenueReportApiQueryController::docAbbr,
                    $fileName
                );

                $response = new BinaryFileResponse($tempFile);
                $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_INLINE,
                    null === $fileName ? $response->getFile()->getFilename() : $fileName
                );
                return $response;
                break;
        }
    }
}
