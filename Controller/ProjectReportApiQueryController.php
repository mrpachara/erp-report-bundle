<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Project Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/project")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class ProjectReportApiQueryController
{

    /** @var \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectReportQuery */
    private $summaryQuery;

    /**
     * ProjectReportApiQueryController constructor.
     * @param \Erp\Bundle\MasterBundle\Domain\CQRS\ProjectQuery $domainQuery
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectReportQuery $summaryQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectReportQuery $summaryQuery
    )
    {
        $this->summaryQuery = $summaryQuery;
    }

    /**
     * @Rest\Get("/{id}")
     */
    public function projectReportAction(ServerRequestInterface $request, $id)
    {
        return [
            'data' => $this->summaryQuery->projectSummary($id),
        ];

    }
}
