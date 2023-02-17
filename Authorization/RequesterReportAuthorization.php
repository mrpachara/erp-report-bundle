<?php

namespace Erp\Bundle\ReportBundle\Authorization;

class RequesterReportAuthorization extends AbstractReportAuthorization
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
        return $this->authorizationChecker->isGranted('ROLE_REPORT_REQUESTER_QUANTITY');
    }

    public function price(...$args): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_REPORT_REQUESTER_PRICE');
    }
}
