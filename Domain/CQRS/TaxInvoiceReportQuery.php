<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface TaxInvoiceReportQuery
{
    function taxInvoiceSummary(array $filter = null, array &$filterDetail = null);
}