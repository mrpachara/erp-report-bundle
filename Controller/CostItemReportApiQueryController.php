<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;


/**
 * CostItem Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/cost-item")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class CostItemReportApiQueryController
{

    /** @var \Erp\Bundle\ReportBundle\Domain\CQRS\CostItemReportQuery */
    private $domainQuery;

    /**
     * CostItemReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\CostItemReportQuery $domainQuery
     */
    public function __construct(\Erp\Bundle\ReportBundle\Domain\CQRS\CostItemReportQuery $domainQuery)
    {
        $this->domainQuery = $domainQuery;
    }

    /**
     * @Rest\Get("/group")
     */
    public function costItemGroupSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->costItemGroupSummary($request->getQueryParams()),
        ];

    }

    /**
     * @Rest\Get("/distribution")
     */
    public function costItemDistributionSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->costItemDistributionSummary($request->getQueryParams()),
        ];

    }

}
