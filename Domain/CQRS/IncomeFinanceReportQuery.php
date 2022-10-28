<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface IncomeFinanceReportQuery
{
    function summarize(array $filter = null, array &$filterDetail = null);
}
