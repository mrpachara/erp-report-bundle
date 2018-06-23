<?php

namespace Erp\Bundle\ReportBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Http\Message\ServerRequestInterface;


/**
 * vat Report Api Controller
 *
 * @Rest\Version("1.0")
 * @Rest\Route("/api/report/vendor")
 * @Rest\View(serializerEnableMaxDepthChecks=true)
 */
class VendorReportApiQueryController
{

/** @var \Erp\Bundle\ReportBundle\Domain\CQRS\VendorReportQuery */
    private $domainQuery;

    /**
     * TaxReportQueryController constructor.
     * @param \Erp\Bundle\ReportBundle\Domain\CQRS\VendorReportQuery $domainQuery
     */
    public function __construct(\Erp\Bundle\ReportBundle\Domain\CQRS\VendorReportQuery $domainQuery)
    {
        $this->domainQuery = $domainQuery;
    }

    /**
     * @Rest\Get("/group")
     */
    public function vendorGroupQuantitySummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->vendorGroupSummary($request->getQueryParams()),
        ];

    }


    /**
     * @Rest\Get("/distribution")
     */
    public function vendorDistributionQuantitySummaryAction(ServerRequestInterface $request)
    {
        return [
            'data' => $this->domainQuery->vendorDistributionSummary($request->getQueryParams()),
        ];

    }



}
