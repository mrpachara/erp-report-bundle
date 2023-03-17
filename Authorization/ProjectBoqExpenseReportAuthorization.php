<?php

namespace Erp\Bundle\ReportBundle\Authorization;

class ProjectBoqExpenseReportAuthorization extends AbstractReportAuthorization
{
    /**
     * {@inheritDoc}
     */
    public function access(...$args): bool
    {
        return parent::access(...$args) &&
            $this->authorizationChecker->isGranted('ROLE_REPORT_MT_PJ_PU_EP');
    }
}
