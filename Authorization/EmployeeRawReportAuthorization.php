<?php

namespace Erp\Bundle\ReportBundle\Authorization;

class EmployeeRawReportAuthorization extends AbstractReportAuthorization
{
    /**
     * {@inheritDoc}
     */
    public function access(...$args): bool
    {
        return parent::access(...$args) &&
            $this->authorizationChecker->isGranted('ROLE_REPORT_MT_EM_RAW');
    }
}
