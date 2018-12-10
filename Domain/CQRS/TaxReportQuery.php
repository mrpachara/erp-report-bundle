<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface TaxReportQuery
{
    function taxSummary(array $filter = null, array &$filterDetail = null);
}
