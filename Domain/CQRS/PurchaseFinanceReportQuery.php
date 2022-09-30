<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface PurchaseFinanceReportQuery
{
    function summarize(array $filter = null, array &$filterDetail = null);
}
