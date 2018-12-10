<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface RetentionBillingNoteReportQuery
{
    function retentionBillingNoteSummary(array $filter = null, array &$filterDetail = null);
}