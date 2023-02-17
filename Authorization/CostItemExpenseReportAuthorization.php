<?php

namespace Erp\Bundle\ReportBundle\Authorization;

class CostItemExpenseReportAuthorization extends AbstractReportAuthorization
{
    /**
     * {@inheritDoc}
     */
    public function access(...$args): bool
    {
        return parent::access(...$args) && (
            ($this->quantity(...$args) || $this->price(...$args))
        );
    }

    public function quantity(...$args): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_REPORT_MT_CI_QUANTITY_EP');
    }

    public function price(...$args): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_REPORT_MT_CI_PRICE');
    }
}
