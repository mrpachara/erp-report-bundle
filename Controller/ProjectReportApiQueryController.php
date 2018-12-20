<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Project Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/project")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class ProjectReportApiQueryController
{

    /** @var \Erp\Bundle\MasterBundle\Domain\CQRS\ProjectQuery */
    private $domainQuery;

    /** @var \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectReportQuery */
    private $summaryQuery;

    /**
     * ProjectReportApiQueryController constructor.
     * @param \Erp\Bundle\MasterBundle\Domain\CQRS\ProjectQuery $domainQuery
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectReportQuery $summaryQuery
     */
    public function __construct(
        \Erp\Bundle\MasterBundle\Domain\CQRS\ProjectQuery $domainQuery,
        \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectReportQuery $summaryQuery
    )
    {
        $this->domainQuery = $domainQuery;
        $this->summaryQuery = $summaryQuery;
    }

    /**
     * @Rest\Get("/{id}/boq")
     */
    public function purchaseOrderSummaryAction(ServerRequestInterface $request, $id)
    {
        /** @var Project */
        $project = $this->domainQuery->find($id);

        if(empty($project)) throw new NotFoundHttpException();

        return [
            'project' => $project,
            'data' => $this->summaryQuery->boqSummary($project->getId()),
        ];

    }
}
