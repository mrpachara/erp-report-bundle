<?php

namespace Erp\Bundle\ReportBundle\Authorization;

class PurchaseOrderReportAuthorization extends AbstractReportAuthorization
{
    /**
     * {@inheritDoc}
     */
    public function access(...$args): bool
    {
        return parent::access(...$args) &&
            $this->authorizationChecker->isGranted('ROLE_REPORT_PU_PO');
    }
}
