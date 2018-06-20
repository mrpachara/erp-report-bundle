<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface CostItemReportQuery
{
    function costItemSummary(array $filter = null);
    function costItemGroupPriceSummary(array $filter = null);
    function costItemDistributionQuantitySummary(array $filter = null);
    function costItemDistributionPriceSummary(array $filter = null);
}
