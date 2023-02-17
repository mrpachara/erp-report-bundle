<?php

namespace Erp\Bundle\ReportBundle\Authorization;

interface ReportAuthorization
{
    /**
     * Can access report?
     */
    public function access(...$args): bool;

    /**
     * Can export excel file?
     */
    public function excel(...$args): bool;

    /**
     * Can export pdf file?
     */
    public function pdf(...$args): bool;
}
