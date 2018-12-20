<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface PayMethodExpenseReportQuery
{
    function payMethodExpenseSummary(array $filter = null, array &$filterDetail = null);
}