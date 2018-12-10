<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface RevenueReportQuery
{
    function revenueSummary(array $filter = null, array &$filterDetail = null);
}