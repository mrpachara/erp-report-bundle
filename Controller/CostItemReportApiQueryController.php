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
     * @Rest\Get("")
     */
    public function costItemSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->costItemSummary($request->getQueryParams()),
        ];

    }

}