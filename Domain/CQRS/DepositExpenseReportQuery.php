<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface DepositExpenseReportQuery
{
    function depositExpenseSummary(array $filter = null, array &$filterDetail = null);
}
