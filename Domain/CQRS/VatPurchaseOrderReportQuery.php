<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface VatPurchaseOrderReportQuery
{
    function vatPurchaseOrderSummary(array $filter = null, array &$filterDetail = null);
}
