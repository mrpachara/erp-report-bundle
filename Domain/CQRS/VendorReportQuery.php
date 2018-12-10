<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface VendorReportQuery
{
  function vendorGroupSummary(array $filter = null, array &$filterDetail = null);
  function vendorDistributionSummary(array $filter = null, array &$filterDetail = null);  
}
