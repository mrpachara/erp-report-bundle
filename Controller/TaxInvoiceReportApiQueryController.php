<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * TaxInvoice Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/tax-invoice")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class TaxInvoiceReportApiQueryController
{
    const docNameEn = 'TAX INVOICE';
    const docNameTh = 'ใบเสร็จรับเงิน/ใบกำกับภาษี';
    const docAbbr = 'TI';

    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\TaxInvoiceReportQuery
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
     * TaxInvoiceReportApiQueryController constructor.
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\TaxInvoiceReportQuery $domainQuery,
        \Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery $settingQuery,
        \Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery $fileQuery,
        \Twig_Environment $templating,
        \Erp\Bundle\DocumentBundle\Service\PDFService $pdfService,
        IncomeFinanceExcelReportHelper $excelReport
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
    public function taxInvoiceSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->taxInvoiceSummary($request->getQueryParams()),
        ];
    }

    /**
     * @Rest\Get("/export.{format}")
     */
    public function taxInvoiceSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->taxInvoiceSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/income-finance-report.pdf.twig', [
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
    public function taxInvoiceCostSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->domainQuery->taxInvoiceSummary($request->getQueryParams(), $filterDetail);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/income-finance-cost-report.pdf.twig', [
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
