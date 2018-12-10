<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface VatRevenueReportQuery
{
    function vatRevenueSummary(array $filter = null, array &$filterDetail = null);
}
