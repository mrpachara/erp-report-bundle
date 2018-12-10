<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface CostItemGoodsReceiptReportQuery
{
    function costItemDistributionGoodsReceiptSummary(array $filter = null, array &$filterDetail = null);
    function costItemGroupGoodsReceiptSummary(array $filter = null, array &$filterDetail = null);
}
