<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface PayMethodRevenueReportQuery
{
    function payMethodRevenueSummary(array $filter = null, array &$filterDetail = null);
}