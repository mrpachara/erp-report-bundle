<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface TaxPurchaseOrderReportQuery
{
    function taxPurchaseOrderSummary(array $filter = null, array &$filterDetail = null);
}
