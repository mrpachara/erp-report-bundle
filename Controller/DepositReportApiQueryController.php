<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;


/**
 * vat Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/deposit")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class DepositReportApiQueryController
{

/** @var \Erp\Bundle\ReportBundle\Domain\CQRS\DepositReportQuery */
    private $domainQuery;

    /**
     * TaxReportQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\DepositReportQuery $domainQuery
     */
    public function __construct(\Erp\Bundle\ReportBundle\Domain\CQRS\DepositReportQuery $domainQuery)
    {
        $this->domainQuery = $domainQuery;
    }

    /**
     * @Rest\Get("")
     */
    public function depositSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->depositSummary($request->getQueryParams()),
        ];

    }

}
