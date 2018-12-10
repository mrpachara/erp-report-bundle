<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface PurchaseRequestReportQuery
{
    function purchaseRequestSummary(array $filter = null, array &$filterDetail = null);
}