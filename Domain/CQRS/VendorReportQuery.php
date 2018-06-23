<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface VendorReportQuery
{
  function vendorGroupSummary(array $filter = null);
  function vendorDistributionSummary(array $filter = null);  
}
