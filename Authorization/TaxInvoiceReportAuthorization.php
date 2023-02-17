<?php

namespace Erp\Bundle\ReportBundle\Authorization;

class TaxInvoiceReportAuthorization extends AbstractReportAuthorization
{
    /**
     * {@inheritDoc}
     */
    public function access(...$args): bool
    {
        return parent::access(...$args) &&
            $this->authorizationChecker->isGranted('ROLE_REPORT_IN_TI');
    }
}
