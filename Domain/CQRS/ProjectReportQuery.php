<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface ProjectReportQuery
{
    function boqSummary(string $idProject);
}
