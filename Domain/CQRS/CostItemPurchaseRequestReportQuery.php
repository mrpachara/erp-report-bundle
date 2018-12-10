<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface CostItemPurchaseRequestReportQuery
{
    function costItemDistributionPurchaseRequestSummary(array $filter = null, array &$filterDetail = null);
}
