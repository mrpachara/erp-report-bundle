<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface WarrantyExpenseReportQuery
{
    function warrantyExpenseSummary(array $filter = null, array &$filterDetail = null);
}
