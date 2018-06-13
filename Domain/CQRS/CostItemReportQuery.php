<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface CostItemReportQuery
{
    function costItemSummary(array $filter = null);
}