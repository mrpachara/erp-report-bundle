<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface VatTaxInvoiceReportQuery
{
    function vatTaxInvoiceSummary(array $filter = null, array &$filterDetail = null);
}
