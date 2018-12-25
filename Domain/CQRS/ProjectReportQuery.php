<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface ProjectReportQuery
{
    function projectSummary(string $idProject);
    function projectBoqSummary(string $idProject);
}
