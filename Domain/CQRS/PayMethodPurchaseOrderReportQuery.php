<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface PayMethodPurchaseOrderReportQuery
{
    function payMethodPurchaseOrderSummary(array $filter = null, array &$filterDetail = null);
}