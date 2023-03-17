<?php

namespace Erp\Bundle\ReportBundle\Authorization;

class ProjectContractReportAuthorization extends AbstractReportAuthorization
{
    /**
     * {@inheritDoc}
     */
    public function access(...$args): bool
    {
        return parent::access(...$args) &&
            $this->authorizationChecker->isGranted('ROLE_REPORT_MT_PJ_IN');
    }

    public function reportDeliveryNote(...$args): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_REPORT_MT_PJ_IN_DN');
    }

    public function reportBillingNote(...$args): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_REPORT_MT_PJ_IN_BN');
    }

    public function reportTaxInvoice(...$args): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_REPORT_MT_PJ_IN_TI');
    }

    public function reportRevenue(...$args): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_REPORT_MT_PJ_IN_RV');
    }

    public function reportAll(...$args): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_REPORT_MT_PJ_IN_ALL');
    }
}
