<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * PurchaseOrder Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/purchase-order")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class PurchaseOrderReportApiQueryController
{
    const docNameEn = 'PURCHASE ORDER';
    const docNameTh = 'ใบสั่งซื้อ';
    const docAbbr = 'PO';

    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseOrderReportQuery
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
     * PurchaseOrderReportApiQueryController constructor.
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseOrderReportQuery $domainQuery,
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
    public function purchaseOrderSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->purchaseOrderSummary($request->getQueryParams()),
        ];
    }

    /**
     * @Rest\Get("/export.{format}")
     */
    public function purchaseOrderSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->purchaseOrderSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/purchase-finance-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                    'docNameEn' => self::docNameEn,
                    'docNameTh' => self::docNameTh,
                    'docAbbr' => self::docAbbr,
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
                break;
            case 'xlsx':
                $fileName = null;
                $tempFile = $this->excelReport->docReportExcel(
                    $data,
                    $filterDetail,
                    self::docNameEn,
                    self::docNameTh,
                    self::docAbbr,
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

    /**
     * @Rest\Get("/cost/export.{format}")
     */
    public function purchaseOrderCostSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->purchaseOrderSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/purchase-finance-cost-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                    'docNameEn' => self::docNameEn,
                    'docNameTh' => self::docNameTh,
                    'docAbbr' => self::docAbbr,
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
                break;
            case 'xlsx':
                $fileName = null;
                $tempFile = $this->excelReport->costReportExcel(
                    $data,
                    $filterDetail,
                    self::docNameEn,
                    self::docNameTh,
                    self::docAbbr,
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
