<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface ProjectIncomeReportQuery
{
    function projectIncomeSummary(string $idProject);
    function projectContractSummary(string $idProject);
}
