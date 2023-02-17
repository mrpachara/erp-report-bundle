<?php

namespace Erp\Bundle\ReportBundle\Authorization;

class ProjectBoqWithoutValueReportAuthorization extends AbstractReportAuthorization
{
    /**
     * {@inheritDoc}
     */
    public function access(...$args): bool
    {
        return parent::access(...$args) &&
            $this->authorizationChecker->isGranted('ROLE_MASTER_PROJECT_BUDGET_VIEW');
    }
}
