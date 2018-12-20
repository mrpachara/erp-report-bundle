<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface CostItemPurchaseOrderReportQuery
{
    function costItemGroupPurchaseOrderSummary(array $filter = null, array &$filterDetail = null);
    function costItemDistributionPurchaseOrderSummary(array $filter = null, array &$filterDetail = null);
}
