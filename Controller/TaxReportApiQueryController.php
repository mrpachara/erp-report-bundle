<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;


/**
 * tax Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/tax")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class TaxReportApiQueryController
{

/** @var \Erp\Bundle\ReportBundle\Domain\CQRS\TaxReportQuery */
    private $domainQuery;

    /**
     * TaxReportQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\TaxReportQuery $domainQuery
     */
    public function __construct(\Erp\Bundle\ReportBundle\Domain\CQRS\TaxReportQuery $domainQuery)
    {
        $this->domainQuery = $domainQuery;
    }

    /**
     * @Rest\Get("")
     */
    public function taxSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->taxSummary($request->getQueryParams()),
        ];

    }

}
