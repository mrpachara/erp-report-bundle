<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface RequesterReportQuery
{
  function requesterSummary(array $filter = null);
}
