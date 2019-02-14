<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface ProjectBoqExpenseReportQuery
{
    function projectBoqEPSummary(string $idProject);
    function projectBoqEPSummaryEach(string $idProject, string $id);
}