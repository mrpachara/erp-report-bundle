<?php

namespace Erp\Bundle\ReportBundle\Controller;

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

    /** @var \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectIncomeReportQuery */
    private $summaryQuery;

    /**
     * ProjectReportApiQueryController constructor.
     * @param \Erp\Bundle\MasterBundle\Domain\CQRS\ProjectQuery $domainQuery
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectIncomeReportQuery $summaryQuery
     */
    public function __construct(
        \Erp\Bundle\ReportBundle\Domain\CQRS\ProjectIncomeReportQuery $summaryQuery
    )
    {
        $this->summaryQuery = $summaryQuery;
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
