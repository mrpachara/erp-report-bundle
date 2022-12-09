<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface ProjectBoqReportQuery
{
    function projectBoqSummary(string $idProject);
    function projectBoqWithoutValueSummary(string $idProject);
    function projectBoqSummaryEach(string $idProject, string $id);
    function projectBoqWithoutValueSummaryEach(string $idProject, string $id);
}
