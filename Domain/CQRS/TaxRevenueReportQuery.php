<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface TaxRevenueReportQuery
{
    function taxRevenueSummary(array $filter = null, array &$filterDetail = null);
}
