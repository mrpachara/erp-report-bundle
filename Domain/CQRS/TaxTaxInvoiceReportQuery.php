<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface TaxTaxInvoiceReportQuery
{
    function taxTaxInvoiceSummary(array $filter = null, array &$filterDetail = null);
}
