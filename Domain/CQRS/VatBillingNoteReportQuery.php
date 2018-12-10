<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface VatBillingNoteReportQuery
{
    function vatBillingNoteSummary(array $filter = null, array &$filterDetail = null);
}
