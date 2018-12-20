<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface WarrantyPurchaseOrderReportQuery
{
    function warrantyPurchaseOrderSummary(array $filter = null, array &$filterDetail = null);
}
