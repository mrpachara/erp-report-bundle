<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface RetentionTaxInvoiceReportQuery
{
    function retentionTaxInvoiceSummary(array $filter = null, array &$filterDetail = null);
}