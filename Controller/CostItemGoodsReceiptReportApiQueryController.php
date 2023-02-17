<?php

namespace Erp\Bundle\ReportBundle\Controller;

use Erp\Bundle\ReportBundle\Authorization\CostItemGoodsReceiptReportAuthorization;
use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * CostItem GoodsReceipt Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/cost-item-gr")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class CostItemGoodsReceiptReportApiQueryController
{
    use ReportGranterTrait;

    /** @var \Erp\Bundle\ReportBundle\Domain\CQRS\CostItemGoodsReceiptReportQuery */
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
     * @var CostItemGoodsReceiptReportAuthorization
     */
    protected $authorization;

    /**
     * CostItemGoodsReceiptReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\CostItemGoodsReceiptReportQuery $domainQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\CostItemGoodsReceiptReportQuery $domainQuery,
        \Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery $settingQuery,
        \Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery $fileQuery,
        \Twig_Environment $templating,
        \Erp\Bundle\DocumentBundle\Service\PDFService $pdfService,
        CostItemGoodsReceiptReportAuthorization $authorization
    ) {
        $this->domainQuery = $domainQuery;
        $this->settingQuery = $settingQuery;
        $this->fileQuery = $fileQuery;
        $this->templating = $templating;
        $this->pdfService = $pdfService;
        $this->authorization = $authorization;

        $this->grant($this->authorization->access());
    }

    private function reduce(array $items): array
    {
        if (!$this->authorization->quantity()) {
            $items = \array_map(function ($item) {
                unset($item['quantity']);
                return $item;
            }, $items);
        }

        if (!$this->authorization->price()) {
            $items = \array_map(function ($item) {
                unset($item['price']);
                unset($item['total']);
                return $item;
            }, $items);
        }

        return $items;
    }

    private function getGroup(?array $filter = null, ?array &$filterDetail = null)
    {
        return $this->reduce(
            $this->domainQuery->costItemGroupGoodsReceiptSummary($filter, $filterDetail)
        );
    }

    private function getDistribution(?array $filter = null, ?array &$filterDetail = null)
    {
        return $this->reduce(
            $this->domainQuery->costItemDistributionGoodsReceiptSummary($filter, $filterDetail)
        );
    }

    /**
     * @Rest\Get("/group")
     */
    public function costItemGroupGoodsReceiptSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->getGroup($request->getQueryParams()),
        ];
    }

    /**
     * @Rest\Get("/distribution")
     */
    public function costItemDistributionGoodsReceiptSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->getDistribution($request->getQueryParams()),
        ];
    }


    /**
     * @Rest\Get("/distribution/quantity/export.{format}")
     */
    public function costItemDistributionGoodsReceiptSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->getDistribution($request->getQueryParams(), $filterDetail);

        $profile = $this->settingQuery->findOneByCode('profile')->getValue();

        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $this->grant($this->authorization->pdf());

                $view = $this->templating->render('@ErpReport/pdf/cost-item-distribution-gr-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                ]);

                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });

                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
            default:
                throw new BadRequestHttpException("Unsupported '{$format}' format.");
        }
    }

    /**
     * @Rest\Get("/distribution/price/export.{format}")
     */
    public function costItemDistributionGoodsReceiptPriceSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->getDistribution($request->getQueryParams(), $filterDetail);

        $profile = $this->settingQuery->findOneByCode('profile')->getValue();

        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $this->grant($this->authorization->pdf());

                $view = $this->templating->render('@ErpReport/pdf/cost-item-distribution-gr-price-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                ]);

                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });

                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
            default:
                throw new BadRequestHttpException("Unsupported '{$format}' format.");
        }
    }

    /**
     * @Rest\Get("/group/quantity/export.{format}")
     */
    public function costItemGroupGoodsReceiptSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->getGroup($request->getQueryParams(), $filterDetail);

        $profile = $this->settingQuery->findOneByCode('profile')->getValue();

        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $this->grant($this->authorization->pdf());

                $view = $this->templating->render('@ErpReport/pdf/cost-item-group-gr-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                ]);

                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });

                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
            default:
                throw new BadRequestHttpException("Unsupported '{$format}' format.");
        }
    }

    /**
     * @Rest\Get("/group/price/export.{format}")
     */
    public function costItemGroupGoodsReceiptPriceSummaryExportAction(ServerRequestInterface $request, string $format)
    {
        $filterDetail = [];
        $data = $this->getGroup($request->getQueryParams(), $filterDetail);

        $profile = $this->settingQuery->findOneByCode('profile')->getValue();

        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $this->grant($this->authorization->pdf());

                $view = $this->templating->render('@ErpReport/pdf/cost-item-group-gr-price-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'filterDetail' => $filterDetail,
                ]);

                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });

                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
            default:
                throw new BadRequestHttpException("Unsupported '{$format}' format.");
        }
    }
}
