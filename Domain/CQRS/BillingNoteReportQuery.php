<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface BillingNoteReportQuery
{
    function billingNoteSummary(array $filter = null, array &$filterDetail = null);
}