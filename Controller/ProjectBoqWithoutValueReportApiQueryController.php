<?php

namespace Erp\Bundle\ReportBundle\Controller;

use Erp\Bundle\ReportBundle\Authorization\ProjectBoqWithoutValueReportAuthorization;
use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Project Boq Without Value Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/project-boq-without-value/{idProject}")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class ProjectBoqWithoutValueReportApiQueryController
{
    use ReportGranterTrait;

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
     * @var ProjectBoqWithoutValueReportAuthorization
     */
    protected $authorization;

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
        ProjectBoqExcelReportHelper $excelReport,
        ProjectBoqWithoutValueReportAuthorization $authorization
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
    public function projectBoqSummaryAction(ServerRequestInterface $request, $idProject)
    {
        return [
            'data' => $this->domainQuery->projectBoqWithoutValueSummary($idProject),
        ];
    }

    /**
     * @Rest\Get("/{id}")
     */
    public function projectBoqSummaryEachAction(ServerRequestInterface $request, $idProject, $id)
    {
        return [
            'data' => $this->domainQuery->projectBoqWithoutValueSummaryEach($idProject, $id),
        ];
    }

    /**
     * @Rest\Get("/{id}/export.{format}")
     */
    public function projectBoqSummaryExportAction(ServerRequestInterface $request, $idProject, $id, string $format)
    {
        $data = $this->domainQuery->projectBoqWithoutValueSummaryEach($idProject, $id);
        $profile = $this->settingQuery->findOneByCode('profile')->getValue();
        $logo = null;
        if (!empty($profile['logo'])) {
            $logo = stream_get_contents($this->fileQuery->get($profile['logo'])->getData());
        }

        switch (strtolower($format)) {
            case 'pdf':
                //$this->grant($this->authorization->pdf());

                $view = $this->templating->render('@ErpReport/pdf/project-boq-report.pdf.twig', [
                    'profile' => $profile,
                    'model' => $data,
                    'extendHeader' => '',
                    'extendName' => 'WOV',
                ]);
                $output = $this->pdfService->generatePdf($view, ['format' => 'A4'], function ($mpdf) use ($logo) {
                    $mpdf->imageVars['logo'] = $logo;
                });
                return new \TFox\MpdfPortBundle\Response\PDFResponse($output);
                break;
            case 'xlsx':
                //$this->grant($this->authorization->excel());

                $qs = $request->getQueryParams();
                $withFormular = empty($qs['raw']);

                $tempFile = $this->excelReport->reportExcel($data, $withFormular, $fileName, true);
                $response = new BinaryFileResponse($tempFile);
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, null === $fileName ? $response->getFile()->getFilename() : $fileName);
                return $response;
                break;
            default:
                throw new BadRequestHttpException("Unsupported '{$format}' format.");
        }
    }
}
