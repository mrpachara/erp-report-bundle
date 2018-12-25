<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface ProjectReportQuery
{
    function projectBoqSummary(array $filter = null, array &$filterDetail = null);
}
