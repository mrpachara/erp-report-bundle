<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;


/**
 * requester Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/requester")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class RequesterReportApiQueryController
{

/** @var \Erp\Bundle\ReportBundle\Domain\CQRS\RequesterReportQuery */
    private $domainQuery;

    /**
     * PurchaseOrderReportApiQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\RequesterReportQuery $domainQuery
     */
    public function __construct(\Erp\Bundle\ReportBundle\Domain\CQRS\RequesterReportQuery $domainQuery)
    {
        $this->domainQuery = $domainQuery;
    }

    /**
     * @Rest\Get("")
     */
    public function requesterSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->requesterSummary($request->getQueryParams()),
        ];

    }

}
