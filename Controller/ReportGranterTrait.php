<?php

namespace Erp\Bundle\ReportBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

trait ReportGranterTrait
{
    /**
     * Check if granted.
     *
     * @throw AccessDeniedException
     */
    private function grant(bool $isGranted): void
    {
        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }
}
