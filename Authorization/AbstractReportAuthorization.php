<?php

namespace Erp\Bundle\ReportBundle\Authorization;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

abstract class AbstractReportAuthorization implements ReportAuthorization
{
    protected Security $security;
    protected AuthorizationCheckerInterface $authorizationChecker;

    /** @required */
    public function setSecurity(Security $security)
    {
        $this->security = $security;
    }

    /** @required */
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function access(...$args): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function excel(...$args): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_REPORT_EXCEL');
    }

    /**
     * {@inheritDoc}
     */
    public function pdf(...$args): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_REPORT_PDF');
    }
}
