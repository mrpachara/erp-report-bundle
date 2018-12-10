<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface VatExpenseReportQuery
{
    function vatExpenseSummary(array $filter = null, array &$filterDetail = null);
}
