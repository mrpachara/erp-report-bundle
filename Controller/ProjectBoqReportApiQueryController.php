<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Project Boq Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/project-boq")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class ProjectBoqReportApiQueryController
{
    /*
2_2d6m1egnbuhw0cgk888owwskk0w4c0wg0oksow8ogg4www0co8
26ijx5m68clc4kcs08ckcckwo8k4ow8og4cow4wcwgoowwk40k
 */

    /**
     * @var \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqReportQuery
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
     * @var ProjectBoqExcelReportHelper
     */
    protected $excelReport;

    /**
     * ProjectBoqReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqReportQuery $domainQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectBoqReportQuery $domainQuery,
        \Erp\Bundle\SettingBundle\Domain\CQRS\SettingQuery $settingQuery,
        \Erp\Bundle\CoreBundle\Domain\CQRS\TempFileItemQuery $fileQuery,
        \Twig_Environment $templating,
        \Erp\Bundle\DocumentBundle\Service\PDFService $pdfService,
        ProjectBoqExcelReportHelper $excelReport
    ) {
        $this->domainQuery = $domainQuery;
        $this->settingQuery = $settingQuery;
        $this->fileQuery = $fileQuery;
        $this->templating = $templating;
        $this->pdfService = $pdfService;
        $this->excelReport = $excelReport;
    }

    /**
     * @Rest\Get("/{id}")
     */
    public function projectBoqSummaryAction(ServerRequestInterface $request, $id)
    {
        return [
            'data' => $this->domainQuery->projectBoqSummary($id),
        ];
    }

    /**
     * @Rest\Get("/{id}/{idBoq}")
     */
    public function projectBoqSummaryEachAction(ServerRequestInterface $request, $id, $idBoq)
    {
        return [
            'data' => $this->domainQuery->projectBoqSummaryEach($id, $idBoq),
        ];
    }

    /**
     * @Rest\Get("/{id}/{idBoq}/export.{format}")
     */
    public function projectBoqSummaryExportAction(ServerRequestInterface $request, $id, $idBoq, string $format)
    {
        $data = $this->domainQuery->projectBoqSummaryEach($id, $idBoq);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                $view = $this->templating->render('@ErpReport/pdf/project-boq-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'extendHeader' => ' by PU',
                    'extendName' => 'PU',
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4-L'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
                break;
            case 'xlsx':
                $qs = $request->getQueryParams();
                $withFormular = empty($qs['raw']);

                $tempFile = $this->excelReport->reportExcel($data, $withFormular, $fileName);
                $response = new BinaryFileResponse($tempFile);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
                break;
        }
    }
}
