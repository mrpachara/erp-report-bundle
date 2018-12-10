<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface ExpenseReportQuery
{
    function expenseSummary(array $filter = null, array &$filterDetail = null);
}