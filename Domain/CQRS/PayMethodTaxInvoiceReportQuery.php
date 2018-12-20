<?php

namespace Erp\Bundle\ReportBundle\Domain\CQRS;

interface PayMethodTaxInvoiceReportQuery
{
    function payMethodTaxInvoiceSummary(array $filter = null, array &$filterDetail = null);
}