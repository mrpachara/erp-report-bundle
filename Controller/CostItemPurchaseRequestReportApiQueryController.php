<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;


/**
 * CostItem PurchaseRequest Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/cost-item-pr")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class CostItemPurchaseRequestReportApiQueryController
{

    /** @var \Erp\Bundle\ReportBundle\Domain\CQRS\CostItemPurchaseRequestReportQuery */
    private $domainQuery;

    /**
     * CostItemPurchaseRequestReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\CostItemPurchaseRequestReportQuery $domainQuery
     */
    public function __construct(\Erp\Bundle\ReportBundle\Domain\CQRS\CostItemPurchaseRequestReportQuery $domainQuery)
    {
        $this->domainQuery = $domainQuery;
    }

    /**
     * @Rest\Get("/distribution")
     */
    public function costItemDistributionPurchaseRequestSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->costItemDistributionPurchaseRequestSummary($request->getQueryParams()),
        ];

    }

}
