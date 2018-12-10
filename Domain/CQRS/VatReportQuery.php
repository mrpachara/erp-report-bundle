<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface VatReportQuery
{
    function vatSummary(array $filter = null, array &$filterDetail = null);
}
