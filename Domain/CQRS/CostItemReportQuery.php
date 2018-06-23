<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface CostItemReportQuery
{
    function costItemGroupSummary(array $filter = null);
    function costItemDistributionSummary(array $filter = null);
}
