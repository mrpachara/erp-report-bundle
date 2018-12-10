<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface RequesterReportQuery
{
    function requesterGroupSummary(array $filter = null, array &$filterDetail = null);
    function requesterDistributionSummary(array $filter = null, array &$filterDetail = null);
}
