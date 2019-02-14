<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface ProjectBoqPurchaseRequestReportQuery
{
    function projectBoqPRSummary(string $idProject);
    function projectBoqPRSummaryEach(string $idProject, string $id);
}