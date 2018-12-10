<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface WarrantyReportQuery
{
    function warrantySummary(array $filter = null, array &$filterDetail = null);
}
