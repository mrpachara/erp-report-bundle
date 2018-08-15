<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface RequesterReportQuery
{
  function requesterGroupSummary(array $filter = null);
  function requesterDistributionSummary(array $filter = null);
}
