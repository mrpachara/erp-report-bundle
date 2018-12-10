<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface CostItemExpenseReportQuery
{
    function costItemDistributionExpenseSummary(array $filter = null, array &$filterDetail = null);
    function costItemGroupExpenseSummary(array $filter = null, array &$filterDetail = null);
}
