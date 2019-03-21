<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface ProjectContractReportQuery
{
    function projectContractSummary(string $idProject);
    function projectContractSummaryEach(string $idProject, string $id);
}