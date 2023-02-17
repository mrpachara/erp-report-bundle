<?php

namespace Erp\Bundle\ReportBundle\Controller;

use Erp\Bundle\ReportBundle\Authorization\ProjectIncomeReportAuthorization;
use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Project Income Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/project-income")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class ProjectIncomeReportApiQueryController
{
    use ReportGranterTrait;

    /** @var \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectIncomeReportQuery */
    private $summaryQuery;

    /**
     * @var ProjectIncomeReportAuthorization
     */
    protected $authorization;

    /**
     * ProjectReportApiQueryController constructor.
     * @param \Erp\Bundle\MasterBundle\Domain\CQRS\ProjectQuery $domainQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectIncomeReportQuery $summaryQuery,
        ProjectIncomeReportAuthorization $authorization
    ) {
        $this->summaryQuery = $summaryQuery;
        $this->authorization = $authorization;

        $this->grant($this->authorization->access());
    }

    /**
     * @Rest\Get("/{id}")
     */
    public function projectIncomeReportAction(ServerRequestInterface $request, $id)
    {
        return [
            'data' => $this->summaryQuery->projectIncomeSummary($id),
        ];
    }
}
