<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;


/**
 * vat Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/warranty")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class WarrantyReportApiQueryController
{

/** @var \Erp\Bundle\ReportBundle\Domain\CQRS\WarrantyReportQuery */
    private $domainQuery;

    /**
     * TaxReportQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\WarrantyReportQuery $domainQuery
     */
    public function __construct(\Erp\Bundle\ReportBundle\Domain\CQRS\WarrantyReportQuery $domainQuery)
    {
        $this->domainQuery = $domainQuery;
    }

    /**
     * @Rest\Get("")
     */
    public function warrantySummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->warrantySummary($request->getQueryParams()),
        ];

    }

}
