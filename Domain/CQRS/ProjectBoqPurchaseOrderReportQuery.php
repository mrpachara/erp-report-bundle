<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface ProjectBoqPurchaseOrderReportQuery
{
    function projectBoqPOSummary(string $idProject);
    function projectBoqPOSummaryEach(string $idProject, string $id);
}