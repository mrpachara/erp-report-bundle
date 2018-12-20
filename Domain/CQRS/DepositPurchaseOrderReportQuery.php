<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface DepositPurchaseOrderReportQuery
{
    function depositPurchaseOrderSummary(array $filter = null, array &$filterDetail = null);
}
