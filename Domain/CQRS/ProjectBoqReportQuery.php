<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface ProjectBoqReportQuery
{
    function projectBoqSummary(string $idProject);
    function projectBoqSummaryEach(string $idProject, string $id);
}