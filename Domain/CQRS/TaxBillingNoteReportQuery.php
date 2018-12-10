<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface TaxBillingNoteReportQuery
{
    function taxBillingNoteSummary(array $filter = null, array &$filterDetail = null);
}
