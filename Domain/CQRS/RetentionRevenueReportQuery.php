<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface RetentionRevenueReportQuery
{
    function retentionRevenueSummary(array $filter = null, array &$filterDetail = null);
}