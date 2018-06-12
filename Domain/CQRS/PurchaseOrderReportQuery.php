<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface PurchaseOrderReportQuery
{
    function purchaseOrderSummary(array $filter = null);
}