<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface PayMethodBillingNoteReportQuery
{
    function payMethodBillingNoteSummary(array $filter = null, array &$filterDetail = null);
}