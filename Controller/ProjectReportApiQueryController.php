<?php

namespace Erp\Bundle\ReportBundle\Controller;

use Erp\Bundle\ReportBundle\Authorization\ProjectPurchaseReportAuthorization;
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
    use ReportGranterTrait;

    /** @var \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectReportQuery */
    private $summaryQuery;

    /**
     * @var ProjectPurchaseReportAuthorization
     */
    protected $authorization;

    /**
     * ProjectReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectReportQuery $summaryQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectReportQuery $summaryQuery,
        ProjectPurchaseReportAuthorization $authorization
    ) {
        $this->summaryQuery = $summaryQuery;
        $this->authorization = $authorization;

        $this->grant($this->authorization->access());
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
