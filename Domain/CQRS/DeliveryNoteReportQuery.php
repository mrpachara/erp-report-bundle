<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface DeliveryNoteReportQuery
{
    function deliveryNoteSummary(array $filter = null, array &$filterDetail = null);
}