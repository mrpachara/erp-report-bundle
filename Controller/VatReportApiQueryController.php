<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;


/**
 * vat Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/vat")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class VatReportApiQueryController
{

/** @var \Erp\Bundle\ReportBundle\Domain\CQRS\VatReportQuery */
    private $domainQuery;

    /**
     * TaxReportQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\VatReportQuery $domainQuery
     */
    public function __construct(\Erp\Bundle\ReportBundle\Domain\CQRS\VatReportQuery $domainQuery)
    {
        $this->domainQuery = $domainQuery;
    }

    /**
     * @Rest\Get("")
     */
    public function vatSummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->vatSummary($request->getQueryParams()),
        ];

    }

}
