<?php

namespace Erp\Bundle\ReportBundle\Authorization;

class ProjectBoqReportAuthorization extends AbstractReportAuthorization
{
    /**
     * {@inheritDoc}
     */
    public function access(...$args): bool
    {
        return parent::access(...$args) &&
            $this->authorizationChecker->isGranted('ROLE_REPORT_MT_PJ_PU');
    }

    public function reportAll(...$args): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_REPORT_MT_PJ_PU_ALL');
    }
}
