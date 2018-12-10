<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface DepositReportQuery
{
    function depositSummary(array $filter = null, array &$filterDetail = null);
}
