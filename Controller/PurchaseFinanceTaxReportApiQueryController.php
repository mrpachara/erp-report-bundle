<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * tax Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/tax-purchase")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class PurchaseFinanceTaxReportApiQueryController
{
    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseFinanceReportQuery
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
     * PurchaseFinanceTaxReportQueryController constructor.
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseFinanceReportQuery $domainQuery,
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
    public function summarizeTaxAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->summarize($request->getQueryParams()),
        ];
    }

    /**
     * @Rest\Get("/export.{format}")
     */
    public function summarizeTaxExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->summarize($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/purchase-finance-tax-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                    'docNameEn' => PurchaseFinanceReportApiQueryController::docNameEn,
                    'docNameTh' => PurchaseFinanceReportApiQueryController::docNameTh,
                    'docAbbr' => PurchaseFinanceReportApiQueryController::docAbbr,
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
                break;
            case 'xlsx':
                $tempFile = $this->excelReport->taxReportExcel(
                    $data,
                    $filterDetail,
                    PurchaseFinanceReportApiQueryController::docNameEn,
                    PurchaseFinanceReportApiQueryController::docNameTh,
                    PurchaseFinanceReportApiQueryController::docAbbr,
                    $fileName
                );

                $response = new BinaryFileResponse($tempFile);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
                break;
        }
    }
}
