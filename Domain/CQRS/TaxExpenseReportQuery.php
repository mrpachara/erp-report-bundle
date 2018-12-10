<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface TaxExpenseReportQuery
{
    function taxExpenseSummary(array $filter = null, array &$filterDetail = null);
}
