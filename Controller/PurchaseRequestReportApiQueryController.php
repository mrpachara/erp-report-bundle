<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;


/**
 * PurchaseRequest Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/purchase-request")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class PurchaseRequestReportApiQueryController
{

    /** @var \Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseRequestReportQuery */
    private $domainQuery;
    
    /**
     * PurchaseRequestReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseRequestReportQuery $domainQuery
     */
    public function __construct(\Erp\Bundle\ReportBundle\Domain\CQRS\PurchaseRequestReportQuery $domainQuery)
    {
        $this->domainQuery = $domainQuery;
    }

    /**
     * @Rest\Get("")
     */
    public function purchaseRequestSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->purchaseRequestSummary($request->getQueryParams()),
        ];
        
    }

}